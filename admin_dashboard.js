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

// ── COUNTER ANIMATION ──
const counterObserver = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (!entry.isIntersecting) return;

    const el = entry.target;
    const target = parseInt(el.dataset.target, 10) || 0;

    let current = 0;
    const step = Math.max(1, Math.ceil(target / 55));

    const timer = setInterval(() => {
      current = Math.min(current + step, target);
      el.textContent = current;

      if (current >= target) {
        clearInterval(timer);
      }
    }, 22);

    counterObserver.unobserve(el);
  });
}, { threshold: 0.45 });

document.querySelectorAll('.stat-num[data-target]').forEach(el => {
  counterObserver.observe(el);
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

// ── CARD RIPPLE EFFECT ──
document.querySelectorAll('.stat-card, .admin-action-card, .admin-section').forEach(card => {
  card.addEventListener('click', function (e) {
    if (
      e.target.closest('a') ||
      e.target.closest('button') ||
      e.target.closest('input') ||
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
      animation: adminRipple 0.6s ease-out forwards;
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
  @keyframes adminRipple {
    to {
      width: 430px;
      height: 430px;
      opacity: 0;
    }
  }
`;

document.head.appendChild(rippleStyle);