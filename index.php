<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Hustle Muscle — Smart Gym</title>
  <link rel="stylesheet" href="hustle.css">
  <script src="https://kit.fontawesome.com/f990be7b56.js" crossorigin="anonymous"></script>
</head>

<body>

  <!-- ── HEADER ── -->
  <header class="site-header" id="header">
    <a href="index.php" class="logo-link">
      <i class="fa-solid fa-x x-icon"></i>Hustle Muscle<sup>©</sup>
    </a>
    <nav class="site-nav">
      <a href="about.html">About</a>
      <a href="#plans">Packages</a>
      <a href="machines.html">Machines</a>
      <span class="nav-divider">|</span>
      <a href="signup.php">Sign Up</a>
      <a href="login.php" class="nav-cta">Log In</a>
    </nav>
  </header>

  <!-- ── HERO ── -->
  <section class="hero">
    <div class="hero-bg" id="heroBg"></div>
    <div class="hero-content">
      <p class="hero-eyebrow">Est. 2026 — Cairo, Egypt</p>
      <h1 class="hero-title">
        <span class="line"><span>WHAT HURTS</span></span>
        <span class="line"><span>TODAY MAKES</span></span>
        <span class="line"><span>YOU STRONGER</span></span>
      </h1>
      <p class="hero-sub">Where discipline becomes lifestyle. Join thousands who chose to push further.</p>
      <a href="#plans" class="hero-cta">
        <span>LET'S DO THIS</span>
        <span>→</span>
      </a>
    </div>
    <div class="hero-scroll-hint"> <!---->
      <div class="scroll-line"></div>
      SCROLL
    </div>
  </section>

  <!-- ── MARQUEE ── -->
  <div class="marquee-strip" aria-hidden="true">
    <div class="marquee-track" id="marqueeTrack">
      <span>HUSTLE MUSCLE</span>
      <span>TRAIN HARDER</span>
      <span>NO EXCUSES</span>
      <span>UNLEASH THE BEAST</span>
      <span>PUSH YOUR LIMITS</span>
      <span>BUILD DISCIPLINE</span>
      <span>HUSTLE MUSCLE</span>
      <span>TRAIN HARDER</span>
      <span>NO EXCUSES</span>
      <span>UNLEASH THE BEAST</span>
      <span>PUSH YOUR LIMITS</span>
      <span>BUILD DISCIPLINE</span>
    </div>
  </div>

  <!-- ── MOTIVATION ── -->
  <section class="motivation">
    <div class="motivation-text reveal">
      <h2>ALL PROGRESS TAKES PLACE<br><em>OUTSIDE THE COMFORT ZONE</em></h2>
      <p>We built Hustle Muscle for those who are serious. No fluff, no gimmicks — just real training, real coaches,
        real results.</p>
    </div>
    <div class="motivation-stats">
      <div class="stat-item reveal reveal-delay-1">
        <span class="stat-num" data-target="500">0</span>
        <span class="stat-label">Members</span>
      </div>
      <div class="stat-item reveal reveal-delay-2">
        <span class="stat-num" data-target="12">0</span>
        <span class="stat-label">Coaches</span>
      </div>
      <div class="stat-item reveal reveal-delay-3">
        <span class="stat-num" data-target="20">0</span>
        <span class="stat-label">Classes</span>
      </div>
    </div>
  </section>

  <!-- ── PACKAGES ── -->
  <section class="packages" id="plans">
    <div class="section-header">
      <span class="section-tag reveal">Membership</span>
      <h2 class="section-title reveal reveal-delay-1">PICK YOUR PACKAGE</h2>
    </div>
    <div class="plans-grid">

      <article class="plan-card reveal">
        <div class="plan-duration">1 Month</div>
        <div class="plan-price">
          <span class="amount">600</span>
          <span class="currency">EGP</span>
        </div>
        <ul class="plan-features">
          <li>Full gym access</li>
          <li>Locker room</li>
          <li>1 free class/week</li>
        </ul>
        <a href="select_plan.php?plan=1month" class="plan-cta"><span>GET STARTED</span></a>
      </article>

      <article class="plan-card reveal reveal-delay-1">
        <div class="plan-duration">3 Months</div>
        <div class="plan-price">
          <span class="amount">1300</span>
          <span class="currency">EGP</span>
        </div>
        <ul class="plan-features">
          <li>Full gym access</li>
          <li>Locker room</li>
          <li>3 free classes/week</li>
        </ul>
        <a href="select_plan.php?plan=3months" class="plan-cta"><span>GET STARTED</span></a>
      </article>

      <article class="plan-card featured reveal reveal-delay-2">
        <div class="plan-badge">Best Value</div>
        <div class="plan-duration">6 Months</div>
        <div class="plan-price">
          <span class="amount">2100</span>
          <span class="currency">EGP</span>
        </div>
        <ul class="plan-features">
          <li>Full gym access</li>
          <li>Locker room</li>
          <li>Unlimited classes</li>
          <li>1 coach session/mo</li>
        </ul>
        <a href="select_plan.php?plan=6months" class="plan-cta"><span>GET STARTED</span></a>
      </article>

      <article class="plan-card reveal reveal-delay-3">
        <div class="plan-duration">1 Year</div>
        <div class="plan-price">
          <span class="amount">3500</span>
          <span class="currency">EGP</span>
        </div>
        <ul class="plan-features">
          <li>Full gym access</li>
          <li>Locker room</li>
          <li>Unlimited classes</li>
          <li>4 coach sessions/mo</li>
          <li>Priority booking</li>
        </ul>
        <a href="select_plan.php?plan=1year" class="plan-cta"><span>GET STARTED</span></a>
      </article>

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

  <script src="hustle.js"></script>
</body>

</html>