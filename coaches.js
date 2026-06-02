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

// ── COACH SEARCH FILTER ──
const coachSearch = document.getElementById('coachSearch');
const coachCards = document.querySelectorAll('.coach-card');
const noResults = document.getElementById('noResults');

if (coachSearch) {
  coachSearch.addEventListener('keyup', () => {
    const value = coachSearch.value.trim().toLowerCase();
    let visibleCount = 0;

    coachCards.forEach(card => {
      const text = card.dataset.search || card.innerText.toLowerCase();

      if (text.includes(value)) {
        card.style.display = '';
        visibleCount++;
      } else {
        card.style.display = 'none';
      }
    });

    if (noResults) {
      noResults.classList.toggle('show', visibleCount === 0);
    }
  });
}

// ── CARD RIPPLE EFFECT ──
document.querySelectorAll('.coach-card').forEach(card => {
  card.addEventListener('click', function (e) {
    if (e.target.closest('a') || e.target.closest('button')) {
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
      animation: coachRipple 0.6s ease-out forwards;
      pointer-events: none;
      z-index: 1;
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
  @keyframes coachRipple {
    to {
      width: 430px;
      height: 430px;
      opacity: 0;
    }
  }
`;

document.head.appendChild(rippleStyle);