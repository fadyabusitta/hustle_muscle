<?php
session_start();
require_once "db.php";

date_default_timezone_set("Africa/Cairo");

/*
    Admin protection:
    Only logged-in users with role = admin can access this page.
*/
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: dashboard.php");
    exit();
}

function formatPlan($plan) {
    switch ($plan) {
        case "1month":
            return "1 Month";
        case "3months":
            return "3 Months";
        case "6months":
            return "6 Months";
        case "1year":
            return "1 Year";
        default:
            return "No Plan";
    }
}

/*
    Statistics
*/
$total_users = $conn->query("SELECT COUNT(*) AS total FROM users")->fetch_assoc()["total"];

$total_bookings = $conn->query("SELECT COUNT(*) AS total FROM bookings")->fetch_assoc()["total"];

$total_enrollments = $conn->query("SELECT COUNT(*) AS total FROM enrollments")->fetch_assoc()["total"];

$active_memberships = $conn->query("
    SELECT COUNT(*) AS total 
    FROM users 
    WHERE plan IS NOT NULL AND plan != ''
")->fetch_assoc()["total"];

$total_coaches = $conn->query("SELECT COUNT(*) AS total FROM coaches")->fetch_assoc()["total"];

$total_classes = $conn->query("SELECT COUNT(*) AS total FROM classes")->fetch_assoc()["total"];

/*
    Recent users
*/
$users_sql = "
    SELECT id, full_name, email, username, plan, role, created_at
    FROM users
    ORDER BY created_at DESC
    LIMIT 10
";
$users_result = $conn->query($users_sql);

/*
    Coach bookings with user and coach details
*/
$bookings_sql = "
    SELECT 
        bookings.id,
        bookings.booking_date,
        bookings.time_slot,
        bookings.status,
        bookings.created_at,
        users.full_name AS user_name,
        users.email AS user_email,
        coaches.name AS coach_name,
        coaches.specialty,
        coaches.price_per_session
    FROM bookings
    INNER JOIN users ON bookings.user_id = users.id
    INNER JOIN coaches ON bookings.coach_id = coaches.id
    ORDER BY bookings.created_at DESC
    LIMIT 15
";
$bookings_result = $conn->query($bookings_sql);

/*
    Class enrollments with user and class details
*/
$enrollments_sql = "
    SELECT
        enrollments.id,
        enrollments.enrolled_at,
        users.full_name AS user_name,
        users.email AS user_email,
        classes.name AS class_name,
        classes.category,
        classes.instructor,
        classes.schedule
    FROM enrollments
    INNER JOIN users ON enrollments.user_id = users.id
    INNER JOIN classes ON enrollments.class_id = classes.id
    ORDER BY enrollments.enrolled_at DESC
    LIMIT 15
";
$enrollments_result = $conn->query($enrollments_sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — Hustle Muscle</title>

    <link rel="stylesheet" href="admin_dashboard.css?v=1">
    <script src="https://kit.fontawesome.com/f990be7b56.js" crossorigin="anonymous"></script>
</head>

<body>

    <!-- ── HEADER ── -->
    <header class="site-header" id="header">
        <a href="index.php" class="logo-link">
            <i class="fa-solid fa-x x-icon"></i>Hustle Muscle<sup>©</sup>
        </a>

        <nav class="site-nav">
            <a href="index.php">Home</a>
            <a href="dashboard.php">User Dashboard</a>
            <a href="coaches.php">Coaches</a>
            <a href="classes.php">Classes</a>
            <a href="admin_manage_coaches.php">Manage Coaches</a>
            <a href="admin_manage_classes.php">Manage Classes</a>

            <span class="nav-divider">|</span>

            <a href="logout.php" class="nav-cta">Logout</a>
        </nav>
    </header>

    <!-- ── HERO ── -->
    <section class="admin-hero">
        <div class="admin-hero-bg" id="heroBg"></div>

        <div class="admin-hero-content">
            <p class="admin-eyebrow">Management Control Center</p>

            <h1 class="admin-title">
                <span class="line"><span>ADMIN</span></span>
                <span class="line"><span>DASHBOARD</span></span>
            </h1>

            <p class="admin-sub">
                Monitor users, memberships, coach bookings, class enrollments, and gym activity from one professional control panel.
            </p>

            <div class="hero-actions">
                <a href="admin_manage_coaches.php" class="hero-btn">
                    <span>MANAGE COACHES</span>
                    <span>→</span>
                </a>

                <a href="admin_manage_classes.php" class="hero-btn ghost">
                    <span>MANAGE CLASSES</span>
                    <span>→</span>
                </a>
            </div>
        </div>
    </section>

    <!-- ── MARQUEE ── -->
    <div class="marquee-strip" aria-hidden="true">
        <div class="marquee-track">
            <span>ADMIN PANEL</span>
            <span>USERS</span>
            <span>BOOKINGS</span>
            <span>CLASSES</span>
            <span>COACHES</span>
            <span>GYM MANAGEMENT</span>
            <span>ADMIN PANEL</span>
            <span>USERS</span>
            <span>BOOKINGS</span>
            <span>CLASSES</span>
            <span>COACHES</span>
            <span>GYM MANAGEMENT</span>
        </div>
    </div>

    <main class="admin-content">

        <!-- ── STATISTICS ── -->
        <section class="stats-grid">

            <article class="stat-card reveal">
                <i class="fa-solid fa-users"></i>
                <h2 class="stat-num" data-target="<?php echo htmlspecialchars($total_users); ?>">
                    <?php echo htmlspecialchars($total_users); ?>
                </h2>
                <p>Total Users</p>
            </article>

            <article class="stat-card reveal reveal-delay-1">
                <i class="fa-solid fa-id-card"></i>
                <h2 class="stat-num" data-target="<?php echo htmlspecialchars($active_memberships); ?>">
                    <?php echo htmlspecialchars($active_memberships); ?>
                </h2>
                <p>Active Memberships</p>
            </article>

            <article class="stat-card reveal reveal-delay-2">
                <i class="fa-solid fa-user-tie"></i>
                <h2 class="stat-num" data-target="<?php echo htmlspecialchars($total_coaches); ?>">
                    <?php echo htmlspecialchars($total_coaches); ?>
                </h2>
                <p>Coaches</p>
            </article>

            <article class="stat-card reveal reveal-delay-3">
                <i class="fa-solid fa-dumbbell"></i>
                <h2 class="stat-num" data-target="<?php echo htmlspecialchars($total_classes); ?>">
                    <?php echo htmlspecialchars($total_classes); ?>
                </h2>
                <p>Classes</p>
            </article>

            <article class="stat-card reveal">
                <i class="fa-solid fa-calendar-check"></i>
                <h2 class="stat-num" data-target="<?php echo htmlspecialchars($total_bookings); ?>">
                    <?php echo htmlspecialchars($total_bookings); ?>
                </h2>
                <p>Coach Bookings</p>
            </article>

            <article class="stat-card reveal reveal-delay-1">
                <i class="fa-solid fa-clipboard-list"></i>
                <h2 class="stat-num" data-target="<?php echo htmlspecialchars($total_enrollments); ?>">
                    <?php echo htmlspecialchars($total_enrollments); ?>
                </h2>
                <p>Class Enrollments</p>
            </article>

        </section>

        <!-- ── QUICK ADMIN ACTIONS ── -->
        <section class="admin-actions-grid">

            <article class="admin-action-card reveal">
                <i class="fa-solid fa-user-plus"></i>
                <h3>Manage Coaches</h3>
                <p>Add, edit, or delete private coaches and update their specialties, prices, and achievements.</p>
                <a href="admin_manage_coaches.php"><span>Open Coaches</span></a>
            </article>

            <article class="admin-action-card reveal reveal-delay-1">
                <i class="fa-solid fa-calendar-plus"></i>
                <h3>Manage Classes</h3>
                <p>Create new classes, edit schedules, adjust capacity, and remove unavailable classes.</p>
                <a href="admin_manage_classes.php"><span>Open Classes</span></a>
            </article>

            <article class="admin-action-card reveal reveal-delay-2">
                <i class="fa-solid fa-gauge-high"></i>
                <h3>User Dashboard</h3>
                <p>Return to the regular member dashboard and view the website from the user side.</p>
                <a href="dashboard.php"><span>User View</span></a>
            </article>

        </section>

        <!-- ── RECENT USERS ── -->
        <section class="admin-section reveal">
            <div class="section-header">
                <span class="section-tag">Users</span>
                <h2 class="section-title-small">RECENT USERS</h2>
            </div>

            <?php if ($users_result && $users_result->num_rows > 0): ?>
                <div class="table-wrap">
                    <table class="admin-table">
                        <tr>
                            <th>ID</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Username</th>
                            <th>Plan</th>
                            <th>Role</th>
                            <th>Created At</th>
                        </tr>

                        <?php while ($user = $users_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user["id"]); ?></td>
                                <td><?php echo htmlspecialchars($user["full_name"]); ?></td>
                                <td><?php echo htmlspecialchars($user["email"]); ?></td>
                                <td><?php echo htmlspecialchars($user["username"]); ?></td>
                                <td><?php echo htmlspecialchars(formatPlan($user["plan"])); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo htmlspecialchars($user["role"]); ?>">
                                        <?php echo htmlspecialchars($user["role"]); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($user["created_at"]); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-box">
                    <i class="fa-solid fa-user-slash"></i>
                    <p>No users found.</p>
                </div>
            <?php endif; ?>
        </section>

        <!-- ── COACH BOOKINGS ── -->
        <section class="admin-section reveal">
            <div class="section-header">
                <span class="section-tag">Bookings</span>
                <h2 class="section-title-small">RECENT COACH BOOKINGS</h2>
            </div>

            <?php if ($bookings_result && $bookings_result->num_rows > 0): ?>
                <div class="table-wrap">
                    <table class="admin-table">
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Email</th>
                            <th>Coach</th>
                            <th>Specialty</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>

                        <?php while ($booking = $bookings_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($booking["id"]); ?></td>
                                <td><?php echo htmlspecialchars($booking["user_name"]); ?></td>
                                <td><?php echo htmlspecialchars($booking["user_email"]); ?></td>
                                <td><?php echo htmlspecialchars($booking["coach_name"]); ?></td>
                                <td><?php echo htmlspecialchars($booking["specialty"]); ?></td>
                                <td><?php echo htmlspecialchars($booking["booking_date"]); ?></td>
                                <td><?php echo htmlspecialchars(date("h:i A", strtotime($booking["time_slot"]))); ?></td>
                                <td><?php echo htmlspecialchars($booking["price_per_session"]); ?> EGP</td>
                                <td>
                                    <span class="badge status-<?php echo htmlspecialchars($booking["status"]); ?>">
                                        <?php echo htmlspecialchars($booking["status"]); ?>
                                    </span>
                                </td>
                                <td class="actions-cell">
                                    <?php if ($booking["status"] !== "confirmed"): ?>
                                        <a class="action-btn confirm-btn"
                                           href="admin_update_booking.php?id=<?php echo $booking["id"]; ?>&status=confirmed">
                                            Confirm
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($booking["status"] !== "cancelled"): ?>
                                        <a class="action-btn cancel-btn js-confirm"
                                           href="admin_update_booking.php?id=<?php echo $booking["id"]; ?>&status=cancelled"
                                           data-confirm="Cancel this booking?">
                                            Cancel
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-box">
                    <i class="fa-solid fa-calendar-xmark"></i>
                    <p>No coach bookings found.</p>
                </div>
            <?php endif; ?>
        </section>

        <!-- ── CLASS ENROLLMENTS ── -->
        <section class="admin-section reveal">
            <div class="section-header">
                <span class="section-tag">Enrollments</span>
                <h2 class="section-title-small">RECENT CLASS ENROLLMENTS</h2>
            </div>

            <?php if ($enrollments_result && $enrollments_result->num_rows > 0): ?>
                <div class="table-wrap">
                    <table class="admin-table">
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Email</th>
                            <th>Class</th>
                            <th>Category</th>
                            <th>Instructor</th>
                            <th>Schedule</th>
                            <th>Enrolled At</th>
                        </tr>

                        <?php while ($enrollment = $enrollments_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($enrollment["id"]); ?></td>
                                <td><?php echo htmlspecialchars($enrollment["user_name"]); ?></td>
                                <td><?php echo htmlspecialchars($enrollment["user_email"]); ?></td>
                                <td><?php echo htmlspecialchars($enrollment["class_name"]); ?></td>
                                <td><?php echo htmlspecialchars($enrollment["category"]); ?></td>
                                <td><?php echo htmlspecialchars($enrollment["instructor"]); ?></td>
                                <td><?php echo htmlspecialchars(date("D, M j, Y - h:i A", strtotime($enrollment["schedule"]))); ?></td>
                                <td><?php echo htmlspecialchars($enrollment["enrolled_at"]); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-box">
                    <i class="fa-solid fa-clipboard-list"></i>
                    <p>No class enrollments found.</p>
                </div>
            <?php endif; ?>
        </section>

    </main>

    <!-- ── FOOTER ── -->
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

    <script src="admin_dashboard.js?v=1"></script>
</body>

</html>