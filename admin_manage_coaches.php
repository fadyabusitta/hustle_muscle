<?php
session_start();
require_once "db.php";

date_default_timezone_set("Africa/Cairo");

/*
    Admin protection
*/
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: dashboard.php");
    exit();
}

$error = "";
$success = "";
$edit_mode = false;
$edit_coach = null;

/*
    Add coach
*/
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_coach"])) {
    $name = trim($_POST["name"] ?? "");
    $specialty = trim($_POST["specialty"] ?? "");
    $bio = trim($_POST["bio"] ?? "");
    $achievements = trim($_POST["achievements"] ?? "");
    $price = trim($_POST["price_per_session"] ?? "");

    if (empty($name) || empty($specialty) || empty($bio) || empty($achievements) || empty($price)) {
        $error = "All fields are required.";
    } elseif (!is_numeric($price) || floatval($price) < 0) {
        $error = "Price must be a valid positive number.";
    } else {
        $price = floatval($price);

        $sql = "INSERT INTO coaches (name, specialty, bio, achievements, image, price_per_session)
                VALUES (?, ?, ?, ?, NULL, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssd", $name, $specialty, $bio, $achievements, $price);

        if ($stmt->execute()) {
            header("Location: admin_manage_coaches.php?added=1");
            exit();
        } else {
            $error = "Failed to add coach.";
        }
    }
}

/*
    Update coach
*/
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update_coach"])) {
    $coach_id = intval($_POST["coach_id"] ?? 0);
    $name = trim($_POST["name"] ?? "");
    $specialty = trim($_POST["specialty"] ?? "");
    $bio = trim($_POST["bio"] ?? "");
    $achievements = trim($_POST["achievements"] ?? "");
    $price = trim($_POST["price_per_session"] ?? "");

    if ($coach_id <= 0) {
        $error = "Invalid coach selected.";
    } elseif (empty($name) || empty($specialty) || empty($bio) || empty($achievements) || empty($price)) {
        $error = "All fields are required.";
    } elseif (!is_numeric($price) || floatval($price) < 0) {
        $error = "Price must be a valid positive number.";
    } else {
        $price = floatval($price);

        $sql = "UPDATE coaches
                SET name = ?, specialty = ?, bio = ?, achievements = ?, price_per_session = ?
                WHERE id = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssdi", $name, $specialty, $bio, $achievements, $price, $coach_id);

        if ($stmt->execute()) {
            header("Location: admin_manage_coaches.php?updated=1");
            exit();
        } else {
            $error = "Failed to update coach.";
        }
    }
}

/*
    Delete coach
*/
if (isset($_GET["delete"])) {
    $coach_id = intval($_GET["delete"]);

    if ($coach_id > 0) {
        $delete_sql = "DELETE FROM coaches WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $coach_id);

        if ($delete_stmt->execute()) {
            header("Location: admin_manage_coaches.php?deleted=1");
            exit();
        } else {
            $error = "Failed to delete coach.";
        }
    }
}

/*
    Load coach data for editing
*/
if (isset($_GET["edit"])) {
    $coach_id = intval($_GET["edit"]);

    if ($coach_id > 0) {
        $edit_sql = "SELECT * FROM coaches WHERE id = ? LIMIT 1";
        $edit_stmt = $conn->prepare($edit_sql);
        $edit_stmt->bind_param("i", $coach_id);
        $edit_stmt->execute();

        $edit_result = $edit_stmt->get_result();

        if ($edit_result->num_rows === 1) {
            $edit_mode = true;
            $edit_coach = $edit_result->fetch_assoc();
        } else {
            $error = "Coach not found.";
        }
    }
}

/*
    Success messages
*/
if (isset($_GET["added"])) {
    $success = "Coach added successfully.";
}

if (isset($_GET["updated"])) {
    $success = "Coach updated successfully.";
}

if (isset($_GET["deleted"])) {
    $success = "Coach deleted successfully.";
}

/*
    Get all coaches
*/
$coaches_sql = "SELECT * FROM coaches ORDER BY id DESC";
$coaches_result = $conn->query($coaches_sql);

$form_name = $edit_coach["name"] ?? "";
$form_specialty = $edit_coach["specialty"] ?? "";
$form_bio = $edit_coach["bio"] ?? "";
$form_achievements = $edit_coach["achievements"] ?? "";
$form_price = $edit_coach["price_per_session"] ?? "";
$form_id = $edit_coach["id"] ?? "";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Coaches — Hustle Muscle</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="admin_manage_coaches.css?v=1">
    <script src="https://kit.fontawesome.com/f990be7b56.js" crossorigin="anonymous"></script>
</head>

<body>

    <!-- ── HEADER ── -->
    <header class="site-header" id="header">
        <a href="index.php" class="logo-link">
            <i class="fa-solid fa-x x-icon"></i>Hustle Muscle<sup>©</sup>
        </a>

        <nav class="site-nav">
            <a href="admin_dashboard.php">Admin Dashboard</a>
            <a href="admin_manage_coaches.php">Manage Coaches</a>
            <a href="admin_manage_classes.php">Manage Classes</a>
            <a href="dashboard.php">User Dashboard</a>

            <span class="nav-divider">|</span>

            <a href="logout.php" class="nav-cta">Logout</a>
        </nav>
    </header>

    <!-- ── HERO ── -->
    <section class="manage-hero">
        <div class="manage-hero-bg" id="heroBg"></div>

        <div class="manage-hero-content">
            <p class="manage-eyebrow">Admin Management</p>

            <h1 class="manage-title">
                <span class="line"><span>MANAGE</span></span>
                <span class="line"><span>COACHES</span></span>
            </h1>

            <p class="manage-sub">
                Add, edit, review, and delete private coaches available on the Hustle Muscle coaching system.
            </p>

            <a href="#coachForm" class="hero-btn">
                <span><?php echo $edit_mode ? "EDIT SELECTED COACH" : "ADD NEW COACH"; ?></span>
                <span>→</span>
            </a>
        </div>
    </section>

    <!-- ── MARQUEE ── -->
    <div class="marquee-strip" aria-hidden="true">
        <div class="marquee-track">
            <span>MANAGE COACHES</span>
            <span>PRIVATE TRAINERS</span>
            <span>STRENGTH</span>
            <span>BOXING</span>
            <span>ADMIN PANEL</span>
            <span>FULL CRUD</span>
            <span>MANAGE COACHES</span>
            <span>PRIVATE TRAINERS</span>
            <span>STRENGTH</span>
            <span>BOXING</span>
            <span>ADMIN PANEL</span>
            <span>FULL CRUD</span>
        </div>
    </div>

    <main class="manage-content">

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

        <!-- ── FORM PANEL ── -->
        <section class="manage-panel reveal" id="coachForm">
            <div class="panel-header">
                <span class="section-tag">
                    <?php echo $edit_mode ? "Update Existing Coach" : "Create New Coach"; ?>
                </span>

                <h2 class="section-title-small">
                    <?php echo $edit_mode ? "EDIT COACH" : "ADD NEW COACH"; ?>
                </h2>
            </div>

            <?php if ($edit_mode): ?>
                <div class="edit-note">
                    <i class="fa-solid fa-pen-to-square"></i>
                    You are editing coach ID #<?php echo htmlspecialchars($form_id); ?>.
                </div>
            <?php endif; ?>

            <form action="admin_manage_coaches.php" method="post">

                <?php if ($edit_mode): ?>
                    <input type="hidden" name="coach_id" value="<?php echo htmlspecialchars($form_id); ?>">
                <?php endif; ?>

                <div class="form-grid">

                    <div class="field">
                        <label for="name">Coach Name</label>
                        <input type="text" id="name" name="name"
                               placeholder="Example: Ahmed Hassan"
                               value="<?php echo htmlspecialchars($form_name); ?>">
                    </div>

                    <div class="field">
                        <label for="specialty">Specialty</label>
                        <input type="text" id="specialty" name="specialty"
                               placeholder="Example: Strength & Conditioning"
                               value="<?php echo htmlspecialchars($form_specialty); ?>">
                    </div>

                    <div class="field full">
                        <label for="bio">Coach Bio</label>
                        <textarea id="bio" name="bio"
                                  placeholder="Write short description about the coach..."><?php echo htmlspecialchars($form_bio); ?></textarea>
                    </div>

                    <div class="field full">
                        <label for="achievements">Achievements</label>
                        <textarea id="achievements" name="achievements"
                                  placeholder="Example: National champion, 7 years coaching experience..."><?php echo htmlspecialchars($form_achievements); ?></textarea>
                    </div>

                    <div class="field">
                        <label for="price_per_session">Price Per Session</label>
                        <input type="number" step="0.01" id="price_per_session" name="price_per_session"
                               placeholder="Example: 350"
                               value="<?php echo htmlspecialchars($form_price); ?>">
                    </div>

                </div>

                <div class="form-actions">
                    <?php if ($edit_mode): ?>
                        <button type="submit" name="update_coach" class="main-btn">
                            <span>Update Coach</span>
                        </button>

                        <a href="admin_manage_coaches.php" class="secondary-btn">
                            <span>Cancel Edit</span>
                        </a>
                    <?php else: ?>
                        <button type="submit" name="add_coach" class="main-btn">
                            <span>Add Coach</span>
                        </button>
                    <?php endif; ?>
                </div>

            </form>
        </section>

        <!-- ── TABLE PANEL ── -->
        <section class="manage-panel table-panel reveal">
            <div class="panel-header">
                <span class="section-tag">Coach Database</span>
                <h2 class="section-title-small">EXISTING COACHES</h2>
            </div>

            <?php if ($coaches_result && $coaches_result->num_rows > 0): ?>

                <div class="table-wrap">
                    <table class="manage-table">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Specialty</th>
                            <th>Price</th>
                            <th>Action</th>
                        </tr>

                        <?php while ($coach = $coaches_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($coach["id"]); ?></td>
                                <td><?php echo htmlspecialchars($coach["name"]); ?></td>
                                <td><?php echo htmlspecialchars($coach["specialty"]); ?></td>
                                <td><?php echo htmlspecialchars($coach["price_per_session"]); ?> EGP</td>
                                <td class="actions-cell">
                                    <a class="action-btn edit-btn"
                                       href="admin_manage_coaches.php?edit=<?php echo $coach["id"]; ?>">
                                        Edit
                                    </a>

                                    <a class="action-btn delete-btn js-confirm"
                                       href="admin_manage_coaches.php?delete=<?php echo $coach["id"]; ?>"
                                       data-confirm="Delete this coach? Related bookings may also be affected.">
                                        Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </table>
                </div>

            <?php else: ?>

                <div class="empty-box">
                    <i class="fa-solid fa-user-slash"></i>
                    <p>No coaches found.</p>
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

    <script src="admin_manage_coaches.js?v=1"></script>
</body>

</html>