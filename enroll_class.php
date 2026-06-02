<?php
session_start();
require_once "db.php";

date_default_timezone_set("Africa/Cairo");

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$class_id = isset($_GET["class_id"]) ? intval($_GET["class_id"]) : 0;

if ($class_id <= 0) {
    header("Location: classes.php");
    exit();
}

$error = "";
$success = "";
$class = null;

$conn->begin_transaction();

try {
    $class_sql = "SELECT * FROM classes WHERE id = ? LIMIT 1 FOR UPDATE";
    $class_stmt = $conn->prepare($class_sql);
    $class_stmt->bind_param("i", $class_id);
    $class_stmt->execute();
    $class_result = $class_stmt->get_result();

    if ($class_result->num_rows !== 1) {
        $conn->rollback();
        $error = "Class not found.";
    } else {
        $class = $class_result->fetch_assoc();

        $schedule_time = strtotime($class["schedule"]);
        $spots_left = intval($class["capacity"]) - intval($class["enrolled"]);

        if ($schedule_time <= time()) {
            $conn->rollback();
            $error = "You cannot enroll in a class that already started or ended.";
        } elseif ($spots_left <= 0) {
            $conn->rollback();
            $error = "This class is already full.";
        } else {
            $check_sql = "SELECT id FROM enrollments WHERE user_id = ? AND class_id = ? LIMIT 1";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("ii", $user_id, $class_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();

            if ($check_result->num_rows > 0) {
                $conn->rollback();
                $error = "You are already enrolled in this class.";
            } else {
                $insert_sql = "INSERT INTO enrollments (user_id, class_id) VALUES (?, ?)";
                $insert_stmt = $conn->prepare($insert_sql);
                $insert_stmt->bind_param("ii", $user_id, $class_id);
                $insert_stmt->execute();

                $update_sql = "UPDATE classes SET enrolled = enrolled + 1 WHERE id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("i", $class_id);
                $update_stmt->execute();

                $conn->commit();
                $success = "You enrolled in this class successfully!";
            }
        }
    }
} catch (Exception $e) {
    $conn->rollback();
    $error = "Enrollment failed. Please try again.";
}

if (!$class) {
    $class_sql = "SELECT * FROM classes WHERE id = ? LIMIT 1";
    $class_stmt = $conn->prepare($class_sql);
    $class_stmt->bind_param("i", $class_id);
    $class_stmt->execute();
    $class_result = $class_stmt->get_result();

    if ($class_result->num_rows === 1) {
        $class = $class_result->fetch_assoc();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Enrollment — Hustle Muscle</title>

    <script src="https://kit.fontawesome.com/f990be7b56.js" crossorigin="anonymous"></script>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: fantasy;
        }

        body {
            background: #0b0b0b;
            color: white;
            min-height: 100vh;
        }

        header {
            background: black;
            padding: 22px 60px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            color: white;
            text-decoration: none;
            font-size: 1.7em;
            line-height: 0.9;
        }

        .logo:hover,
        nav a:hover {
            color: yellow;
        }

        nav a {
            color: white;
            text-decoration: none;
            margin-left: 25px;
            font-family: Arial, sans-serif;
            font-weight: bold;
        }

        .page {
            max-width: 850px;
            margin: 80px auto;
            padding: 0 25px;
        }

        .result-card {
            background: white;
            color: black;
            border-radius: 25px;
            padding: 45px;
            text-align: center;
            box-shadow: 0 15px 35px rgba(0,0,0,0.4);
        }

        .result-card i {
            font-size: 65px;
            margin-bottom: 20px;
        }

        .success-icon {
            color: green;
        }

        .error-icon {
            color: red;
        }

        .result-card h1 {
            margin-bottom: 15px;
        }

        .result-card p {
            font-family: Arial, sans-serif;
            color: #333;
            line-height: 1.7;
            margin-bottom: 12px;
        }

        .message {
            margin: 25px 0;
            padding: 15px;
            border-radius: 12px;
            font-family: Arial, sans-serif;
            font-weight: bold;
        }

        .success {
            background: rgba(0,180,0,0.12);
            color: green;
            border: 1px solid rgba(0,180,0,0.35);
        }

        .error {
            background: rgba(255,0,0,0.12);
            color: red;
            border: 1px solid rgba(255,0,0,0.35);
        }

        .actions {
            display: flex;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
            margin-top: 25px;
        }

        .actions a {
            background: black;
            color: white;
            text-decoration: none;
            padding: 13px 25px;
            border-radius: 30px;
            font-family: Arial, sans-serif;
            font-weight: bold;
        }

        .actions a:hover {
            color: yellow;
        }

        @media(max-width: 768px) {
            header {
                padding: 20px;
                flex-direction: column;
                gap: 20px;
            }

            nav a {
                margin: 0 8px;
            }

            .result-card {
                padding: 30px 20px;
            }
        }
    </style>
</head>

<body>

<header>
    <a href="index.php" class="logo">
        <i class="fa-solid fa-x"></i>
        Hustle<br>Muscle<sup>©</sup>
    </a>

    <nav>
        <a href="index.php">Home</a>
        <a href="classes.php">Classes</a>
        <a href="dashboard.php">Dashboard</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<main class="page">
    <div class="result-card">

        <?php if (!empty($success)): ?>
            <i class="fa-solid fa-circle-check success-icon"></i>
        <?php else: ?>
            <i class="fa-solid fa-circle-xmark error-icon"></i>
        <?php endif; ?>

        <h1>Class Enrollment</h1>

        <?php if ($class): ?>
            <p>
                <strong>Class:</strong>
                <?php echo htmlspecialchars($class["name"]); ?>
            </p>

            <p>
                <strong>Instructor:</strong>
                <?php echo htmlspecialchars($class["instructor"]); ?>
            </p>

            <p>
                <strong>Schedule:</strong>
                <?php echo htmlspecialchars(date("D, M j, Y - h:i A", strtotime($class["schedule"]))); ?>
            </p>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="message success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="message error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="actions">
            <a href="classes.php">Back to Classes</a>
            <a href="dashboard.php">Go to Dashboard</a>
        </div>

    </div>
</main>

</body>
</html>