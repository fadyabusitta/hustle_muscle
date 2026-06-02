<?php
session_start();
require_once "db.php";

$sql = "SELECT * FROM coaches ORDER BY id ASC";
$result = $conn->query($sql);

$is_logged_in = isset($_SESSION["user_id"]);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Private Coaches — Hustle Muscle</title>

  <link rel="stylesheet" href="coaches.css?v=2">
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
  <section class="coaches-hero">
    <div class="coaches-hero-bg" id="heroBg"></div>

    <div class="coaches-hero-overlay"></div>

    <div class="coaches-hero-content">
      <p class="coaches-eyebrow">Private Coaching — Built For Results</p>

      <h1 class="coaches-title">
        <span class="line"><span>PRIVATE</span></span>
        <span class="line"><span>COACHES</span></span>
      </h1>

      <p class="coaches-sub">
        Choose a professional coach based on your goal. Book strength training,
        boxing, fat loss, CrossFit, endurance, or functional fitness sessions.
      </p>

      <a href="#coachesList" class="hero-btn">
        <span>VIEW COACHES</span>
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
      <span>PRIVATE COACHING</span>
      <span>TRAIN HARDER</span>
      <span>BUILD STRENGTH</span>
      <span>BOXING</span>
      <span>FAT LOSS</span>
      <span>NO EXCUSES</span>
      <span>PRIVATE COACHING</span>
      <span>TRAIN HARDER</span>
      <span>BUILD STRENGTH</span>
      <span>BOXING</span>
      <span>FAT LOSS</span>
      <span>NO EXCUSES</span>
    </div>
  </div>

  <!-- ── COACHES SECTION ── -->
  <section class="coaches-section" id="coachesList">

    <div class="section-header">
      <span class="section-tag reveal">Coaching Team</span>
      <h2 class="section-title reveal reveal-delay-1">CHOOSE YOUR COACH</h2>
    </div>

    <div class="coach-search-wrap reveal reveal-delay-2">
      <i class="fa-solid fa-magnifying-glass"></i>
      <input type="text" id="coachSearch" placeholder="Search by name, specialty, achievement...">
    </div>

    <div class="coaches-grid" id="coachesGrid">

      <?php if ($result && $result->num_rows > 0): ?>
        <?php
          $delay_classes = ["", "reveal-delay-1", "reveal-delay-2", "reveal-delay-3"];
          $counter = 0;
        ?>

        <?php while ($coach = $result->fetch_assoc()): ?>
          <?php
            $delay_class = $delay_classes[$counter % count($delay_classes)];

            $search_text = strtolower(
              $coach["name"] . " " .
              $coach["specialty"] . " " .
              $coach["bio"] . " " .
              $coach["achievements"]
            );

            $counter++;
          ?>

          <article class="coach-card reveal <?php echo $delay_class; ?>"
                   data-search="<?php echo htmlspecialchars($search_text); ?>">

            <div class="coach-icon">
              <i class="fa-solid fa-user-ninja"></i>
            </div>

            <h3 class="coach-name">
              <?php echo htmlspecialchars($coach["name"]); ?>
            </h3>

            <p class="coach-specialty">
              <?php echo htmlspecialchars($coach["specialty"]); ?>
            </p>

            <div class="coach-price">
              <span class="amount"><?php echo htmlspecialchars($coach["price_per_session"]); ?></span>
              <span class="currency">EGP / Session</span>
            </div>

            <ul class="coach-features">
              <li>
                <?php echo htmlspecialchars($coach["bio"]); ?>
              </li>

              <li>
                <strong>Achievements:</strong>
                <?php echo htmlspecialchars($coach["achievements"]); ?>
              </li>
            </ul>

            <a class="coach-cta" href="book_coach.php?coach_id=<?php echo $coach['id']; ?>">
              <span>BOOK SESSION</span>
            </a>

            <?php if (!$is_logged_in): ?>
              <p class="login-note">You will be asked to log in before booking.</p>
            <?php endif; ?>

          </article>

        <?php endwhile; ?>

      <?php else: ?>

        <div class="empty-state reveal">
          <i class="fa-solid fa-circle-exclamation"></i>
          <h3>No Coaches Available</h3>
          <p>There are no private coaches available right now. Please check again later.</p>
        </div>

      <?php endif; ?>

    </div>

    <div class="no-results" id="noResults">
      <i class="fa-solid fa-magnifying-glass"></i>
      <h3>No Matching Coaches</h3>
      <p>Try searching with another name, specialty, or training goal.</p>
    </div>

  </section>

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

  <script src="coaches.js?v=2"></script>
</body>

</html>