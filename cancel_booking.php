<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$booking_id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;

if ($booking_id <= 0) {
    header("Location: dashboard.php");
    exit();
}

/*
    We update only bookings that belong to the logged-in user.
    This prevents one user from cancelling another user's booking.
*/
$sql = "UPDATE bookings 
        SET status = 'cancelled'
        WHERE id = ? AND user_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();

header("Location: dashboard.php");
exit();
?>