<?php
session_start();
require_once "db.php";

date_default_timezone_set("Africa/Cairo");

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = intval($_SESSION["user_id"]);
$coach_id = isset($_GET["coach_id"]) ? intval($_GET["coach_id"]) : 0;

if ($coach_id <= 0) {
    header("Location: coaches.php");
    exit();
}

/*
    Get selected coach information from database.
*/
$coach_sql = "SELECT * FROM coaches WHERE id = ? LIMIT 1";
$coach_stmt = $conn->prepare($coach_sql);
$coach_stmt->bind_param("i", $coach_id);
$coach_stmt->execute();
$coach_result = $coach_stmt->get_result();

if ($coach_result->num_rows !== 1) {
    header("Location: coaches.php");
    exit();
}

$coach = $coach_result->fetch_assoc();

$error = "";
$success = "";

$is_own_coach_profile = !empty($coach["user_id"]) && intval($coach["user_id"]) === $user_id;

/*
    Handle booking form submission.
*/
if ($_SERVER["REQUEST_METHOD"] == "POST" && !$is_own_coach_profile) {
    $booking_date = $_POST["booking_date"] ?? "";
    $time_slot = $_POST["time_slot"] ?? "";

    if (empty($booking_date) || empty($time_slot)) {
        $error = "Please choose a date and time.";
    } else {

        $selected_datetime = DateTime::createFromFormat(
            "Y-m-d H:i:s",
            $booking_date . " " . $time_slot
        );

        $current_datetime = new DateTime();

        if (!$selected_datetime) {
            $error = "Invalid booking date or time.";
        } elseif ($selected_datetime <= $current_datetime) {
            $error = "You cannot book a session in the past.";
        } else {

            $check_sql = "SELECT id FROM bookings 
                          WHERE coach_id = ? 
                          AND booking_date = ? 
                          AND time_slot = ?
                          AND status != 'cancelled'
                          LIMIT 1";

            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("iss", $coach_id, $booking_date, $time_slot);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();

            if ($check_result->num_rows > 0) {
                $error = "This time slot is already booked. Please choose another one.";
            } else {

                $insert_sql = "INSERT INTO bookings (user_id, coach_id, booking_date, time_slot)
                               VALUES (?, ?, ?, ?)";

                $insert_stmt = $conn->prepare($insert_sql);
                $insert_stmt->bind_param("iiss", $user_id, $coach_id, $booking_date, $time_slot);

                if ($insert_stmt->execute()) {
                    $success = "Booking request submitted successfully!";
                } else {
                    $error = "Failed to create booking.";
                }
            }
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $is_own_coach_profile) {
    $error = "You cannot book a session with yourself.";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Book Coach — Hustle Muscle</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="book_coach.css?v=2">
    <script src="https://kit.fontawesome.com/f990be7b56.js" crossorigin="anonymous"></script>
</head>

<body>

<header class="site-header" id="header">
    <a href="index.php" class="logo-link">
        <i class="fa-solid fa-x x-icon"></i>Hustle Muscle<sup>©</sup>
    </a>

    <nav class="site-nav">
        <a href="index.php">Home</a>
        <a href="index.php#plans">Packages</a>
        <a href="machines.html">Machines</a>
        <a href="coaches.php">Coaches</a>
        <a href="classes.php">Classes</a>
        <span class="nav-divider">|</span>
        <a href="dashboard.php">Dashboard</a>
        <a href="logout.php" class="nav-cta">Logout</a>
    </nav>
</header>

<section class="booking-hero">
    <div class="booking-hero-bg" id="heroBg"></div>

    <div class="booking-hero-content">
        <p class="booking-eyebrow">Private Coach Booking</p>

        <h1 class="booking-title">
            <span class="line"><span>BOOK YOUR</span></span>
            <span class="line"><span>SESSION</span></span>
        </h1>

        <p class="booking-sub">
            Choose a valid future date and time slot. Your booking request will appear in your dashboard and admin panel.
        </p>

        <a href="#bookingForm" class="hero-btn">
            <span>START BOOKING</span>
            <span>→</span>
        </a>
    </div>
</section>

<div class="marquee-strip" aria-hidden="true">
    <div class="marquee-track">
        <span>PRIVATE COACH</span>
        <span>BOOK SESSION</span>
        <span>TRAIN HARDER</span>
        <span>NO EXCUSES</span>
        <span>BUILD STRENGTH</span>
        <span>HUSTLE MUSCLE</span>
        <span>PRIVATE COACH</span>
        <span>BOOK SESSION</span>
        <span>TRAIN HARDER</span>
        <span>NO EXCUSES</span>
        <span>BUILD STRENGTH</span>
        <span>HUSTLE MUSCLE</span>
    </div>
</div>

<main class="booking-content">

    <section class="booking-panel reveal" id="bookingForm">

        <div class="coach-side">

            <div class="coach-icon">
                <i class="fa-solid fa-user-ninja"></i>
            </div>

            <span class="section-tag">Selected Coach</span>

            <h2 class="coach-name">
                <?php echo htmlspecialchars($coach["name"]); ?>
            </h2>

            <p class="coach-specialty">
                <?php echo htmlspecialchars($coach["specialty"]); ?>
            </p>

            <div class="coach-info-list">

                <div class="coach-info-item">
                    <span>Bio</span>
                    <p><?php echo htmlspecialchars($coach["bio"]); ?></p>
                </div>

                <div class="coach-info-item">
                    <span>Achievements</span>
                    <p><?php echo htmlspecialchars($coach["achievements"]); ?></p>
                </div>

                <div class="coach-info-item">
                    <span>Price</span>
                    <p class="price-text">
                        <?php echo htmlspecialchars($coach["price_per_session"]); ?> EGP / Session
                    </p>
                </div>

            </div>

        </div>

        <div class="form-side">

            <div class="panel-header">
                <span class="section-tag">Booking Form</span>
                <h2 class="section-title-small">
                    <?php echo $is_own_coach_profile ? "BOOKING BLOCKED" : "CHOOSE DATE & TIME"; ?>
                </h2>
            </div>

            <?php if ($is_own_coach_profile): ?>

                <div class="message error">
                    You cannot book a private session with yourself because this coach profile is linked to your account.
                </div>

                <div class="booking-links">
                    <a href="coaches.php">← Back to Coaches</a>
                    <a href="dashboard.php">View Dashboard →</a>
                </div>

            <?php else: ?>

                <?php if (!empty($error)): ?>
                    <div class="message error">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="message success">
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <form action="book_coach.php?coach_id=<?php echo $coach_id; ?>" method="post">

                    <div class="field">
                        <label for="booking_date">Session Date</label>
                        <input type="date" id="booking_date" name="booking_date" required>
                    </div>

                    <div class="field">
                        <label for="time_slot">Time Slot</label>
                        <select id="time_slot" name="time_slot" required>
                            <option value="">Choose time</option>
                            <option value="09:00:00">09:00 AM</option>
                            <option value="11:00:00">11:00 AM</option>
                            <option value="13:00:00">01:00 PM</option>
                            <option value="15:00:00">03:00 PM</option>
                            <option value="17:00:00">05:00 PM</option>
                            <option value="19:00:00">07:00 PM</option>
                        </select>
                    </div>

                    <button type="submit" class="main-btn">
                        <span>Confirm Booking</span>
                    </button>

                </form>

                <div class="booking-links">
                    <a href="coaches.php">← Back to Coaches</a>
                    <a href="dashboard.php">View Dashboard →</a>
                </div>

            <?php endif; ?>

        </div>

    </section>

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

<script src="book_coach.js?v=2"></script>
</body>

</html>