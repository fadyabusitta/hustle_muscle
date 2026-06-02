<?php
session_start();
require_once "db.php";

date_default_timezone_set("Africa/Cairo");

header("Content-Type: application/json");

if (!isset($_SESSION["user_id"])) {
    echo json_encode([
        "success" => false,
        "message" => "You must be logged in.",
        "messages" => []
    ]);
    exit();
}

$current_user_id = intval($_SESSION["user_id"]);
$current_role = $_SESSION["role"] ?? "user";

$thread_id = intval($_GET["thread_id"] ?? 0);
$after_id = intval($_GET["after_id"] ?? 0);

if ($thread_id <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid thread.",
        "messages" => []
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
        "message" => "Chat not found.",
        "messages" => []
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
        "message" => "Access denied.",
        "messages" => []
    ]);
    exit();
}

/*
    Fetch new messages
*/
$message_sql = "
    SELECT 
        chat_messages.id,
        chat_messages.sender_id,
        chat_messages.message,
        chat_messages.created_at,
        users.full_name AS sender_name,
        users.role AS sender_role
    FROM chat_messages
    INNER JOIN users ON chat_messages.sender_id = users.id
    WHERE chat_messages.thread_id = ?
    AND chat_messages.id > ?
    ORDER BY chat_messages.id ASC
";

$message_stmt = $conn->prepare($message_sql);
$message_stmt->bind_param("ii", $thread_id, $after_id);
$message_stmt->execute();
$message_result = $message_stmt->get_result();

$messages = [];

while ($row = $message_result->fetch_assoc()) {
    $messages[] = [
        "id" => intval($row["id"]),
        "sender_id" => intval($row["sender_id"]),
        "sender_name" => $row["sender_name"],
        "sender_role" => $row["sender_role"],
        "message" => $row["message"],
        "created_at" => date("D, M j - h:i A", strtotime($row["created_at"]))
    ];
}

/*
    Mark messages from the other person as read.
*/
$read_sql = "
    UPDATE chat_messages
    SET is_read = 1
    WHERE thread_id = ?
    AND sender_id != ?
";

$read_stmt = $conn->prepare($read_sql);
$read_stmt->bind_param("ii", $thread_id, $current_user_id);
$read_stmt->execute();

echo json_encode([
    "success" => true,
    "messages" => $messages
]);
exit();