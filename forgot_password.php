<?php
session_start();
require_once "db.php";

date_default_timezone_set("Africa/Cairo");

$error = "";
$success = "";
$reset_link = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");

    if (empty($email)) {
        $error = "Please enter your email address.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {

        $sql = "SELECT id, full_name, email FROM users WHERE email = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows !== 1) {
            /*
                For a real system, we usually avoid saying whether email exists or not.
                But for college/local testing, showing a clear message is okay.
            */
            $error = "No account found with this email address.";
        } else {
            $user = $result->fetch_assoc();

            /*
                Generate secure random token.
                This token will be stored in database and used in reset link.
            */
            $token = bin2hex(random_bytes(32));

            /*
                Token expires after 30 minutes.
            */
            $expires = date("Y-m-d H:i:s", time() + (30 * 60));

            $update_sql = "UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("ssi", $token, $expires, $user["id"]);

            if ($update_stmt->execute()) {
                $success = "Password reset link generated successfully.";

                /*
                    Localhost testing link.
                    In real hosting, this link would be emailed to the user.
                */
                $reset_link = "http://localhost/hustle_muscle/reset_password.php?token=" . urlencode($token);
            } else {
                $error = "Failed to generate reset link.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password — Hustle Muscle</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

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
            width: 100%;
            background: black;
            padding: 22px 60px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .logo {
            color: white;
            text-decoration: none;
            font-size: 1.9em;
            line-height: 0.9;
            text-align: center;
        }

        .logo:hover {
            color: yellow;
        }

        .page {
            max-width: 600px;
            margin: 80px auto;
            padding: 0 25px;
        }

        .box {
            background: #171717;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 24px;
            padding: 35px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.4);
        }

        h1 {
            color: yellow;
            margin-bottom: 15px;
            text-align: center;
        }

        .description {
            font-family: Arial, sans-serif;
            color: #ccc;
            line-height: 1.6;
            text-align: center;
            margin-bottom: 28px;
        }

        .message {
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-family: Arial, sans-serif;
            font-weight: bold;
            font-size: 14px;
        }

        .success {
            background: rgba(0,180,0,0.14);
            border: 1px solid rgba(0,180,0,0.35);
            color: #00ff7f;
        }

        .error {
            background: rgba(255,0,0,0.14);
            border: 1px solid rgba(255,0,0,0.35);
            color: #ff6b6b;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-family: Arial, sans-serif;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 14px;
            border-radius: 10px;
            border: none;
            outline: none;
            font-family: Arial, sans-serif;
            font-size: 15px;
            margin-bottom: 22px;
        }

        button {
            width: 100%;
            background: yellow;
            color: black;
            border: none;
            padding: 14px;
            border-radius: 30px;
            font-family: Arial, sans-serif;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }

        button:hover {
            transform: scale(1.03);
        }

        .reset-link-box {
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 14px;
            padding: 18px;
            margin-top: 22px;
            word-break: break-all;
        }

        .reset-link-box p {
            font-family: Arial, sans-serif;
            color: #ccc;
            margin-bottom: 10px;
        }

        .reset-link-box a {
            color: yellow;
            font-family: Arial, sans-serif;
            font-weight: bold;
        }

        .back-link {
            display: block;
            margin-top: 25px;
            text-align: center;
            color: white;
            text-decoration: none;
            font-family: Arial, sans-serif;
            font-weight: bold;
        }

        .back-link:hover {
            color: yellow;
        }

        @media(max-width: 700px) {
            header {
                padding: 20px;
            }

            .page {
                margin: 45px auto;
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
</header>

<main class="page">
    <div class="box">

        <h1>Forgot Password</h1>

        <p class="description">
            Enter your account email address. For local testing, the reset link will be displayed on this page instead of being sent by email.
        </p>

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

        <form action="forgot_password.php" method="post">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" placeholder="your@email.com"
                   value="<?php echo htmlspecialchars($_POST["email"] ?? ""); ?>">

            <button type="submit">Generate Reset Link</button>
        </form>

        <?php if (!empty($reset_link)): ?>
            <div class="reset-link-box">
                <p><strong>Local Testing Reset Link:</strong></p>
                <a href="<?php echo htmlspecialchars($reset_link); ?>">
                    <?php echo htmlspecialchars($reset_link); ?>
                </a>
            </div>
        <?php endif; ?>

        <a href="login.php" class="back-link">← Back to Login</a>

    </div>
</main>

</body>
</html>