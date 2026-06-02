<?php
session_start();
require_once "db.php";

$allowed_plans = ["1month", "3months", "6months", "1year"];

$plan = $_GET["plan"] ?? "";

if (!in_array($plan, $allowed_plans)) {
    header("Location: index.php#plans");
    exit();
}

/*
    If user is not logged in, send him to signup page
    and keep the selected plan in the URL.
*/
if (!isset($_SESSION["user_id"])) {
    header("Location: signup.php?plan=" . urlencode($plan));
    exit();
}

/*
    If user is logged in, update his selected plan in database.
*/
$user_id = $_SESSION["user_id"];

$sql = "UPDATE users SET plan = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $plan, $user_id);

if ($stmt->execute()) {
    $_SESSION["plan"] = $plan;
    header("Location: dashboard.php");
    exit();
} else {
    echo "Failed to update membership plan.";
}
?>