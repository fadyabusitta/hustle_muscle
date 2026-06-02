// ── HEADER SCROLL ──
const header = document.getElementById('header');
window.addEventListener('scroll', () => {
  header.classList.toggle('scrolled', window.scrollY > 40);
});

// ── HERO BG ──
window.addEventListener('load', () => {
  document.getElementById('heroBg').classList.add('loaded');
});

// ── SLIDER ──
const track      = document.getElementById('sliderTrack');
const slides     = document.querySelectorAll('.slide');
const progressEl = document.getElementById('sliderProgress');
const prevBtn    = document.getElementById('sliderPrev');
const nextBtn    = document.getElementById('sliderNext');

let current  = 0;
let autoPlay = null;

// Build progress dots
slides.forEach((_, i) => {
  const dot = document.createElement('button');
  dot.className = 'progress-dot' + (i === 0 ? ' active' : '');
  dot.setAttribute('aria-label', `Go to slide ${i + 1}`);
  dot.addEventListener('click', () => goTo(i));
  progressEl.appendChild(dot);
});

function goTo(n) {
  slides[current].classList.remove('active');
  progressEl.querySelectorAll('.progress-dot')[current].classList.remove('active');

  current = (n + slides.length) % slides.length;

  slides[current].classList.add('active');
  progressEl.querySelectorAll('.progress-dot')[current].classList.add('active');
  track.style.transform = `translateX(-${current * 100}%)`;
}

function startAutoPlay() {
  autoPlay = setInterval(() => goTo(current + 1), 5000);
}
function stopAutoPlay() {
  clearInterval(autoPlay);
}

prevBtn.addEventListener('click', () => { stopAutoPlay(); goTo(current - 1); startAutoPlay(); });
nextBtn.addEventListener('click', () => { stopAutoPlay(); goTo(current + 1); startAutoPlay(); });

// Touch / swipe support
let touchStartX = 0;
track.addEventListener('touchstart', (e) => { touchStartX = e.touches[0].clientX; }, { passive: true });
track.addEventListener('touchend', (e) => {
  const diff = touchStartX - e.changedTouches[0].clientX;
  if (Math.abs(diff) > 50) {
    stopAutoPlay();
    goTo(diff > 0 ? current + 1 : current - 1);
    startAutoPlay();
  }
});

// Keyboard arrows
document.addEventListener('keydown', (e) => {
  if (e.key === 'ArrowRight') { stopAutoPlay(); goTo(current + 1); startAutoPlay(); }
  if (e.key === 'ArrowLeft')  { stopAutoPlay(); goTo(current - 1); startAutoPlay(); }
});

startAutoPlay();

// ── SCROLL REVEAL ──
const revealObs = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) entry.target.classList.add('visible');
  });
}, { threshold: 0.15 });

document.querySelectorAll('.reveal').forEach(el => revealObs.observe(el));

// ── PARALLAX HERO ──
window.addEventListener('scroll', () => {
  const bg = document.getElementById('heroBg');
  if (bg) bg.style.transform = `translateY(${window.scrollY * 0.3}px)`;
}, { passive: true });
