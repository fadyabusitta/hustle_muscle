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

if ($current_role !== "coach" && $current_role !== "admin") {
    header("Location: dashboard.php");
    exit();
}

$coach = null;
$coach_id = 0;
$coach_error = "";
$page_message = "";

if (isset($_GET["confirmed"])) {
    $page_message = "Booking confirmed successfully.";
}

if (isset($_GET["error"])) {
    switch ($_GET["error"]) {
        case "booking_not_found":
            $page_message = "Booking was not found.";
            break;
        case "access_denied":
            $page_message = "You are not allowed to update this booking.";
            break;
        case "cancelled_booking":
            $page_message = "Cancelled bookings cannot be confirmed.";
            break;
        case "update_failed":
            $page_message = "Failed to update booking.";
            break;
        default:
            $page_message = "Something went wrong.";
            break;
    }
}

/*
    If logged-in user is a coach, get the coach profile connected to this account.
*/
if ($current_role === "coach") {
    $coach_sql = "SELECT id, name, specialty FROM coaches WHERE user_id = ? LIMIT 1";
    $coach_stmt = $conn->prepare($coach_sql);
    $coach_stmt->bind_param("i", $current_user_id);
    $coach_stmt->execute();
    $coach_result = $coach_stmt->get_result();

    if ($coach_result->num_rows !== 1) {
        $coach_error = "No coach profile is linked to this account.";
    } else {
        $coach = $coach_result->fetch_assoc();
        $coach_id = intval($coach["id"]);
    }
}

/*
    ========================================================
    PRIVATE BOOKING CONVERSATIONS
    ========================================================
*/
if ($current_role === "admin") {

    $bookings_sql = "
        SELECT
            bookings.id AS booking_id,
            bookings.booking_date,
            bookings.time_slot,
            bookings.status,
            users.full_name AS member_name,
            users.email AS member_email,
            coaches.name AS coach_name,
            coaches.specialty
        FROM bookings
        INNER JOIN users ON bookings.user_id = users.id
        INNER JOIN coaches ON bookings.coach_id = coaches.id
        WHERE bookings.status != 'cancelled'
        ORDER BY bookings.booking_date DESC, bookings.time_slot DESC
    ";

    $bookings_result = $conn->query($bookings_sql);

} elseif (empty($coach_error)) {

    $bookings_sql = "
        SELECT
            bookings.id AS booking_id,
            bookings.booking_date,
            bookings.time_slot,
            bookings.status,
            users.full_name AS member_name,
            users.email AS member_email,
            coaches.name AS coach_name,
            coaches.specialty
        FROM bookings
        INNER JOIN users ON bookings.user_id = users.id
        INNER JOIN coaches ON bookings.coach_id = coaches.id
        WHERE bookings.coach_id = ?
        AND bookings.status != 'cancelled'
        ORDER BY bookings.booking_date DESC, bookings.time_slot DESC
    ";

    $bookings_stmt = $conn->prepare($bookings_sql);
    $bookings_stmt->bind_param("i", $coach_id);
    $bookings_stmt->execute();
    $bookings_result = $bookings_stmt->get_result();

} else {
    $bookings_result = null;
}

/*
    ========================================================
    CLASS ENROLLMENT CONVERSATIONS
    ========================================================
*/
if ($current_role === "admin") {

    $class_chats_sql = "
        SELECT
            enrollments.id AS enrollment_id,
            enrollments.user_id AS member_id,
            enrollments.enrolled_at,

            users.full_name AS member_name,
            users.email AS member_email,

            classes.id AS class_id,
            classes.name AS class_name,
            classes.category,
            classes.schedule,
            classes.duration_min,

            coaches.name AS coach_name,
            coaches.specialty,
            coaches.user_id AS coach_user_id,

            chat_threads.id AS existing_thread_id
        FROM enrollments
        INNER JOIN users ON enrollments.user_id = users.id
        INNER JOIN classes ON enrollments.class_id = classes.id
        INNER JOIN coaches ON classes.coach_id = coaches.id
        LEFT JOIN chat_threads 
            ON chat_threads.class_id = classes.id
            AND chat_threads.user_id = enrollments.user_id
        ORDER BY classes.schedule DESC, enrollments.enrolled_at DESC
    ";

    $class_chats_result = $conn->query($class_chats_sql);

} elseif (empty($coach_error)) {

    $class_chats_sql = "
        SELECT
            enrollments.id AS enrollment_id,
            enrollments.user_id AS member_id,
            enrollments.enrolled_at,

            users.full_name AS member_name,
            users.email AS member_email,

            classes.id AS class_id,
            classes.name AS class_name,
            classes.category,
            classes.schedule,
            classes.duration_min,

            coaches.name AS coach_name,
            coaches.specialty,
            coaches.user_id AS coach_user_id,

            chat_threads.id AS existing_thread_id
        FROM enrollments
        INNER JOIN users ON enrollments.user_id = users.id
        INNER JOIN classes ON enrollments.class_id = classes.id
        INNER JOIN coaches ON classes.coach_id = coaches.id
        LEFT JOIN chat_threads 
            ON chat_threads.class_id = classes.id
            AND chat_threads.user_id = enrollments.user_id
        WHERE classes.coach_id = ?
        ORDER BY classes.schedule DESC, enrollments.enrolled_at DESC
    ";

    $class_chats_stmt = $conn->prepare($class_chats_sql);
    $class_chats_stmt->bind_param("i", $coach_id);
    $class_chats_stmt->execute();
    $class_chats_result = $class_chats_stmt->get_result();

} else {
    $class_chats_result = null;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Coach Chats — Hustle Muscle</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="dashboard.css?v=5">
    <script src="https://kit.fontawesome.com/f990be7b56.js" crossorigin="anonymous"></script>
</head>

<body>

<header class="site-header" id="header">
    <a href="index.php" class="logo-link">
        <i class="fa-solid fa-x x-icon"></i>Hustle Muscle<sup>©</sup>
    </a>

    <nav class="site-nav">
        <a href="index.php">Home</a>
        <a href="dashboard.php">Dashboard</a>
        <a href="coach_chats.php">Coach Chats</a>

        <?php if ($current_role === "admin"): ?>
            <a href="admin_dashboard.php">Admin</a>
        <?php endif; ?>

        <span class="nav-divider">|</span>
        <a href="logout.php" class="nav-cta">Logout</a>
    </nav>
</header>

<section class="dashboard-hero">
    <div class="dashboard-hero-bg" id="heroBg"></div>

    <div class="dashboard-hero-content">
        <p class="dashboard-eyebrow">Coach Communication Center</p>

        <h1 class="dashboard-title">
            <span class="line"><span>COACH</span></span>
            <span class="line"><span>CHATS</span></span>
        </h1>

        <p class="dashboard-sub">
            View private booking conversations, confirm pending bookings, and reply to class enrollment chats.
        </p>
    </div>
</section>

<div class="marquee-strip" aria-hidden="true">
    <div class="marquee-track">
        <span>COACH CHATS</span>
        <span>CONFIRM BOOKINGS</span>
        <span>CLASS CHATS</span>
        <span>MEMBER SUPPORT</span>
        <span>PRIVATE BOOKING</span>
        <span>TRAIN HARDER</span>
        <span>NO EXCUSES</span>
        <span>COACH CHATS</span>
        <span>CONFIRM BOOKINGS</span>
        <span>CLASS CHATS</span>
        <span>MEMBER SUPPORT</span>
        <span>PRIVATE BOOKING</span>
        <span>TRAIN HARDER</span>
        <span>NO EXCUSES</span>
    </div>
</div>

<main class="dashboard-content">

    <?php if (!empty($page_message)): ?>
        <div class="message <?php echo isset($_GET["confirmed"]) ? 'success' : 'error'; ?> reveal">
            <?php echo htmlspecialchars($page_message); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($coach_error)): ?>

        <section class="dashboard-panel data-section reveal">
            <div class="empty-box">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <p><?php echo htmlspecialchars($coach_error); ?></p>
            </div>
        </section>

    <?php else: ?>

        <!-- PRIVATE BOOKING CHATS -->
        <section class="dashboard-panel data-section reveal">
            <div class="panel-header">
                <span class="section-tag">Private Coaching</span>
                <h2 class="section-title-small">BOOKING CONVERSATIONS</h2>
            </div>

            <?php if ($bookings_result && $bookings_result->num_rows > 0): ?>

                <div class="table-wrap">
                    <table class="data-table">
                        <tr>
                            <th>Member</th>
                            <th>Email</th>
                            <th>Coach</th>
                            <th>Specialty</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Status</th>
                            <th>Confirm</th>
                            <th>Chat</th>
                        </tr>

                        <?php while ($booking = $bookings_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($booking["member_name"]); ?></td>
                                <td><?php echo htmlspecialchars($booking["member_email"]); ?></td>
                                <td><?php echo htmlspecialchars($booking["coach_name"]); ?></td>
                                <td><?php echo htmlspecialchars($booking["specialty"]); ?></td>
                                <td><?php echo htmlspecialchars($booking["booking_date"]); ?></td>
                                <td><?php echo htmlspecialchars(date("h:i A", strtotime($booking["time_slot"]))); ?></td>
                                <td>
                                    <span class="status status-<?php echo htmlspecialchars($booking["status"]); ?>">
                                        <?php echo htmlspecialchars($booking["status"]); ?>
                                    </span>
                                </td>

                                <td>
                                    <?php if ($booking["status"] === "pending"): ?>
                                        <a class="confirm-action js-confirm"
                                           href="coach_update_booking.php?id=<?php echo $booking["booking_id"]; ?>&status=confirmed"
                                           data-confirm="Confirm this booking?">
                                            Confirm
                                        </a>
                                    <?php else: ?>
                                        <span class="muted-text">Confirmed</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <a class="chat-action" href="chat.php?booking_id=<?php echo $booking["booking_id"]; ?>">
                                        Open Chat
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>

                    </table>
                </div>

            <?php else: ?>

                <div class="empty-box">
                    <i class="fa-solid fa-comments"></i>
                    <p>No active private booking chats available yet.</p>
                </div>

            <?php endif; ?>

        </section>

        <!-- CLASS ENROLLMENT CHATS -->
        <section class="dashboard-panel data-section reveal">
            <div class="panel-header">
                <span class="section-tag">Class Support</span>
                <h2 class="section-title-small">CLASS ENROLLMENT CHATS</h2>
            </div>

            <?php if ($class_chats_result && $class_chats_result->num_rows > 0): ?>

                <div class="table-wrap">
                    <table class="data-table">
                        <tr>
                            <th>Member</th>
                            <th>Email</th>
                            <th>Class</th>
                            <th>Category</th>
                            <th>Coach</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Duration</th>
                            <th>Chat</th>
                        </tr>

                        <?php while ($class_chat = $class_chats_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($class_chat["member_name"]); ?></td>
                                <td><?php echo htmlspecialchars($class_chat["member_email"]); ?></td>
                                <td><?php echo htmlspecialchars($class_chat["class_name"]); ?></td>
                                <td><?php echo htmlspecialchars($class_chat["category"]); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($class_chat["coach_name"]); ?>
                                    <br>
                                    <span class="muted-text">
                                        <?php echo htmlspecialchars($class_chat["specialty"]); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars(date("D, M j, Y", strtotime($class_chat["schedule"]))); ?></td>
                                <td><?php echo htmlspecialchars(date("h:i A", strtotime($class_chat["schedule"]))); ?></td>
                                <td><?php echo htmlspecialchars($class_chat["duration_min"]); ?> min</td>
                                <td>
                                    <a class="chat-action"
                                       href="chat.php?class_id=<?php echo $class_chat["class_id"]; ?>&member_id=<?php echo $class_chat["member_id"]; ?>">
                                        Open Chat
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>

                    </table>
                </div>

            <?php else: ?>

                <div class="empty-box">
                    <i class="fa-solid fa-clipboard-list"></i>
                    <p>No enrolled class members available for chat yet.</p>
                </div>

            <?php endif; ?>

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

<script src="dashboard.js?v=5"></script>
</body>

</html>