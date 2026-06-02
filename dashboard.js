// ── HEADER SCROLL EFFECT ──
const header = document.getElementById('header');

if (header) {
  window.addEventListener('scroll', () => {
    header.classList.toggle('scrolled', window.scrollY > 40);
  });
}

// ── HERO BG LOAD ANIMATION ──
window.addEventListener('load', () => {
  const heroBg = document.getElementById('heroBg');

  if (heroBg) {
    heroBg.classList.add('loaded');
  }
});

// ── SCROLL REVEAL ──
const revealObserver = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      entry.target.classList.add('visible');
    }
  });
}, { threshold: 0.15 });

document.querySelectorAll('.reveal').forEach(el => {
  revealObserver.observe(el);
});

// ── PARALLAX HERO ──
window.addEventListener('scroll', () => {
  const bg = document.getElementById('heroBg');

  if (bg) {
    const scrolled = window.scrollY;
    bg.style.transform = `scale(1) translateY(${scrolled * 0.18}px)`;
  }
}, { passive: true });

// ── TOGGLE PROFILE / PASSWORD PANELS ──
const toggleButtons = document.querySelectorAll('[data-panel]');
const profilePanel = document.getElementById('profilePanel');
const passwordPanel = document.getElementById('passwordPanel');

toggleButtons.forEach(button => {
  button.addEventListener('click', () => {
    const targetId = button.dataset.panel;
    const selectedPanel = document.getElementById(targetId);

    if (!selectedPanel) return;

    if (selectedPanel.classList.contains('open')) {
      selectedPanel.classList.remove('open');
      return;
    }

    if (profilePanel) profilePanel.classList.remove('open');
    if (passwordPanel) passwordPanel.classList.remove('open');

    selectedPanel.classList.add('open');

    selectedPanel.scrollIntoView({
      behavior: 'smooth',
      block: 'start'
    });
  });
});

// ── CONFIRM LINKS ──
document.querySelectorAll('.js-confirm').forEach(link => {
  link.addEventListener('click', (event) => {
    const message = link.dataset.confirm || 'Are you sure?';

    if (!confirm(message)) {
      event.preventDefault();
    }
  });
});

// ── CARD / PANEL RIPPLE EFFECT ──
document.querySelectorAll('.dashboard-card, .dashboard-panel').forEach(card => {
  card.addEventListener('click', function (e) {
    if (
      e.target.closest('a') ||
      e.target.closest('button') ||
      e.target.closest('input') ||
      e.target.closest('label') ||
      e.target.closest('table')
    ) {
      return;
    }

    const rect = this.getBoundingClientRect();
    const ripple = document.createElement('span');

    ripple.style.cssText = `
      position: absolute;
      left: ${e.clientX - rect.left}px;
      top: ${e.clientY - rect.top}px;
      width: 0;
      height: 0;
      background: rgba(245,197,24,0.12);
      border-radius: 50%;
      transform: translate(-50%, -50%);
      animation: dashboardRipple 0.6s ease-out forwards;
      pointer-events: none;
    `;

    this.style.position = 'relative';
    this.style.overflow = 'hidden';
    this.appendChild(ripple);

    setTimeout(() => {
      ripple.remove();
    }, 600);
  });
});

// ── INJECT RIPPLE KEYFRAME ──
const rippleStyle = document.createElement('style');

rippleStyle.textContent = `
  @keyframes dashboardRipple {
    to {
      width: 420px;
      height: 420px;
      opacity: 0;
    }
  }
`;

document.head.appendChild(rippleStyle);