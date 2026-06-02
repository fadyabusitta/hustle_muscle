<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: dashboard.php");
    exit();
}

$booking_id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;
$status = $_GET["status"] ?? "";

$allowed_statuses = ["pending", "confirmed", "cancelled"];

if ($booking_id <= 0 || !in_array($status, $allowed_statuses)) {
    header("Location: admin_dashboard.php");
    exit();
}

$sql = "UPDATE bookings SET status = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $status, $booking_id);
$stmt->execute();

header("Location: admin_dashboard.php");
exit();
?>