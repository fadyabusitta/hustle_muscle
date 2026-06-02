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

$thread_id = isset($_GET["thread_id"]) ? intval($_GET["thread_id"]) : 0;
$booking_id = isset($_GET["booking_id"]) ? intval($_GET["booking_id"]) : 0;
$class_id = isset($_GET["class_id"]) ? intval($_GET["class_id"]) : 0;
$member_id = isset($_GET["member_id"]) ? intval($_GET["member_id"]) : 0;

$error = "";
$thread = null;

/*
    ========================================================
    BOOKING CHAT
    ========================================================
*/
if ($booking_id > 0) {

    $booking_sql = "
        SELECT 
            bookings.id AS booking_id,
            bookings.user_id AS member_id,
            bookings.coach_id,
            bookings.booking_date,
            bookings.time_slot,
            bookings.status,
            users.full_name AS member_name,
            users.email AS member_email,
            coaches.name AS coach_name,
            coaches.specialty,
            coaches.user_id AS coach_user_id
        FROM bookings
        INNER JOIN users ON bookings.user_id = users.id
        INNER JOIN coaches ON bookings.coach_id = coaches.id
        WHERE bookings.id = ?
        LIMIT 1
    ";

    $booking_stmt = $conn->prepare($booking_sql);
    $booking_stmt->bind_param("i", $booking_id);
    $booking_stmt->execute();
    $booking_result = $booking_stmt->get_result();

    if ($booking_result->num_rows !== 1) {
        $error = "Booking not found.";
    } else {
        $booking = $booking_result->fetch_assoc();

        $is_member = intval($booking["member_id"]) === $current_user_id;
        $is_coach = intval($booking["coach_user_id"]) === $current_user_id;
        $is_admin = $current_role === "admin";

        if (!$is_member && !$is_coach && !$is_admin) {
            $error = "You are not allowed to access this chat.";
        } elseif ($booking["status"] === "cancelled") {
            $error = "Chat is not available for cancelled bookings.";
        } else {

            $existing_sql = "SELECT id FROM chat_threads WHERE booking_id = ? LIMIT 1";
            $existing_stmt = $conn->prepare($existing_sql);
            $existing_stmt->bind_param("i", $booking_id);
            $existing_stmt->execute();
            $existing_result = $existing_stmt->get_result();

            if ($existing_result->num_rows === 1) {
                $existing = $existing_result->fetch_assoc();
                $thread_id = intval($existing["id"]);
            } else {
                $create_sql = "
                    INSERT INTO chat_threads (user_id, coach_id, booking_id, class_id)
                    VALUES (?, ?, ?, NULL)
                ";

                $create_stmt = $conn->prepare($create_sql);
                $create_stmt->bind_param(
                    "iii",
                    $booking["member_id"],
                    $booking["coach_id"],
                    $booking_id
                );

                if ($create_stmt->execute()) {
                    $thread_id = $create_stmt->insert_id;
                } else {
                    $error = "Failed to create chat thread.";
                }
            }
        }
    }
}

/*
    ========================================================
    CLASS CHAT
    Supports:
    User side:
        chat.php?class_id=6

    Coach/Admin side:
        chat.php?class_id=6&member_id=5
    ========================================================
*/
if (empty($error) && $class_id > 0) {

    $class_sql = "
        SELECT
            classes.id AS class_id,
            classes.name AS class_name,
            classes.category,
            classes.instructor,
            classes.schedule,
            classes.duration_min,
            classes.coach_id,
            coaches.name AS coach_name,
            coaches.specialty,
            coaches.user_id AS coach_user_id
        FROM classes
        LEFT JOIN coaches ON classes.coach_id = coaches.id
        WHERE classes.id = ?
        LIMIT 1
    ";

    $class_stmt = $conn->prepare($class_sql);
    $class_stmt->bind_param("i", $class_id);
    $class_stmt->execute();
    $class_result = $class_stmt->get_result();

    if ($class_result->num_rows !== 1) {
        $error = "Class not found.";
    } else {
        $class = $class_result->fetch_assoc();

        if (empty($class["coach_id"]) || empty($class["coach_user_id"])) {
            $error = "This class is not linked to a coach account yet.";
        } else {

            $coach_user_id = intval($class["coach_user_id"]);
            $is_class_coach = $coach_user_id === $current_user_id;
            $is_admin = $current_role === "admin";

            /*
                If a member opens class chat from classes.php or dashboard.php,
                member_id is not passed, so the current user is the member.
            */
            $target_member_id = $member_id > 0 ? $member_id : $current_user_id;

            /*
                Normal users cannot choose another member manually.
            */
            if (!$is_class_coach && !$is_admin && $target_member_id !== $current_user_id) {
                $error = "You are not allowed to open another member's class chat.";
            } elseif ($target_member_id === $coach_user_id) {
                $error = "You cannot open a class chat with yourself as the instructor.";
            } else {

                /*
                    Check that the target member is enrolled in this class.
                */
                $enroll_sql = "
                    SELECT 
                        enrollments.id,
                        users.full_name,
                        users.email
                    FROM enrollments
                    INNER JOIN users ON enrollments.user_id = users.id
                    WHERE enrollments.user_id = ?
                    AND enrollments.class_id = ?
                    LIMIT 1
                ";

                $enroll_stmt = $conn->prepare($enroll_sql);
                $enroll_stmt->bind_param("ii", $target_member_id, $class_id);
                $enroll_stmt->execute();
                $enroll_result = $enroll_stmt->get_result();

                if ($enroll_result->num_rows !== 1) {
                    $error = "This member is not enrolled in this class.";
                } else {

                    /*
                        Authorization:
                        - enrolled member can open his own class chat
                        - class coach can open enrolled members' chats
                        - admin can open any class chat
                    */
                    $is_target_member = $target_member_id === $current_user_id;

                    if (!$is_target_member && !$is_class_coach && !$is_admin) {
                        $error = "You are not allowed to access this class chat.";
                    } else {

                        /*
                            One class chat per member per class.
                        */
                        $existing_sql = "
                            SELECT id 
                            FROM chat_threads 
                            WHERE class_id = ?
                            AND user_id = ?
                            LIMIT 1
                        ";

                        $existing_stmt = $conn->prepare($existing_sql);
                        $existing_stmt->bind_param("ii", $class_id, $target_member_id);
                        $existing_stmt->execute();
                        $existing_result = $existing_stmt->get_result();

                        if ($existing_result->num_rows === 1) {
                            $existing = $existing_result->fetch_assoc();
                            $thread_id = intval($existing["id"]);
                        } else {

                            $create_sql = "
                                INSERT INTO chat_threads (user_id, coach_id, booking_id, class_id)
                                VALUES (?, ?, NULL, ?)
                            ";

                            $create_stmt = $conn->prepare($create_sql);
                            $create_stmt->bind_param(
                                "iii",
                                $target_member_id,
                                $class["coach_id"],
                                $class_id
                            );

                            if ($create_stmt->execute()) {
                                $thread_id = $create_stmt->insert_id;
                            } else {
                                $error = "Failed to create class chat thread.";
                            }
                        }
                    }
                }
            }
        }
    }
}

/*
    ========================================================
    LOAD THREAD DATA
    ========================================================
*/
if (empty($error) && $thread_id > 0) {

    $thread_sql = "
        SELECT 
            chat_threads.id AS thread_id,
            chat_threads.user_id AS member_id,
            chat_threads.coach_id,
            chat_threads.booking_id,
            chat_threads.class_id,

            users.full_name AS member_name,
            users.email AS member_email,

            coaches.name AS coach_name,
            coaches.specialty,
            coaches.user_id AS coach_user_id,

            bookings.booking_date,
            bookings.time_slot,
            bookings.status AS booking_status,

            classes.name AS class_name,
            classes.category AS class_category,
            classes.schedule AS class_schedule,
            classes.duration_min AS class_duration
        FROM chat_threads
        INNER JOIN users ON chat_threads.user_id = users.id
        INNER JOIN coaches ON chat_threads.coach_id = coaches.id
        LEFT JOIN bookings ON chat_threads.booking_id = bookings.id
        LEFT JOIN classes ON chat_threads.class_id = classes.id
        WHERE chat_threads.id = ?
        LIMIT 1
    ";

    $thread_stmt = $conn->prepare($thread_sql);
    $thread_stmt->bind_param("i", $thread_id);
    $thread_stmt->execute();
    $thread_result = $thread_stmt->get_result();

    if ($thread_result->num_rows !== 1) {
        $error = "Chat thread not found.";
    } else {
        $thread = $thread_result->fetch_assoc();

        $is_member = intval($thread["member_id"]) === $current_user_id;
        $is_coach = intval($thread["coach_user_id"]) === $current_user_id;
        $is_admin = $current_role === "admin";

        if (!$is_member && !$is_coach && !$is_admin) {
            $error = "You are not allowed to access this chat.";
        }
    }
}

if ($thread_id <= 0 && empty($error)) {
    $error = "No chat selected.";
}

/*
    ========================================================
    PAGE TITLES
    ========================================================
*/
$chat_title = "Coach Chat";
$chat_subtitle = "";
$context_label = "";

if ($thread) {

    $is_class_chat = !empty($thread["class_id"]);
    $is_booking_chat = !empty($thread["booking_id"]);

    if (intval($thread["member_id"]) === $current_user_id) {
        $chat_title = "Chat with " . $thread["coach_name"];
    } else {
        $chat_title = "Chat with " . $thread["member_name"];
    }

    if ($is_class_chat) {
        $chat_subtitle = "Class: " . $thread["class_name"] . " — " . $thread["class_category"];
        $context_label = "Class";
    } elseif ($is_booking_chat) {
        $chat_subtitle = "Private booking with " . $thread["coach_name"];
        $context_label = "Booking";
    } else {
        $chat_subtitle = $thread["specialty"];
        $context_label = "Conversation";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Coach Chat — Hustle Muscle</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="chat.css?v=3">
    <script src="https://kit.fontawesome.com/f990be7b56.js" crossorigin="anonymous"></script>
</head>

<body>

<header class="site-header" id="header">
    <a href="index.php" class="logo-link">
        <i class="fa-solid fa-x x-icon"></i>Hustle Muscle<sup>©</sup>
    </a>

    <nav class="site-nav">
        <a href="index.php">Home</a>
        <a href="coaches.php">Coaches</a>
        <a href="classes.php">Classes</a>
        <a href="dashboard.php">Dashboard</a>

        <?php if ($current_role === "admin"): ?>
            <a href="admin_dashboard.php">Admin</a>
        <?php endif; ?>

        <span class="nav-divider">|</span>
        <a href="logout.php" class="nav-cta">Logout</a>
    </nav>
</header>

<section class="chat-hero">
    <div class="chat-hero-bg" id="heroBg"></div>

    <div class="chat-hero-content">
        <p class="chat-eyebrow">Hustle Muscle Communication</p>

        <h1 class="chat-title-main">
            <span class="line"><span>COACH</span></span>
            <span class="line"><span>CHAT</span></span>
        </h1>

        <p class="chat-sub-main">
            Send messages directly between members and assigned coaches using a PHP, MySQL, and AJAX polling system.
        </p>
    </div>
</section>

<div class="marquee-strip" aria-hidden="true">
    <div class="marquee-track">
        <span>COACH CHAT</span>
        <span>MEMBER SUPPORT</span>
        <span>PRIVATE SESSION</span>
        <span>CLASS SUPPORT</span>
        <span>TRAIN HARDER</span>
        <span>NO EXCUSES</span>
        <span>COACH CHAT</span>
        <span>MEMBER SUPPORT</span>
        <span>PRIVATE SESSION</span>
        <span>CLASS SUPPORT</span>
        <span>TRAIN HARDER</span>
        <span>NO EXCUSES</span>
    </div>
</div>

<main class="chat-content">

    <?php if (!empty($error)): ?>

        <section class="chat-panel error-panel reveal">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <h2>Chat Error</h2>
            <p><?php echo htmlspecialchars($error); ?></p>
            <a href="dashboard.php" class="main-btn">
                <span>Back to Dashboard</span>
            </a>
        </section>

    <?php else: ?>

        <section class="chat-panel reveal">

            <div class="chat-header-box">
                <div class="chat-avatar">
                    <i class="fa-solid fa-comments"></i>
                </div>

                <div>
                    <span class="section-tag">
                        <?php echo htmlspecialchars($context_label); ?> Conversation
                    </span>

                    <h2><?php echo htmlspecialchars($chat_title); ?></h2>

                    <p><?php echo htmlspecialchars($chat_subtitle); ?></p>

                    <?php if (!empty($thread["booking_date"])): ?>
                        <small>
                            Booking:
                            <?php echo htmlspecialchars($thread["booking_date"]); ?>
                            at
                            <?php echo htmlspecialchars(date("h:i A", strtotime($thread["time_slot"]))); ?>
                        </small>
                    <?php endif; ?>

                    <?php if (!empty($thread["class_schedule"])): ?>
                        <small>
                            Class:
                            <?php echo htmlspecialchars(date("D, M j, Y", strtotime($thread["class_schedule"]))); ?>
                            at
                            <?php echo htmlspecialchars(date("h:i A", strtotime($thread["class_schedule"]))); ?>
                            —
                            <?php echo htmlspecialchars($thread["class_duration"]); ?> min
                        </small>
                    <?php endif; ?>
                </div>
            </div>

            <div class="chat-box" id="chatBox">
                <div class="loading-messages">
                    Loading messages...
                </div>
            </div>

            <form id="messageForm" class="message-form">
                <input type="hidden" id="threadId" value="<?php echo htmlspecialchars($thread_id); ?>">

                <textarea id="messageInput" placeholder="Type your message..." maxlength="1000"></textarea>

                <button type="submit" class="send-btn">
                    <span>Send</span>
                    <i class="fa-solid fa-paper-plane"></i>
                </button>
            </form>

            <div class="chat-links">
                <a href="dashboard.php">← Back to Dashboard</a>
                <a href="coach_chats.php">Coach Chats →</a>
            </div>

        </section>

    <?php endif; ?>

</main>

<footer class="site-footer">
    <a href="index.php" class="logo-link">
        <i class="fa-solid fa-x x-icon"></i>Hustle Muscle<sup>©</sup>
    </a>

    <span class="footer-copy">© 2026 Hustle Muscle · Cairo, Egypt</span>

    <div class="footer-social">
        <a href="#"><i class="fa-brands fa-square-facebook"></i></a>
        <a href="#"><i class="fa-brands fa-instagram"></i></a>
        <a href="#"><i class="fa-brands fa-twitter"></i></a>
        <a href="#"><i class="fa-brands fa-youtube"></i></a>
    </div>
</footer>

<script>
    window.CHAT_CONFIG = {
        threadId: <?php echo intval($thread_id); ?>,
        currentUserId: <?php echo intval($current_user_id); ?>
    };
</script>

<script src="chat.js?v=3"></script>
</body>
</html>