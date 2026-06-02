<?php
session_start();
require_once "db.php";

date_default_timezone_set("Africa/Cairo");

$is_logged_in = isset($_SESSION["user_id"]);
$current_user_id = $is_logged_in ? intval($_SESSION["user_id"]) : 0;
$current_role = $_SESSION["role"] ?? "user";

$error = "";
$success = "";

/*
    Handle class enrollment
*/
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["enroll_class"])) {

    if (!$is_logged_in) {
        header("Location: login.php");
        exit();
    }

    $class_id = intval($_POST["class_id"] ?? 0);

    if ($class_id <= 0) {
        $error = "Invalid class selected.";
    } else {

        $class_sql = "
            SELECT
                classes.id,
                classes.name,
                classes.schedule,
                classes.capacity,
                classes.enrolled,
                classes.coach_id,
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

            $class_time = strtotime($class["schedule"]);
            $class_started = $class_time <= time();

            $is_own_class = !empty($class["coach_user_id"]) && intval($class["coach_user_id"]) === $current_user_id;

            if ($class_started) {
                $error = "You cannot enroll in a class that already started.";
            } elseif ($is_own_class) {
                $error = "You cannot enroll in your own class.";
            } elseif (intval($class["enrolled"]) >= intval($class["capacity"])) {
                $error = "This class is already full.";
            } else {

                $check_sql = "SELECT id FROM enrollments WHERE user_id = ? AND class_id = ? LIMIT 1";
                $check_stmt = $conn->prepare($check_sql);
                $check_stmt->bind_param("ii", $current_user_id, $class_id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();

                if ($check_result->num_rows > 0) {
                    $error = "You are already enrolled in this class.";
                } else {

                    $conn->begin_transaction();

                    try {
                        $insert_sql = "INSERT INTO enrollments (user_id, class_id) VALUES (?, ?)";
                        $insert_stmt = $conn->prepare($insert_sql);
                        $insert_stmt->bind_param("ii", $current_user_id, $class_id);
                        $insert_stmt->execute();

                        $update_sql = "UPDATE classes SET enrolled = enrolled + 1 WHERE id = ?";
                        $update_stmt = $conn->prepare($update_sql);
                        $update_stmt->bind_param("i", $class_id);
                        $update_stmt->execute();

                        $conn->commit();

                        header("Location: classes.php?enrolled=1");
                        exit();

                    } catch (Exception $e) {
                        $conn->rollback();
                        $error = "Failed to enroll in class.";
                    }
                }
            }
        }
    }
}

/*
    Success messages
*/
if (isset($_GET["enrolled"])) {
    $success = "You enrolled in the class successfully.";
}

if (isset($_GET["cancelled"])) {
    $success = "Class enrollment cancelled successfully.";
}

/*
    Get enrolled class IDs for current user
*/
$enrolled_classes = [];

if ($is_logged_in) {
    $enrolled_sql = "SELECT class_id FROM enrollments WHERE user_id = ?";
    $enrolled_stmt = $conn->prepare($enrolled_sql);
    $enrolled_stmt->bind_param("i", $current_user_id);
    $enrolled_stmt->execute();
    $enrolled_result = $enrolled_stmt->get_result();

    while ($row = $enrolled_result->fetch_assoc()) {
        $enrolled_classes[] = intval($row["class_id"]);
    }
}

/*
    Get classes with linked coach data
*/
$classes_sql = "
    SELECT
        classes.id,
        classes.name,
        classes.category,
        classes.instructor,
        classes.description,
        classes.schedule,
        classes.duration_min,
        classes.capacity,
        classes.enrolled,
        classes.image,
        classes.coach_id,
        coaches.name AS linked_coach_name,
        coaches.specialty AS linked_coach_specialty,
        coaches.user_id AS linked_coach_user_id
    FROM classes
    LEFT JOIN coaches ON classes.coach_id = coaches.id
    ORDER BY classes.schedule ASC
";

$classes_result = $conn->query($classes_sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Classes — Hustle Muscle</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="classes.css?v=3">
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

        <?php if ($is_logged_in): ?>
            <a href="dashboard.php">Dashboard</a>
            <a href="logout.php" class="nav-cta">Logout</a>
        <?php else: ?>
            <a href="signup.php">Sign Up</a>
            <a href="login.php" class="nav-cta">Log In</a>
        <?php endif; ?>
    </nav>
</header>

<!-- ── HERO ── -->
<section class="classes-hero">
    <div class="classes-hero-bg" id="heroBg"></div>
    <div class="classes-hero-overlay"></div>

    <div class="classes-hero-content">
        <p class="classes-eyebrow">Group Training — Energy, Discipline, Results</p>

        <h1 class="classes-title">
            <span class="line"><span>GYM</span></span>
            <span class="line"><span>CLASSES</span></span>
        </h1>

        <p class="classes-sub">
            Join boxing, Zumba, CrossFit, HIIT, yoga, and kickboxing classes taught by linked Hustle Muscle coaches.
        </p>

        <a href="#classesList" class="hero-btn">
            <span>VIEW CLASSES</span>
            <span>→</span>
        </a>
    </div>

    <div class="hero-scroll-hint">
        <div class="scroll-line"></div>
        SCROLL
    </div>
</section>

<!-- ── MARQUEE ── -->
<div class="marquee-strip" aria-hidden="true">
    <div class="marquee-track">
        <span>BOXING</span>
        <span>ZUMBA</span>
        <span>CROSSFIT</span>
        <span>HIIT</span>
        <span>YOGA</span>
        <span>KICKBOXING</span>
        <span>GROUP TRAINING</span>
        <span>BOXING</span>
        <span>ZUMBA</span>
        <span>CROSSFIT</span>
        <span>HIIT</span>
        <span>YOGA</span>
        <span>KICKBOXING</span>
        <span>GROUP TRAINING</span>
    </div>
</div>

<main class="classes-section" id="classesList">

    <?php if (!empty($success)): ?>
        <div class="message success reveal">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="message error reveal">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="section-header">
        <span class="section-tag reveal">Class Schedule</span>
        <h2 class="section-title reveal reveal-delay-1">CHOOSE YOUR CLASS</h2>
    </div>

    <div class="filter-panel reveal reveal-delay-2">

        <div class="class-search-wrap">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="classSearch" placeholder="Search by class, coach, category...">
        </div>

        <div class="category-filter">
            <button type="button" class="filter-btn active" data-category="all">All</button>
            <button type="button" class="filter-btn" data-category="Boxing">Boxing</button>
            <button type="button" class="filter-btn" data-category="Zumba">Zumba</button>
            <button type="button" class="filter-btn" data-category="CrossFit">CrossFit</button>
            <button type="button" class="filter-btn" data-category="HIIT">HIIT</button>
            <button type="button" class="filter-btn" data-category="Yoga">Yoga</button>
            <button type="button" class="filter-btn" data-category="Kickboxing">Kickboxing</button>
        </div>

    </div>

    <div class="classes-grid" id="classesGrid">

        <?php if ($classes_result && $classes_result->num_rows > 0): ?>
            <?php
                $delay_classes = ["", "reveal-delay-1", "reveal-delay-2", "reveal-delay-3"];
                $counter = 0;
            ?>

            <?php while ($class = $classes_result->fetch_assoc()): ?>
                <?php
                    $delay_class = $delay_classes[$counter % count($delay_classes)];
                    $counter++;

                    $class_id = intval($class["id"]);
                    $is_enrolled = in_array($class_id, $enrolled_classes);

                    $class_time = strtotime($class["schedule"]);
                    $class_started = $class_time <= time();

                    $spots_left = intval($class["capacity"]) - intval($class["enrolled"]);
                    $is_full = $spots_left <= 0;

                    $has_linked_coach = !empty($class["coach_id"]) && !empty($class["linked_coach_user_id"]);

                    $is_own_class = $is_logged_in
                        && !empty($class["linked_coach_user_id"])
                        && intval($class["linked_coach_user_id"]) === $current_user_id;

                    $search_text = strtolower(
                        $class["name"] . " " .
                        $class["category"] . " " .
                        $class["instructor"] . " " .
                        $class["description"] . " " .
                        $class["linked_coach_name"] . " " .
                        $class["linked_coach_specialty"]
                    );
                ?>

                <article class="class-card reveal <?php echo $delay_class; ?>"
                         data-search="<?php echo htmlspecialchars($search_text); ?>"
                         data-category="<?php echo htmlspecialchars($class["category"]); ?>">

                    <div class="class-icon">
                        <?php if ($class["category"] === "Boxing"): ?>
                            <i class="fa-solid fa-hand-fist"></i>
                        <?php elseif ($class["category"] === "Zumba"): ?>
                            <i class="fa-solid fa-music"></i>
                        <?php elseif ($class["category"] === "CrossFit"): ?>
                            <i class="fa-solid fa-dumbbell"></i>
                        <?php elseif ($class["category"] === "HIIT"): ?>
                            <i class="fa-solid fa-fire"></i>
                        <?php elseif ($class["category"] === "Yoga"): ?>
                            <i class="fa-solid fa-spa"></i>
                        <?php elseif ($class["category"] === "Kickboxing"): ?>
                            <i class="fa-solid fa-bolt"></i>
                        <?php else: ?>
                            <i class="fa-solid fa-person-running"></i>
                        <?php endif; ?>
                    </div>

                    <span class="category-badge">
                        <?php echo htmlspecialchars($class["category"]); ?>
                    </span>

                    <h3 class="class-name">
                        <?php echo htmlspecialchars($class["name"]); ?>
                    </h3>

                    <p class="class-description">
                        <?php echo htmlspecialchars($class["description"]); ?>
                    </p>

                    <div class="class-info-list">

                        <div class="class-info-item">
                            <span>Instructor</span>
                            <strong>
                                <?php echo htmlspecialchars($class["linked_coach_name"] ?: $class["instructor"]); ?>
                            </strong>

                            <?php if (!empty($class["linked_coach_specialty"])): ?>
                                <small><?php echo htmlspecialchars($class["linked_coach_specialty"]); ?></small>
                            <?php endif; ?>
                        </div>

                        <div class="class-info-item">
                            <span>Date</span>
                            <strong><?php echo htmlspecialchars(date("D, M j, Y", $class_time)); ?></strong>
                        </div>

                        <div class="class-info-item">
                            <span>Time</span>
                            <strong><?php echo htmlspecialchars(date("h:i A", $class_time)); ?></strong>
                        </div>

                        <div class="class-info-item">
                            <span>Duration</span>
                            <strong><?php echo htmlspecialchars($class["duration_min"]); ?> min</strong>
                        </div>

                        <div class="class-info-item">
                            <span>Spots</span>
                            <strong>
                                <?php echo max(0, $spots_left); ?>
                                /
                                <?php echo htmlspecialchars($class["capacity"]); ?>
                                left
                            </strong>
                        </div>

                    </div>

                    <div class="class-actions">

                        <?php if (!$is_logged_in): ?>

                            <a class="class-cta" href="login.php">
                                <span>LOG IN TO ENROLL</span>
                            </a>

                        <?php elseif ($is_own_class): ?>

                            <button class="class-cta disabled" type="button" disabled>
                                <span>YOUR CLASS</span>
                            </button>

                        <?php elseif ($is_enrolled): ?>

                            <button class="class-cta enrolled" type="button" disabled>
                                <span>ENROLLED</span>
                            </button>

                            <?php if ($has_linked_coach): ?>
                                <a class="class-cta chat" href="chat.php?class_id=<?php echo $class_id; ?>">
                                    <span>CHAT WITH INSTRUCTOR</span>
                                </a>
                            <?php endif; ?>

                        <?php elseif ($class_started): ?>

                            <button class="class-cta disabled" type="button" disabled>
                                <span>CLASS STARTED</span>
                            </button>

                        <?php elseif ($is_full): ?>

                            <button class="class-cta disabled" type="button" disabled>
                                <span>CLASS FULL</span>
                            </button>

                        <?php else: ?>

                            <form action="classes.php" method="post">
                                <input type="hidden" name="class_id" value="<?php echo $class_id; ?>">
                                <button class="class-cta" type="submit" name="enroll_class">
                                    <span>ENROLL NOW</span>
                                </button>
                            </form>

                        <?php endif; ?>

                    </div>

                </article>

            <?php endwhile; ?>

        <?php else: ?>

            <div class="empty-state reveal">
                <i class="fa-solid fa-calendar-xmark"></i>
                <h3>No Classes Available</h3>
                <p>There are no classes available right now. Please check again later.</p>
            </div>

        <?php endif; ?>

    </div>

    <div class="no-results" id="noResults">
        <i class="fa-solid fa-magnifying-glass"></i>
        <h3>No Matching Classes</h3>
        <p>Try another class name, category, coach, or training goal.</p>
    </div>

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

<script src="classes.js?v=3"></script>
</body>
</html>