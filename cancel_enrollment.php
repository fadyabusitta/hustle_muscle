<?php
session_start();
require_once "db.php";

date_default_timezone_set("Africa/Cairo");

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$enrollment_id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;

if ($enrollment_id <= 0) {
    header("Location: dashboard.php");
    exit();
}

$conn->begin_transaction();

try {
    /*
        Get enrollment and class information.
        We only select enrollment that belongs to the logged-in user.
        This prevents users from cancelling other users' enrollments.
    */
    $select_sql = "
        SELECT 
            enrollments.id,
            enrollments.class_id,
            classes.schedule
        FROM enrollments
        INNER JOIN classes ON enrollments.class_id = classes.id
        WHERE enrollments.id = ? AND enrollments.user_id = ?
        LIMIT 1
        FOR UPDATE
    ";

    $select_stmt = $conn->prepare($select_sql);
    $select_stmt->bind_param("ii", $enrollment_id, $user_id);
    $select_stmt->execute();
    $select_result = $select_stmt->get_result();

    if ($select_result->num_rows !== 1) {
        $conn->rollback();
        header("Location: dashboard.php");
        exit();
    }

    $enrollment = $select_result->fetch_assoc();
    $class_id = $enrollment["class_id"];
    $class_time = strtotime($enrollment["schedule"]);

    /*
        Prevent cancelling classes that already started.
    */
    if ($class_time <= time()) {
        $conn->rollback();
        header("Location: dashboard.php");
        exit();
    }

    /*
        Delete enrollment.
    */
    $delete_sql = "DELETE FROM enrollments WHERE id = ? AND user_id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("ii", $enrollment_id, $user_id);
    $delete_stmt->execute();

    /*
        Decrease enrolled count safely.
    */
    $update_sql = "UPDATE classes 
                   SET enrolled = GREATEST(enrolled - 1, 0)
                   WHERE id = ?";

    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("i", $class_id);
    $update_stmt->execute();

    $conn->commit();

} catch (Exception $e) {
    $conn->rollback();
}

header("Location: dashboard.php");
exit();
?>