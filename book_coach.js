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

// ── BOOKING DATE + TIME VALIDATION UI ──
const dateInput = document.getElementById('booking_date');
const timeSelect = document.getElementById('time_slot');

if (dateInput && timeSelect) {
  /*
      Get today's date in local browser time.
      This prevents choosing yesterday or older days from the date picker.
  */
  const now = new Date();

  const todayString =
    now.getFullYear() + '-' +
    String(now.getMonth() + 1).padStart(2, '0') + '-' +
    String(now.getDate()).padStart(2, '0');

  dateInput.setAttribute('min', todayString);

  /*
      Disable time slots that already passed if user chooses today's date.
  */
  function updateAvailableTimes() {
    const selectedDate = dateInput.value;
    const currentTime = new Date();

    Array.from(timeSelect.options).forEach(option => {
      if (option.value === '') {
        return;
      }

      option.disabled = false;

      if (selectedDate === todayString) {
        const [hours, minutes, seconds] = option.value.split(':');

        const optionDateTime = new Date();
        optionDateTime.setHours(parseInt(hours, 10));
        optionDateTime.setMinutes(parseInt(minutes, 10));
        optionDateTime.setSeconds(parseInt(seconds || '0', 10));
        optionDateTime.setMilliseconds(0);

        if (optionDateTime <= currentTime) {
          option.disabled = true;
        }
      }
    });

    if (timeSelect.selectedOptions[0] && timeSelect.selectedOptions[0].disabled) {
      timeSelect.value = '';
    }
  }

  dateInput.addEventListener('change', updateAvailableTimes);
  timeSelect.addEventListener('focus', updateAvailableTimes);
  timeSelect.addEventListener('click', updateAvailableTimes);

  updateAvailableTimes();
}

// ── PANEL RIPPLE EFFECT ──
document.querySelectorAll('.booking-panel').forEach(panel => {
  panel.addEventListener('click', function (e) {
    if (
      e.target.closest('a') ||
      e.target.closest('button') ||
      e.target.closest('input') ||
      e.target.closest('select') ||
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
      animation: bookingRipple 0.6s ease-out forwards;
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
  @keyframes bookingRipple {
    to {
      width: 430px;
      height: 430px;
      opacity: 0;
    }
  }
`;

document.head.appendChild(rippleStyle);