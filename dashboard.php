<?php
session_start();
require_once "db.php";

date_default_timezone_set("Africa/Cairo");

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = intval($_SESSION["user_id"]);

$profile_error = "";
$profile_success = "";
$password_error = "";
$password_success = "";

$open_profile_form = false;
$open_password_form = false;

/*
    Get current user data from database
*/
$user_sql = "SELECT id, full_name, email, username, phone, plan, profile_image, role, password
             FROM users
             WHERE id = ?
             LIMIT 1";

$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();

if ($user_result->num_rows !== 1) {
    header("Location: logout.php");
    exit();
}

$user = $user_result->fetch_assoc();

/*
    Handle profile update
*/
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_profile"])) {

    $open_profile_form = true;

    $full_name = trim($_POST["full_name"] ?? "");
    $username = trim($_POST["username"] ?? "");
    $phone = trim($_POST["phone"] ?? "");

    if (empty($full_name) || empty($username)) {
        $profile_error = "Full name and username are required.";
    } elseif (strlen($username) < 3) {
        $profile_error = "Username must be at least 3 characters.";
    } else {

        $profile_image = $user["profile_image"];

        if (isset($_FILES["profile_image"]) && $_FILES["profile_image"]["error"] !== UPLOAD_ERR_NO_FILE) {

            if ($_FILES["profile_image"]["error"] !== UPLOAD_ERR_OK) {
                $profile_error = "Image upload failed.";
            } else {

                $allowed_types = ["image/jpeg", "image/png", "image/webp"];
                $file_type = mime_content_type($_FILES["profile_image"]["tmp_name"]);
                $file_size = $_FILES["profile_image"]["size"];

                if (!in_array($file_type, $allowed_types)) {
                    $profile_error = "Only JPG, PNG, and WEBP images are allowed.";
                } elseif ($file_size > 2 * 1024 * 1024) {
                    $profile_error = "Image must be smaller than 2MB.";
                } else {

                    $extension = strtolower(pathinfo($_FILES["profile_image"]["name"], PATHINFO_EXTENSION));
                    $new_name = "user_" . $user_id . "_" . time() . "." . $extension;

                    $upload_dir = "uploads/profiles/";
                    $upload_path = $upload_dir . $new_name;

                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }

                    if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $upload_path)) {
                        $profile_image = $upload_path;
                    } else {
                        $profile_error = "Failed to save uploaded image.";
                    }
                }
            }
        }

        if (empty($profile_error)) {
            $update_sql = "UPDATE users
                           SET full_name = ?, username = ?, phone = ?, profile_image = ?
                           WHERE id = ?";

            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("ssssi", $full_name, $username, $phone, $profile_image, $user_id);

            if ($update_stmt->execute()) {

                $_SESSION["full_name"] = $full_name;
                $_SESSION["username"] = $username;

                $profile_success = "Profile updated successfully.";

                $user["full_name"] = $full_name;
                $user["username"] = $username;
                $user["phone"] = $phone;
                $user["profile_image"] = $profile_image;

            } else {
                if ($conn->errno == 1062) {
                    $profile_error = "Username already exists.";
                } else {
                    $profile_error = "Failed to update profile.";
                }
            }
        }
    }
}

/*
    Handle password update
*/
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_password"])) {

    $open_password_form = true;

    $old_password = $_POST["old_password"] ?? "";
    $new_password = $_POST["new_password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
        $password_error = "Please fill all password fields.";
    } elseif (!password_verify($old_password, $user["password"])) {
        $password_error = "Old password is incorrect.";
    } elseif (strlen($new_password) < 6) {
        $password_error = "New password must be at least 6 characters.";
    } elseif ($new_password !== $confirm_password) {
        $password_error = "New password and confirmation do not match.";
    } else {

        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        $password_sql = "UPDATE users SET password = ? WHERE id = ?";
        $password_stmt = $conn->prepare($password_sql);
        $password_stmt->bind_param("si", $hashed_password, $user_id);

        if ($password_stmt->execute()) {
            $password_success = "Password changed successfully.";
            $user["password"] = $hashed_password;
        } else {
            $password_error = "Failed to change password.";
        }
    }
}

$full_name = $user["full_name"] ?? "Member";
$email = $user["email"] ?? "";
$username = $user["username"] ?? "";
$phone = $user["phone"] ?? "";
$plan = $user["plan"] ?? "";
$profile_image = $user["profile_image"] ?? "";
$role = $user["role"] ?? "user";

$_SESSION["full_name"] = $full_name;
$_SESSION["username"] = $username;
$_SESSION["plan"] = $plan;
$_SESSION["role"] = $role;

function formatPlan($plan) {
    switch ($plan) {
        case "1month":
            return "1 Month Membership — 600 EGP";
        case "3months":
            return "3 Months Membership — 1,300 EGP";
        case "6months":
            return "6 Months Membership — 2,100 EGP";
        case "1year":
            return "1 Year Membership — 3,500 EGP";
        default:
            return "No membership plan selected";
    }
}

/*
    Get user coach bookings
*/
$booking_sql = "
    SELECT
        bookings.id,
        bookings.booking_date,
        bookings.time_slot,
        bookings.status,
        coaches.name AS coach_name,
        coaches.specialty,
        coaches.price_per_session
    FROM bookings
    INNER JOIN coaches ON bookings.coach_id = coaches.id
    WHERE bookings.user_id = ?
    ORDER BY bookings.booking_date DESC, bookings.time_slot DESC
";

$booking_stmt = $conn->prepare($booking_sql);
$booking_stmt->bind_param("i", $user_id);
$booking_stmt->execute();
$bookings_result = $booking_stmt->get_result();

/*
    Get user class enrollments with linked coach data
*/
$class_sql = "
    SELECT
        enrollments.id AS enrollment_id,
        enrollments.enrolled_at,

        classes.id AS class_id,
        classes.name,
        classes.category,
        classes.instructor,
        classes.schedule,
        classes.duration_min,
        classes.coach_id,

        coaches.name AS linked_coach_name,
        coaches.specialty AS linked_coach_specialty,
        coaches.user_id AS linked_coach_user_id
    FROM enrollments
    INNER JOIN classes ON enrollments.class_id = classes.id
    LEFT JOIN coaches ON classes.coach_id = coaches.id
    WHERE enrollments.user_id = ?
    ORDER BY classes.schedule ASC
";

$class_stmt = $conn->prepare($class_sql);
$class_stmt->bind_param("i", $user_id);
$class_stmt->execute();
$classes_result = $class_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Dashboard — Hustle Muscle</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="dashboard.css?v=4">
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

        <?php if ($role === "coach"): ?>
            <a href="coach_chats.php">Coach Chats</a>
        <?php endif; ?>

        <?php if ($role === "admin"): ?>
            <a href="admin_dashboard.php">Admin</a>
            <a href="coach_chats.php">Coach Chats</a>
        <?php endif; ?>

        <span class="nav-divider">|</span>
        <a href="logout.php" class="nav-cta">Logout</a>
    </nav>
</header>

<section class="dashboard-hero">
    <div class="dashboard-hero-bg" id="heroBg"></div>

    <div class="dashboard-hero-content">
        <p class="dashboard-eyebrow">Member Control Center</p>

        <h1 class="dashboard-title">
            <span class="line"><span>WELCOME BACK</span></span>
            <span class="line"><span><?php echo htmlspecialchars($full_name); ?></span></span>
        </h1>

        <p class="dashboard-sub">
            Manage your profile, password, coach bookings, class enrollments, and instructor chats from one place.
        </p>
    </div>
</section>

<div class="marquee-strip" aria-hidden="true">
    <div class="marquee-track">
        <span>DASHBOARD</span>
        <span>TRAIN HARDER</span>
        <span>BOOK COACHES</span>
        <span>JOIN CLASSES</span>
        <span>INSTRUCTOR CHAT</span>
        <span>COACH CHAT</span>
        <span>NO EXCUSES</span>
        <span>DASHBOARD</span>
        <span>TRAIN HARDER</span>
        <span>BOOK COACHES</span>
        <span>JOIN CLASSES</span>
        <span>INSTRUCTOR CHAT</span>
        <span>COACH CHAT</span>
        <span>NO EXCUSES</span>
    </div>
</div>

<main class="dashboard-content">

    <?php if (!empty($profile_success)): ?>
        <div class="message success reveal"><?php echo htmlspecialchars($profile_success); ?></div>
    <?php endif; ?>

    <?php if (!empty($profile_error)): ?>
        <div class="message error reveal"><?php echo htmlspecialchars($profile_error); ?></div>
    <?php endif; ?>

    <?php if (!empty($password_success)): ?>
        <div class="message success reveal"><?php echo htmlspecialchars($password_success); ?></div>
    <?php endif; ?>

    <?php if (!empty($password_error)): ?>
        <div class="message error reveal"><?php echo htmlspecialchars($password_error); ?></div>
    <?php endif; ?>

    <!-- ACCOUNT SUMMARY -->
    <section class="dashboard-panel summary-panel reveal">
        <div class="panel-header">
            <span class="section-tag">Account</span>
            <h2 class="section-title-small">ACCOUNT SUMMARY</h2>
        </div>

        <div class="summary-layout">

            <div class="profile-preview">
                <?php if (!empty($profile_image)): ?>
                    <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="Profile Image">
                <?php else: ?>
                    <div class="profile-placeholder">
                        <i class="fa-solid fa-user"></i>
                    </div>
                <?php endif; ?>
            </div>

            <div class="summary-details">

                <div class="info-row">
                    <span>Full Name</span>
                    <strong><?php echo htmlspecialchars($full_name); ?></strong>
                </div>

                <div class="info-row">
                    <span>Email</span>
                    <strong><?php echo htmlspecialchars($email); ?></strong>
                </div>

                <div class="info-row">
                    <span>Username</span>
                    <strong><?php echo htmlspecialchars($username); ?></strong>
                </div>

                <div class="info-row">
                    <span>Phone</span>
                    <strong><?php echo !empty($phone) ? htmlspecialchars($phone) : "Not added"; ?></strong>
                </div>

                <div class="info-row">
                    <span>Membership Plan</span>
                    <strong><?php echo htmlspecialchars(formatPlan($plan)); ?></strong>
                </div>

                <div class="info-row">
                    <span>Account Role</span>
                    <strong class="role-badge"><?php echo htmlspecialchars($role); ?></strong>
                </div>

                <div class="info-row">
                    <span>Account Status</span>
                    <strong class="active-status">Active</strong>
                </div>

            </div>
        </div>

        <div class="action-row">
            <button class="toggle-btn" type="button" data-panel="profilePanel">
                <span>Edit Profile</span>
            </button>

            <button class="toggle-btn alt" type="button" data-panel="passwordPanel">
                <span>Change Password</span>
            </button>
        </div>
    </section>

    <!-- EDIT PROFILE PANEL -->
    <section id="profilePanel" class="dashboard-panel form-panel <?php echo $open_profile_form ? 'open' : ''; ?>">
        <div class="panel-header">
            <span class="section-tag">Profile</span>
            <h2 class="section-title-small">EDIT PROFILE</h2>
        </div>

        <form action="dashboard.php" method="post" enctype="multipart/form-data">
            <div class="form-grid">

                <div class="field">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name"
                           value="<?php echo htmlspecialchars($full_name); ?>">
                </div>

                <div class="field">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username"
                           value="<?php echo htmlspecialchars($username); ?>">
                </div>

                <div class="field">
                    <label for="email">Email Address</label>
                    <input type="email" id="email"
                           value="<?php echo htmlspecialchars($email); ?>" readonly>
                </div>

                <div class="field">
                    <label for="phone">Phone Number</label>
                    <input type="text" id="phone" name="phone"
                           placeholder="Optional phone number"
                           value="<?php echo htmlspecialchars($phone); ?>">
                </div>

                <div class="field full">
                    <label for="profile_image">Profile Image</label>
                    <input type="file" id="profile_image" name="profile_image" accept="image/jpeg,image/png,image/webp">
                </div>

            </div>

            <button class="save-btn" type="submit" name="update_profile">
                <span>Save Profile Changes</span>
            </button>
        </form>
    </section>

    <!-- CHANGE PASSWORD PANEL -->
    <section id="passwordPanel" class="dashboard-panel form-panel <?php echo $open_password_form ? 'open' : ''; ?>">
        <div class="panel-header">
            <span class="section-tag">Security</span>
            <h2 class="section-title-small">CHANGE PASSWORD</h2>
        </div>

        <form action="dashboard.php" method="post">
            <div class="form-grid">

                <div class="field full">
                    <label for="old_password">Old Password</label>
                    <input type="password" id="old_password" name="old_password"
                           placeholder="Enter your current password">
                </div>

                <div class="field">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password"
                           placeholder="Enter new password">
                </div>

                <div class="field">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password"
                           placeholder="Confirm new password">
                </div>

            </div>

            <button class="save-btn" type="submit" name="update_password">
                <span>Change Password</span>
            </button>
        </form>
    </section>

    <!-- SHORTCUT CARDS -->
    <section class="shortcut-grid">

        <article class="dashboard-card reveal">
            <i class="fa-solid fa-dumbbell"></i>
            <h3>Membership</h3>
            <p>View available gym packages and upgrade your membership plan.</p>
            <a href="index.php#plans"><span>View Plans</span></a>
        </article>

        <article class="dashboard-card reveal reveal-delay-1">
            <i class="fa-solid fa-user-tie"></i>
            <h3>Private Coach</h3>
            <p>Book a personal trainer for strength, boxing, fat loss, or conditioning.</p>
            <a href="coaches.php"><span>Book Coach</span></a>
        </article>

        <article class="dashboard-card reveal reveal-delay-2">
            <i class="fa-solid fa-calendar-days"></i>
            <h3>Classes</h3>
            <p>Join boxing, Zumba, CrossFit, HIIT, yoga, and other gym classes.</p>
            <a href="classes.php"><span>View Classes</span></a>
        </article>

        <?php if ($role === "coach"): ?>
            <article class="dashboard-card reveal reveal-delay-3">
                <i class="fa-solid fa-comments"></i>
                <h3>Coach Chats</h3>
                <p>Open conversations with members who booked sessions or enrolled in your classes.</p>
                <a href="coach_chats.php"><span>Open Chats</span></a>
            </article>
        <?php endif; ?>

        <?php if ($role === "admin"): ?>
            <article class="dashboard-card admin-card reveal reveal-delay-3">
                <i class="fa-solid fa-user-shield"></i>
                <h3>Admin Panel</h3>
                <p>Manage users, bookings, enrollments, coaches, classes, and chats.</p>
                <a href="admin_dashboard.php"><span>Open Admin</span></a>
            </article>

            <article class="dashboard-card reveal">
                <i class="fa-solid fa-comments"></i>
                <h3>Coach Chats</h3>
                <p>Open all active coach-member conversations.</p>
                <a href="coach_chats.php"><span>Open Chats</span></a>
            </article>
        <?php endif; ?>

    </section>

    <!-- COACH BOOKINGS -->
    <section class="dashboard-panel data-section reveal">
        <div class="panel-header">
            <span class="section-tag">Bookings</span>
            <h2 class="section-title-small">MY COACH BOOKINGS</h2>
        </div>

        <?php if ($bookings_result && $bookings_result->num_rows > 0): ?>

            <div class="table-wrap">
                <table class="data-table">
                    <tr>
                        <th>Coach</th>
                        <th>Specialty</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Chat</th>
                        <th>Action</th>
                    </tr>

                    <?php while ($booking = $bookings_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($booking["coach_name"]); ?></td>
                            <td><?php echo htmlspecialchars($booking["specialty"]); ?></td>
                            <td><?php echo htmlspecialchars($booking["booking_date"]); ?></td>
                            <td><?php echo htmlspecialchars(date("h:i A", strtotime($booking["time_slot"]))); ?></td>
                            <td><?php echo htmlspecialchars($booking["price_per_session"]); ?> EGP</td>
                            <td>
                                <span class="status status-<?php echo htmlspecialchars($booking["status"]); ?>">
                                    <?php echo htmlspecialchars($booking["status"]); ?>
                                </span>
                            </td>

                            <td>
                                <?php if ($booking["status"] !== "cancelled"): ?>
                                    <a class="chat-action" href="chat.php?booking_id=<?php echo $booking["id"]; ?>">
                                        Chat
                                    </a>
                                <?php else: ?>
                                    <span class="muted-text">—</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if ($booking["status"] !== "cancelled"): ?>
                                    <a class="danger-action js-confirm"
                                       href="cancel_booking.php?id=<?php echo $booking["id"]; ?>"
                                       data-confirm="Are you sure you want to cancel this booking?">
                                        Cancel
                                    </a>
                                <?php else: ?>
                                    <span class="muted-text">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </table>
            </div>

        <?php else: ?>

            <div class="empty-box">
                <i class="fa-solid fa-calendar-xmark"></i>
                <p>You do not have any coach bookings yet.</p>
                <a href="coaches.php">Book your first coach session</a>
            </div>

        <?php endif; ?>
    </section>

    <!-- CLASS ENROLLMENTS -->
    <section class="dashboard-panel data-section reveal">
        <div class="panel-header">
            <span class="section-tag">Classes</span>
            <h2 class="section-title-small">MY CLASS ENROLLMENTS</h2>
        </div>

        <?php if ($classes_result && $classes_result->num_rows > 0): ?>

            <div class="table-wrap">
                <table class="data-table">
                    <tr>
                        <th>Class</th>
                        <th>Category</th>
                        <th>Instructor</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Duration</th>
                        <th>Chat</th>
                        <th>Action</th>
                    </tr>

                    <?php while ($class = $classes_result->fetch_assoc()): ?>
                        <?php
                            $class_time = strtotime($class["schedule"]);
                            $class_started = $class_time <= time();

                            $has_linked_coach = !empty($class["coach_id"]) && !empty($class["linked_coach_user_id"]);

                            $is_own_class = $has_linked_coach && intval($class["linked_coach_user_id"]) === $user_id;

                            $display_instructor = !empty($class["linked_coach_name"])
                                ? $class["linked_coach_name"]
                                : $class["instructor"];
                        ?>

                        <tr>
                            <td><?php echo htmlspecialchars($class["name"]); ?></td>
                            <td><?php echo htmlspecialchars($class["category"]); ?></td>
                            <td>
                                <?php echo htmlspecialchars($display_instructor); ?>

                                <?php if (!empty($class["linked_coach_specialty"])): ?>
                                    <br>
                                    <span class="muted-text">
                                        <?php echo htmlspecialchars($class["linked_coach_specialty"]); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars(date("D, M j, Y", $class_time)); ?></td>
                            <td><?php echo htmlspecialchars(date("h:i A", $class_time)); ?></td>
                            <td><?php echo htmlspecialchars($class["duration_min"]); ?> min</td>

                            <td>
                                <?php if ($has_linked_coach && !$is_own_class): ?>
                                    <a class="chat-action" href="chat.php?class_id=<?php echo $class["class_id"]; ?>">
                                        Chat
                                    </a>
                                <?php elseif ($is_own_class): ?>
                                    <span class="muted-text">Your class</span>
                                <?php else: ?>
                                    <span class="muted-text">No coach</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if (!$class_started): ?>
                                    <a class="danger-action js-confirm"
                                       href="cancel_enrollment.php?id=<?php echo $class["enrollment_id"]; ?>"
                                       data-confirm="Are you sure you want to cancel this class enrollment?">
                                        Cancel
                                    </a>
                                <?php else: ?>
                                    <span class="muted-text">Started</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </table>
            </div>

        <?php else: ?>

            <div class="empty-box">
                <i class="fa-solid fa-clipboard-list"></i>
                <p>You are not enrolled in any classes yet.</p>
                <a href="classes.php">Enroll in your first class</a>
            </div>

        <?php endif; ?>
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

<script src="dashboard.js?v=4"></script>
</body>

</html>