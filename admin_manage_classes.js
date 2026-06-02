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

// ── SET MINIMUM DATETIME TO NOW ──
const scheduleInput = document.getElementById('schedule');

if (scheduleInput) {
  const now = new Date();
  now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
  scheduleInput.min = now.toISOString().slice(0, 16);
}

// ── CONFIRM LINKS ──
document.querySelectorAll('.js-confirm').forEach(link => {
  link.addEventListener('click', (event) => {
    const message = link.dataset.confirm || 'Are you sure?';

    if (!confirm(message)) {
      event.preventDefault();
    }
  });
});

// ── SMOOTH SCROLL FOR ANCHOR LINKS ──
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
  anchor.addEventListener('click', (e) => {
    const target = document.querySelector(anchor.getAttribute('href'));

    if (target) {
      e.preventDefault();

      const offset = 72;

      window.scrollTo({
        top: target.getBoundingClientRect().top + window.scrollY - offset,
        behavior: 'smooth'
      });
    }
  });
});

// ── PANEL RIPPLE EFFECT ──
document.querySelectorAll('.manage-panel').forEach(panel => {
  panel.addEventListener('click', function (e) {
    if (
      e.target.closest('a') ||
      e.target.closest('button') ||
      e.target.closest('input') ||
      e.target.closest('textarea') ||
      e.target.closest('select') ||
      e.target.closest('table') ||
      e.target.closest('label')
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
      animation: manageRipple 0.6s ease-out forwards;
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
  @keyframes manageRipple {
    to {
      width: 430px;
      height: 430px;
      opacity: 0;
    }
  }
`;

document.head.appendChild(rippleStyle);