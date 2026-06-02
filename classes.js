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

// ── SEARCH + CATEGORY FILTER ──
const classSearch = document.getElementById('classSearch');
const classCards = document.querySelectorAll('.class-card');
const filterButtons = document.querySelectorAll('.filter-btn');
const noResults = document.getElementById('noResults');

let activeCategory = 'all';

function filterClasses() {
    const searchValue = classSearch ? classSearch.value.trim().toLowerCase() : '';
    let visibleCount = 0;

    classCards.forEach(card => {
        const cardText = card.dataset.search || card.innerText.toLowerCase();
        const cardCategory = card.dataset.category || '';

        const matchesSearch = cardText.includes(searchValue);
        const matchesCategory = activeCategory === 'all' || cardCategory === activeCategory;

        if (matchesSearch && matchesCategory) {
            card.style.display = '';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });

    if (noResults) {
        noResults.classList.toggle('show', visibleCount === 0);
    }
}

if (classSearch) {
    classSearch.addEventListener('keyup', filterClasses);
}

filterButtons.forEach(button => {
    button.addEventListener('click', () => {
        filterButtons.forEach(btn => btn.classList.remove('active'));
        button.classList.add('active');

        activeCategory = button.dataset.category || 'all';

        filterClasses();
    });
});

// ── CARD RIPPLE EFFECT ──
document.querySelectorAll('.class-card').forEach(card => {
    card.addEventListener('click', function (e) {
        if (
            e.target.closest('a') ||
            e.target.closest('button') ||
            e.target.closest('form') ||
            e.target.closest('input')
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
      animation: classRipple 0.6s ease-out forwards;
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
  @keyframes classRipple {
    to {
      width: 430px;
      height: 430px;
      opacity: 0;
    }
  }
`;

document.head.appendChild(rippleStyle);