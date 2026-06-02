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
$edit_class = null;

/*
    Get coaches for instructor dropdown.
    The class must now be linked to an actual coach profile.
*/
$coaches_sql = "
    SELECT id, name, specialty, user_id
    FROM coaches
    ORDER BY name ASC
";
$coaches_result_for_form = $conn->query($coaches_sql);

/*
    Helper function to get selected coach data.
*/
function getCoachById($conn, $coach_id) {
    $sql = "SELECT id, name, specialty FROM coaches WHERE id = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $coach_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows !== 1) {
        return null;
    }

    return $result->fetch_assoc();
}

/*
    Add class
*/
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_class"])) {
    $name = trim($_POST["name"] ?? "");
    $category = trim($_POST["category"] ?? "");
    $coach_id = intval($_POST["coach_id"] ?? 0);
    $description = trim($_POST["description"] ?? "");
    $schedule_input = trim($_POST["schedule"] ?? "");
    $duration = intval($_POST["duration_min"] ?? 0);
    $capacity = intval($_POST["capacity"] ?? 0);

    if (empty($name) || empty($category) || empty($description) || empty($schedule_input)) {
        $error = "All fields are required.";
    } elseif ($coach_id <= 0) {
        $error = "Please choose a coach for this class.";
    } elseif ($duration <= 0) {
        $error = "Duration must be greater than zero.";
    } elseif ($capacity <= 0) {
        $error = "Capacity must be greater than zero.";
    } else {
        $selected_coach = getCoachById($conn, $coach_id);

        if (!$selected_coach) {
            $error = "Selected coach does not exist.";
        } else {
            $instructor = $selected_coach["name"];
            $schedule_object = DateTime::createFromFormat("Y-m-d\TH:i", $schedule_input);

            if (!$schedule_object) {
                $error = "Invalid schedule format.";
            } elseif ($schedule_object <= new DateTime()) {
                $error = "Class schedule must be in the future.";
            } else {
                $schedule = $schedule_object->format("Y-m-d H:i:s");

                $sql = "INSERT INTO classes 
                        (name, category, instructor, coach_id, description, schedule, duration_min, capacity, enrolled, image)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, NULL)";

                $stmt = $conn->prepare($sql);
                $stmt->bind_param(
                    "sssissii",
                    $name,
                    $category,
                    $instructor,
                    $coach_id,
                    $description,
                    $schedule,
                    $duration,
                    $capacity
                );

                if ($stmt->execute()) {
                    header("Location: admin_manage_classes.php?added=1");
                    exit();
                } else {
                    $error = "Failed to add class.";
                }
            }
        }
    }
}

/*
    Update class
*/
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update_class"])) {
    $class_id = intval($_POST["class_id"] ?? 0);
    $name = trim($_POST["name"] ?? "");
    $category = trim($_POST["category"] ?? "");
    $coach_id = intval($_POST["coach_id"] ?? 0);
    $description = trim($_POST["description"] ?? "");
    $schedule_input = trim($_POST["schedule"] ?? "");
    $duration = intval($_POST["duration_min"] ?? 0);
    $capacity = intval($_POST["capacity"] ?? 0);

    if ($class_id <= 0) {
        $error = "Invalid class selected.";
    } elseif (empty($name) || empty($category) || empty($description) || empty($schedule_input)) {
        $error = "All fields are required.";
    } elseif ($coach_id <= 0) {
        $error = "Please choose a coach for this class.";
    } elseif ($duration <= 0) {
        $error = "Duration must be greater than zero.";
    } elseif ($capacity <= 0) {
        $error = "Capacity must be greater than zero.";
    } else {

        /*
            Check enrolled count before lowering capacity.
        */
        $current_sql = "SELECT enrolled FROM classes WHERE id = ? LIMIT 1";
        $current_stmt = $conn->prepare($current_sql);
        $current_stmt->bind_param("i", $class_id);
        $current_stmt->execute();
        $current_result = $current_stmt->get_result();

        if ($current_result->num_rows !== 1) {
            $error = "Class not found.";
        } else {
            $current_class = $current_result->fetch_assoc();
            $current_enrolled = intval($current_class["enrolled"]);

            if ($capacity < $current_enrolled) {
                $error = "Capacity cannot be less than the current enrolled count.";
            } else {
                $selected_coach = getCoachById($conn, $coach_id);

                if (!$selected_coach) {
                    $error = "Selected coach does not exist.";
                } else {
                    $instructor = $selected_coach["name"];
                    $schedule_object = DateTime::createFromFormat("Y-m-d\TH:i", $schedule_input);

                    if (!$schedule_object) {
                        $error = "Invalid schedule format.";
                    } elseif ($schedule_object <= new DateTime()) {
                        $error = "Class schedule must be in the future.";
                    } else {
                        $schedule = $schedule_object->format("Y-m-d H:i:s");

                        $sql = "UPDATE classes
                                SET name = ?, 
                                    category = ?, 
                                    instructor = ?, 
                                    coach_id = ?, 
                                    description = ?, 
                                    schedule = ?, 
                                    duration_min = ?, 
                                    capacity = ?
                                WHERE id = ?";

                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param(
                            "sssissiii",
                            $name,
                            $category,
                            $instructor,
                            $coach_id,
                            $description,
                            $schedule,
                            $duration,
                            $capacity,
                            $class_id
                        );

                        if ($stmt->execute()) {
                            header("Location: admin_manage_classes.php?updated=1");
                            exit();
                        } else {
                            $error = "Failed to update class.";
                        }
                    }
                }
            }
        }
    }
}

/*
    Delete class
*/
if (isset($_GET["delete"])) {
    $class_id = intval($_GET["delete"]);

    if ($class_id > 0) {
        $delete_sql = "DELETE FROM classes WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $class_id);

        if ($delete_stmt->execute()) {
            header("Location: admin_manage_classes.php?deleted=1");
            exit();
        } else {
            $error = "Failed to delete class. This class may have enrollments connected to it.";
        }
    }
}

/*
    Load class data for editing
*/
if (isset($_GET["edit"])) {
    $class_id = intval($_GET["edit"]);

    if ($class_id > 0) {
        $edit_sql = "SELECT * FROM classes WHERE id = ? LIMIT 1";
        $edit_stmt = $conn->prepare($edit_sql);
        $edit_stmt->bind_param("i", $class_id);
        $edit_stmt->execute();

        $edit_result = $edit_stmt->get_result();

        if ($edit_result->num_rows === 1) {
            $edit_mode = true;
            $edit_class = $edit_result->fetch_assoc();
        } else {
            $error = "Class not found.";
        }
    }
}

/*
    Success messages
*/
if (isset($_GET["added"])) {
    $success = "Class added successfully.";
}

if (isset($_GET["updated"])) {
    $success = "Class updated successfully.";
}

if (isset($_GET["deleted"])) {
    $success = "Class deleted successfully.";
}

/*
    Get all classes with coach data.
*/
$classes_sql = "
    SELECT 
        classes.*,
        coaches.name AS linked_coach_name,
        coaches.specialty AS linked_coach_specialty,
        coaches.user_id AS linked_coach_user_id
    FROM classes
    LEFT JOIN coaches ON classes.coach_id = coaches.id
    ORDER BY classes.schedule ASC
";
$classes_result = $conn->query($classes_sql);

/*
    Values for form
*/
$form_id = $edit_class["id"] ?? "";
$form_name = $edit_class["name"] ?? "";
$form_category = $edit_class["category"] ?? "";
$form_coach_id = $edit_class["coach_id"] ?? "";
$form_description = $edit_class["description"] ?? "";
$form_schedule = "";

if (!empty($edit_class["schedule"])) {
    $form_schedule = date("Y-m-d\TH:i", strtotime($edit_class["schedule"]));
}

$form_duration = $edit_class["duration_min"] ?? "";
$form_capacity = $edit_class["capacity"] ?? "";

/*
    Re-query coaches for the form because previous result may be consumed.
*/
$coaches_result_for_form = $conn->query($coaches_sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Classes — Hustle Muscle</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="admin_manage_classes.css?v=2">
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
            <a href="coach_chats.php">Coach Chats</a>

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
                <span class="line"><span>CLASSES</span></span>
            </h1>

            <p class="manage-sub">
                Add, edit, schedule, and delete gym classes while linking each class to a real coach account for class chat.
            </p>

            <a href="#classForm" class="hero-btn">
                <span><?php echo $edit_mode ? "EDIT SELECTED CLASS" : "ADD NEW CLASS"; ?></span>
                <span>→</span>
            </a>
        </div>
    </section>

    <!-- ── MARQUEE ── -->
    <div class="marquee-strip" aria-hidden="true">
        <div class="marquee-track">
            <span>MANAGE CLASSES</span>
            <span>LINK COACHES</span>
            <span>CLASS CHAT</span>
            <span>BOXING</span>
            <span>ZUMBA</span>
            <span>CROSSFIT</span>
            <span>FULL CRUD</span>
            <span>MANAGE CLASSES</span>
            <span>LINK COACHES</span>
            <span>CLASS CHAT</span>
            <span>BOXING</span>
            <span>ZUMBA</span>
            <span>CROSSFIT</span>
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
        <section class="manage-panel reveal" id="classForm">
            <div class="panel-header">
                <span class="section-tag">
                    <?php echo $edit_mode ? "Update Existing Class" : "Create New Class"; ?>
                </span>

                <h2 class="section-title-small">
                    <?php echo $edit_mode ? "EDIT CLASS" : "ADD NEW CLASS"; ?>
                </h2>
            </div>

            <?php if ($edit_mode): ?>
                <div class="edit-note">
                    <i class="fa-solid fa-pen-to-square"></i>
                    You are editing class ID #<?php echo htmlspecialchars($form_id); ?>.
                </div>
            <?php endif; ?>

            <form action="admin_manage_classes.php" method="post">

                <?php if ($edit_mode): ?>
                    <input type="hidden" name="class_id" value="<?php echo htmlspecialchars($form_id); ?>">
                <?php endif; ?>

                <div class="form-grid">

                    <div class="field">
                        <label for="name">Class Name</label>
                        <input type="text" id="name" name="name"
                               placeholder="Example: Boxing Fundamentals"
                               value="<?php echo htmlspecialchars($form_name); ?>">
                    </div>

                    <div class="field">
                        <label for="category">Category</label>
                        <select id="category" name="category">
                            <option value="">Choose Category</option>
                            <option value="Boxing" <?php if ($form_category == "Boxing") echo "selected"; ?>>Boxing</option>
                            <option value="Zumba" <?php if ($form_category == "Zumba") echo "selected"; ?>>Zumba</option>
                            <option value="CrossFit" <?php if ($form_category == "CrossFit") echo "selected"; ?>>CrossFit</option>
                            <option value="HIIT" <?php if ($form_category == "HIIT") echo "selected"; ?>>HIIT</option>
                            <option value="Yoga" <?php if ($form_category == "Yoga") echo "selected"; ?>>Yoga</option>
                            <option value="Kickboxing" <?php if ($form_category == "Kickboxing") echo "selected"; ?>>Kickboxing</option>
                        </select>
                    </div>

                    <div class="field">
                        <label for="coach_id">Class Coach / Instructor</label>
                        <select id="coach_id" name="coach_id">
                            <option value="">Choose Coach</option>

                            <?php if ($coaches_result_for_form && $coaches_result_for_form->num_rows > 0): ?>
                                <?php while ($coach_option = $coaches_result_for_form->fetch_assoc()): ?>
                                    <option value="<?php echo htmlspecialchars($coach_option["id"]); ?>"
                                        <?php if (intval($form_coach_id) === intval($coach_option["id"])) echo "selected"; ?>>
                                        <?php echo htmlspecialchars($coach_option["name"]); ?>
                                        —
                                        <?php echo htmlspecialchars($coach_option["specialty"]); ?>
                                        <?php echo empty($coach_option["user_id"]) ? " (not linked to login)" : ""; ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>

                        </select>

                        <small class="field-hint">
                            This links the class to a real coach so users can chat with the instructor.
                        </small>
                    </div>

                    <div class="field">
                        <label for="schedule">Schedule</label>
                        <input type="datetime-local" id="schedule" name="schedule"
                               value="<?php echo htmlspecialchars($form_schedule); ?>">
                    </div>

                    <div class="field">
                        <label for="duration_min">Duration in Minutes</label>
                        <input type="number" id="duration_min" name="duration_min"
                               placeholder="Example: 60"
                               value="<?php echo htmlspecialchars($form_duration); ?>">
                    </div>

                    <div class="field">
                        <label for="capacity">Capacity</label>
                        <input type="number" id="capacity" name="capacity"
                               placeholder="Example: 20"
                               value="<?php echo htmlspecialchars($form_capacity); ?>">
                    </div>

                    <div class="field full">
                        <label for="description">Description</label>
                        <textarea id="description" name="description"
                                  placeholder="Write class description..."><?php echo htmlspecialchars($form_description); ?></textarea>
                    </div>

                </div>

                <div class="form-actions">
                    <?php if ($edit_mode): ?>
                        <button type="submit" name="update_class" class="main-btn">
                            <span>Update Class</span>
                        </button>

                        <a href="admin_manage_classes.php" class="secondary-btn">
                            <span>Cancel Edit</span>
                        </a>
                    <?php else: ?>
                        <button type="submit" name="add_class" class="main-btn">
                            <span>Add Class</span>
                        </button>
                    <?php endif; ?>
                </div>

            </form>
        </section>

        <!-- ── TABLE PANEL ── -->
        <section class="manage-panel table-panel reveal">
            <div class="panel-header">
                <span class="section-tag">Class Database</span>
                <h2 class="section-title-small">EXISTING CLASSES</h2>
            </div>

            <?php if ($classes_result && $classes_result->num_rows > 0): ?>

                <div class="table-wrap">
                    <table class="manage-table">
                        <tr>
                            <th>ID</th>
                            <th>Class</th>
                            <th>Category</th>
                            <th>Linked Coach</th>
                            <th>Coach Login</th>
                            <th>Schedule</th>
                            <th>Duration</th>
                            <th>Capacity</th>
                            <th>Enrolled</th>
                            <th>Action</th>
                        </tr>

                        <?php while ($class = $classes_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($class["id"]); ?></td>

                                <td><?php echo htmlspecialchars($class["name"]); ?></td>

                                <td>
                                    <span class="category-badge">
                                        <?php echo htmlspecialchars($class["category"]); ?>
                                    </span>
                                </td>

                                <td>
                                    <?php if (!empty($class["linked_coach_name"])): ?>
                                        <?php echo htmlspecialchars($class["linked_coach_name"]); ?>
                                        <br>
                                        <span class="muted-small">
                                            <?php echo htmlspecialchars($class["linked_coach_specialty"]); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="warning-text">Not linked</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if (!empty($class["linked_coach_user_id"])): ?>
                                        <span class="linked-badge">Linked</span>
                                    <?php else: ?>
                                        <span class="not-linked-badge">No Login</span>
                                    <?php endif; ?>
                                </td>

                                <td><?php echo htmlspecialchars(date("D, M j, Y - h:i A", strtotime($class["schedule"]))); ?></td>

                                <td><?php echo htmlspecialchars($class["duration_min"]); ?> min</td>

                                <td><?php echo htmlspecialchars($class["capacity"]); ?></td>

                                <td><?php echo htmlspecialchars($class["enrolled"]); ?></td>

                                <td class="actions-cell">
                                    <a class="action-btn edit-btn"
                                       href="admin_manage_classes.php?edit=<?php echo $class["id"]; ?>">
                                        Edit
                                    </a>

                                    <a class="action-btn delete-btn js-confirm"
                                       href="admin_manage_classes.php?delete=<?php echo $class["id"]; ?>"
                                       data-confirm="Delete this class? Related enrollments may also be affected.">
                                        Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </table>
                </div>

            <?php else: ?>

                <div class="empty-box">
                    <i class="fa-solid fa-calendar-xmark"></i>
                    <p>No classes found.</p>
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

    <script src="admin_manage_classes.js?v=2"></script>
</body>

</html>