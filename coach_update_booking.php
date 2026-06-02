<?php
session_start();
require_once "db.php";

date_default_timezone_set("Africa/Cairo");

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$current_user_id = intval($_SESSION["user_id"]);
$current_role = $_SESSION["role"] ?? "user";

$booking_id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;
$status = $_GET["status"] ?? "";

if ($booking_id <= 0) {
    header("Location: coach_chats.php");
    exit();
}

/*
    For now, coaches are only allowed to confirm bookings.
    Admins can also use this file if needed.
*/
if ($status !== "confirmed") {
    header("Location: coach_chats.php");
    exit();
}

/*
    Load booking and connected coach account.
*/
$sql = "
    SELECT 
        bookings.id,
        bookings.status,
        bookings.coach_id,
        coaches.user_id AS coach_user_id
    FROM bookings
    INNER JOIN coaches ON bookings.coach_id = coaches.id
    WHERE bookings.id = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    header("Location: coach_chats.php?error=booking_not_found");
    exit();
}

$booking = $result->fetch_assoc();

$is_admin = $current_role === "admin";
$is_booking_coach = intval($booking["coach_user_id"]) === $current_user_id;

if (!$is_admin && !$is_booking_coach) {
    header("Location: coach_chats.php?error=access_denied");
    exit();
}

if ($booking["status"] === "cancelled") {
    header("Location: coach_chats.php?error=cancelled_booking");
    exit();
}

$update_sql = "UPDATE bookings SET status = 'confirmed' WHERE id = ?";
$update_stmt = $conn->prepare($update_sql);
$update_stmt->bind_param("i", $booking_id);

if ($update_stmt->execute()) {
    header("Location: coach_chats.php?confirmed=1");
    exit();
}

header("Location: coach_chats.php?error=update_failed");
exit();