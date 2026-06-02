<?php
session_start();
require_once "db.php";

$error = "";

/*
    If user is already logged in, send him directly to dashboard.
*/
if (isset($_SESSION["user_id"])) {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username_or_email = trim($_POST["user_name"] ?? "");
    $password = $_POST["password"] ?? "";

    if (empty($username_or_email) || empty($password)) {
        $error = "Please enter your email/username and password.";
    } else {

        $sql = "SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1";
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            $error = "Database error: " . $conn->error;
        } else {
            $stmt->bind_param("ss", $username_or_email, $username_or_email);
            $stmt->execute();

            $result = $stmt->get_result();

            if ($result->num_rows == 1) {
                $user = $result->fetch_assoc();

                if (password_verify($password, $user["password"])) {

                    $_SESSION["user_id"] = $user["id"];
                    $_SESSION["full_name"] = $user["full_name"];
                    $_SESSION["username"] = $user["username"];
                    $_SESSION["plan"] = $user["plan"];
                    $_SESSION["role"] = $user["role"];

                    header("Location: dashboard.php");
                    exit();

                } else {
                    $error = "Incorrect password.";
                }

            } else {
                $error = "No account found with this email or username.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Log In — Hustle Muscle</title>
  <link rel="stylesheet" href="login.css">
  <script src="https://kit.fontawesome.com/f990be7b56.js" crossorigin="anonymous"></script>
</head>

<body>

  <!-- ── LEFT: VISUAL PANEL ── -->
  <div class="auth-visual">
    <a href="index.php" class="logo-link visual-logo">
      <i class="fa-solid fa-x x-icon"></i>Hustle Muscle<sup>©</sup>
    </a>

    <div class="visual-content">
      <p class="visual-quote">"PAIN IS JUST<br><em>WEAKNESS LEAVING</em><br>THE BODY."</p>
      <p class="visual-author">— Hustle Muscle Creed</p>
    </div>
  </div>

  <!-- ── RIGHT: FORM PANEL ── -->
  <div class="auth-panel">

    <div class="auth-header">
      <h1>WELCOME BACK</h1>
      <p>Log in to continue your journey.</p>
    </div>

    <?php if (!empty($error)): ?>
      <div style="
        background: rgba(255, 0, 0, 0.12);
        border: 1px solid rgba(255, 0, 0, 0.35);
        color: #ff6b6b;
        padding: 12px 16px;
        border-radius: 10px;
        margin-bottom: 20px;
        font-family: Arial, sans-serif;
        font-size: 14px;
      ">
        <?php echo htmlspecialchars($error); ?>
      </div>
    <?php endif; ?>

    <div class="social-btns">
      <button type="button" class="social-btn facebook">
        <i class="fa-brands fa-square-facebook"></i> Continue with Facebook
      </button>

      <button type="button" class="social-btn apple">
        <i class="fa-brands fa-apple"></i> Continue with Apple
      </button>

      <button type="button" class="social-btn google">
        <i class="fa-brands fa-google"></i> Continue with Google
      </button>
    </div>

    <div class="divider">
      <div class="divider-line"></div>
      <span>or</span>
      <div class="divider-line"></div>
    </div>

    <form class="auth-form" id="loginForm" action="login.php" method="post" novalidate>

      <div class="field-group" id="emailGroup">
        <label class="field-label" for="user_name">Email or Username</label>
        <input class="field-input" type="text" id="user_name" name="user_name"
               placeholder="your@email.com" autocomplete="username"
               value="<?php echo htmlspecialchars($_POST['user_name'] ?? ''); ?>">
        <span class="error-msg" id="emailError">Please enter your email or username.</span>
      </div>

      <div class="field-group" id="passGroup">
        <label class="field-label" for="password">Password</label>

        <div style="position:relative;">
          <input class="field-input" type="password" id="password" name="password"
                 placeholder="••••••••" autocomplete="current-password" style="padding-right:48px;">

          <button type="button" class="toggle-pass" id="togglePass" aria-label="Show password">
            <i class="fa-regular fa-eye" id="eyeIcon"></i>
          </button>
        </div>

        <span class="error-msg" id="passError">Please enter your password.</span>
      </div>

      <div class="field-row">
        <label class="remember-wrap">
          <input type="checkbox" name="remember" id="remember">
          <span>Remember me</span>
        </label>

        <a href="forgot_password.php" class="forgot-link">Forgot password?</a>
      </div>

      <button type="submit" class="submit-btn" id="submitBtn">
        <span>LOG IN</span>
      </button>

    </form>

    <p class="auth-footer-text">
      Don't have an account? <a href="signup.php">Sign up free</a>
    </p>

  </div>

  <script src="login.js"></script>
</body>
</html>