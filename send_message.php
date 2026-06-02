<?php
session_start();
require_once "db.php";

date_default_timezone_set("Africa/Cairo");

header("Content-Type: application/json");

if (!isset($_SESSION["user_id"])) {
    echo json_encode([
        "success" => false,
        "message" => "You must be logged in."
    ]);
    exit();
}

$current_user_id = intval($_SESSION["user_id"]);
$current_role = $_SESSION["role"] ?? "user";

$thread_id = intval($_POST["thread_id"] ?? 0);
$message = trim($_POST["message"] ?? "");

if ($thread_id <= 0 || empty($message)) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid message."
    ]);
    exit();
}

if (strlen($message) > 1000) {
    echo json_encode([
        "success" => false,
        "message" => "Message is too long."
    ]);
    exit();
}

/*
    Authorization check
*/
$thread_sql = "
    SELECT 
        chat_threads.id,
        chat_threads.user_id AS member_id,
        coaches.user_id AS coach_user_id
    FROM chat_threads
    INNER JOIN coaches ON chat_threads.coach_id = coaches.id
    WHERE chat_threads.id = ?
    LIMIT 1
";

$thread_stmt = $conn->prepare($thread_sql);
$thread_stmt->bind_param("i", $thread_id);
$thread_stmt->execute();
$thread_result = $thread_stmt->get_result();

if ($thread_result->num_rows !== 1) {
    echo json_encode([
        "success" => false,
        "message" => "Chat not found."
    ]);
    exit();
}

$thread = $thread_result->fetch_assoc();

$is_member = intval($thread["member_id"]) === $current_user_id;
$is_coach = intval($thread["coach_user_id"]) === $current_user_id;
$is_admin = $current_role === "admin";

if (!$is_member && !$is_coach && !$is_admin) {
    echo json_encode([
        "success" => false,
        "message" => "Access denied."
    ]);
    exit();
}

/*
    Insert message
*/
$insert_sql = "
    INSERT INTO chat_messages (thread_id, sender_id, message)
    VALUES (?, ?, ?)
";

$insert_stmt = $conn->prepare($insert_sql);
$insert_stmt->bind_param("iis", $thread_id, $current_user_id, $message);

if ($insert_stmt->execute()) {

    $update_sql = "UPDATE chat_threads SET updated_at = CURRENT_TIMESTAMP WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("i", $thread_id);
    $update_stmt->execute();

    echo json_encode([
        "success" => true,
        "message" => "Message sent."
    ]);
    exit();
}

echo json_encode([
    "success" => false,
    "message" => "Failed to send message."
]);
exit();