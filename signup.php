<?php
session_start();
require_once "db.php";

$error = "";
$selected_plan = $_GET["plan"] ?? ($_POST["plan"] ?? "");

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $full_name = trim($_POST["full_name"] ?? "");
    $email = trim($_POST["user_email"] ?? "");
    $username = trim($_POST["user_name"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm_pass = $_POST["confirm_pass"] ?? "";
    $plan = $_POST["plan"] ?? "";
    $terms = isset($_POST["terms"]);

    if (empty($full_name) || empty($email) || empty($username) || empty($password) || empty($confirm_pass)) {
        $error = "All required fields must be filled.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($username) < 3) {
        $error = "Username must be at least 3 characters.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($password !== $confirm_pass) {
        $error = "Passwords do not match.";
    } elseif (empty($plan)) {
        $error = "Please choose a membership plan.";
    } elseif (!$terms) {
        $error = "You must accept the terms and privacy policy.";
    } else {

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO users (full_name, email, username, password, plan)
                VALUES (?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            $error = "Database error: " . $conn->error;
        } else {
            $stmt->bind_param("sssss", $full_name, $email, $username, $hashed_password, $plan);

            if ($stmt->execute()) {
                $_SESSION["user_id"] = $stmt->insert_id;
                $_SESSION["full_name"] = $full_name;
                $_SESSION["username"] = $username;
                $_SESSION["plan"] = $plan;

                header("Location: dashboard.php");
                exit();
            } else {
                if ($conn->errno == 1062) {
                    $error = "Email or username already exists.";
                } else {
                    $error = "Signup failed: " . $conn->error;
                }
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
  <title>Sign Up — Hustle Muscle</title>
  <link rel="stylesheet" href="signup.css">
  <script src="https://kit.fontawesome.com/f990be7b56.js" crossorigin="anonymous"></script>
</head>
<body>

  <!-- ── LEFT VISUAL ── -->
  <div class="auth-visual">
    <a href="index.php" class="logo-link visual-logo">
      <i class="fa-solid fa-x x-icon"></i>Hustle Muscle<sup>©</sup>
    </a>
    <div class="visual-content">
      <p class="visual-quote">"THE BODY<br><em>ACHIEVES WHAT</em><br>THE MIND BELIEVES."</p>
      <p class="visual-author">— Hustle Muscle Creed</p>
    </div>
  </div>

  <!-- ── RIGHT FORM PANEL ── -->
  <div class="auth-panel">
    <div class="auth-header">
      <h1>JOIN THE GYM</h1>
      <p>Create your account and start training today.</p>
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

    <!-- Step progress -->
    <div class="signup-steps">
      <div class="step-dot active" id="step1">1</div>
      <div class="step-line" id="line1"></div>
      <div class="step-dot" id="step2">2</div>
      <div class="step-line" id="line2"></div>
      <div class="step-dot" id="step3">3</div>
    </div>

    <form class="auth-form" id="signupForm" action="signup.php" method="post" novalidate>

      <!-- Step 1: Identity -->
      <div id="formStep1">
        <div class="field-group" id="nameGroup">
          <label class="field-label" for="full_name">Full Name</label>
          <input class="field-input" type="text" id="full_name" name="full_name"
                 placeholder="John Doe" autocomplete="name"
                 value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
          <span class="error-msg">Please enter your full name.</span>
        </div>

        <div class="field-group" id="emailGroup" style="margin-top:20px;">
          <label class="field-label" for="user_email">Email Address</label>
          <input class="field-input" type="email" id="user_email" name="user_email"
                 placeholder="your@email.com" autocomplete="email"
                 value="<?php echo htmlspecialchars($_POST['user_email'] ?? ''); ?>">
          <span class="error-msg" id="emailError">Please enter a valid email.</span>
        </div>

        <button type="button" class="submit-btn" id="nextBtn1" style="margin-top:24px;">
          <span>NEXT →</span>
        </button>
      </div>

      <!-- Step 2: Credentials -->
      <div id="formStep2" style="display:none; flex-direction:column; gap:20px;">
        <div class="field-group" id="userGroup">
          <label class="field-label" for="user_name">Username</label>
          <input class="field-input" type="text" id="user_name" name="user_name"
                 placeholder="hustler99" autocomplete="username"
                 value="<?php echo htmlspecialchars($_POST['user_name'] ?? ''); ?>">
          <span class="error-msg">Choose a username (min 3 chars).</span>
        </div>

        <div class="field-group" id="passGroup">
          <label class="field-label" for="password">Password</label>
          <div class="pass-wrap">
            <input class="field-input" type="password" id="password" name="password"
                   placeholder="Create a strong password" autocomplete="new-password"
                   style="padding-right:48px;">
            <button type="button" class="toggle-pass" id="togglePass" aria-label="Toggle password">
              <i class="fa-regular fa-eye" id="eyeIcon"></i>
            </button>
          </div>
          <div class="strength-bar">
            <div class="strength-seg" id="seg1"></div>
            <div class="strength-seg" id="seg2"></div>
            <div class="strength-seg" id="seg3"></div>
            <div class="strength-seg" id="seg4"></div>
          </div>
          <span class="strength-label" id="strengthLabel">Enter a password</span>
          <span class="error-msg">Password must be at least 6 characters.</span>
        </div>

        <div class="field-group" id="confirmGroup">
          <label class="field-label" for="confirm_pass">Confirm Password</label>
          <input class="field-input" type="password" id="confirm_pass" name="confirm_pass"
                 placeholder="Re-enter password" autocomplete="new-password">
          <span class="error-msg" id="confirmError">Passwords do not match.</span>
        </div>

        <div style="display:flex; gap:12px; margin-top:4px;">
          <button type="button" class="submit-btn" id="backBtn2"
                  style="background:transparent;border:1px solid rgba(255,255,255,0.15);color:var(--grey);flex:0.4;">
            <span>← BACK</span>
          </button>
          <button type="button" class="submit-btn" id="nextBtn2" style="flex:1;">
            <span>NEXT →</span>
          </button>
        </div>
      </div>

      <!-- Step 3: Confirm -->
      <div id="formStep3" style="display:none; flex-direction:column; gap:20px;">
        <div class="field-group">
          <label class="field-label">Membership Plan</label>
          <select class="field-input" name="plan" id="plan" style="cursor:pointer;">
           <option value="">— Choose a plan —</option>
           <option value="1month" <?php if ($selected_plan == "1month") echo "selected"; ?>>1 Month — 600 EGP</option>
           <option value="3months" <?php if ($selected_plan == "3months") echo "selected"; ?>>3 Months — 1,300 EGP</option>
           <option value="6months" <?php if ($selected_plan == "6months") echo "selected"; ?>>6 Months — 2,100 EGP</option>
           <option value="1year" <?php if ($selected_plan == "1year") echo "selected"; ?>>1 Year — 3,500 EGP</option>
          </select>
        </div>

        <label class="terms-wrap">
          <input type="checkbox" id="terms" name="terms" <?php if (isset($_POST['terms'])) echo 'checked'; ?>>
          <span>I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a> of Hustle Muscle.</span>
        </label>

        <span class="error-msg" id="termsError" style="display:none;margin-top:-12px;">
          Please accept the terms.
        </span>

        <div style="display:flex; gap:12px; margin-top:4px;">
          <button type="button" class="submit-btn" id="backBtn3"
                  style="background:transparent;border:1px solid rgba(255,255,255,0.15);color:var(--grey);flex:0.4;">
            <span>← BACK</span>
          </button>
          <button type="submit" class="submit-btn" id="submitBtn" style="flex:1;">
            <span>CREATE ACCOUNT</span>
          </button>
        </div>
      </div>

    </form>

    <p class="auth-footer-text">
      Already a member? <a href="login.php">Log in</a>
    </p>
  </div>

  <script src="signup.js"></script>
</body>
</html>