// ── HEADER SCROLL ── /**/
const header = document.getElementById('header');
window.addEventListener('scroll', () => {
  header.classList.toggle('scrolled', window.scrollY > 40);
});

// ── SCROLL REVEAL ── /**/
const revealObs = new IntersectionObserver((entries) => {
  entries.forEach(e => { if (e.isIntersecting) e.target.classList.add('visible'); });
}, { threshold: 0.1 });
document.querySelectorAll('.reveal').forEach(el => revealObs.observe(el));

// ── FILTER ──
const filterBtns = document.querySelectorAll('.filter-btn');
const cards = document.querySelectorAll('.machine-card');

filterBtns.forEach(btn => {
  btn.addEventListener('click', () => {
    filterBtns.forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    const filter = btn.dataset.filter;

    cards.forEach(card => {
      const cats = card.dataset.category || '';
      const show = filter === 'all' || cats.includes(filter);
      card.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
      if (show) {
        card.style.opacity = '1';
        card.style.transform = 'scale(1)';
        card.style.display = 'grid';
      } else {
        card.style.opacity = '0';
        card.style.transform = 'scale(0.97)';
        setTimeout(() => {
          if (!card.dataset.category.includes(document.querySelector('.filter-btn.active').dataset.filter)
            && document.querySelector('.filter-btn.active').dataset.filter !== 'all') {
            card.style.display = 'none';
          }
        }, 400);
      }
    });
  });
});

// ── MODAL DATA ──
const machineData = {
  bike: {
    title: 'SPRINT BIKE',
    tag: 'Cardio · Power Training',
    desc: 'Eight weeks of maximal power cycling significantly improves muscle mass, muscle power output, and VO₂ max. The sprint bike kickstarts fast-twitch muscle fiber recruitment — the engine behind explosive athletic performance.',
    benefits: [
      'Builds explosive leg power and endurance simultaneously',
      'Increases VO₂ max and cardiovascular efficiency',
      'Burns 800+ calories per hour at high intensity',
      'Low joint impact compared to running or jumping',
      'Engages quads, hamstrings, glutes, and core',
    ],
  },
  rower: {
    title: 'CROSSFIT ROWER',
    tag: 'Cardio · CrossFit',
    desc: 'Rowing CrossFit Workouts are a great way to build conditioning and test your mental toughness. As a core skill, rowing appears in countless WODs — helping you develop power, endurance, and mental grit across all intensities.',
    benefits: [
      'Works 86% of muscles in a single stroke',
      'Excellent for interval training and AMRAP WODs',
      'Dramatically improves aerobic and anaerobic capacity',
      'Zero impact on joints — perfect for injury recovery',
      'Strengthens back, shoulders, arms, and legs together',
    ],
  },
  skierg: {
    title: 'SKIERG',
    tag: 'Full Body · Cardio',
    desc: 'The Concept2 SkiErg develops full-body strength and endurance through a rhythmic, skiing-inspired motion. The harder you pull, the faster the flywheel spins — meaning you control every aspect of the resistance.',
    benefits: [
      'Works arms, core, and legs in every rep',
      'You control resistance — suitable for all levels',
      'Burns 700+ calories per hour',
      'Develops shoulder and lat endurance',
      'Excellent for cross-training and active recovery',
    ],
  },
  weights: {
    title: 'WEIGHTLIFTING',
    tag: 'Strength · Mass Building',
    desc: 'The cornerstone of any serious training program. Lifting weights builds muscle, burns body fat, strengthens your bones and joints, reduces injury risk, and improves cardiovascular health. Start right, stay consistent.',
    benefits: [
      'Increases metabolic rate and fat burning at rest',
      'Builds functional muscle and bone density',
      'Reduces risk of injury through joint stability',
      'Improves posture and body composition',
      'Releases endorphins and reduces stress significantly',
    ],
  },
  kettlebell: {
    title: 'KETTLEBELLS',
    tag: 'Strength · HIIT',
    desc: 'Kettlebell training combines the best of strength training and high-intensity cardio. It reveals and fixes imbalances in the body, relieving stiffness and joint pain while delivering a full-body workout in only 20 minutes.',
    benefits: [
      'Corrects muscle imbalances and asymmetries',
      'Full-body workout in 20 minutes',
      'Combines cardio and strength in one tool',
      'Builds grip strength and forearm endurance',
      'Highly versatile — dozens of movement patterns',
    ],
  },
  rope: {
    title: 'CLIMBING ROPE',
    tag: 'Upper Body · CrossFit',
    desc: 'Rope climbing is a primal, brutally effective upper-body exercise. It forces both arms to work in perfect unison, hammers the lats and upper back musculature, and builds grip strength that transfers to every other lift.',
    benefits: [
      'Builds elite grip strength and endurance',
      'Develops lats, biceps, and rear delts simultaneously',
      'Improves coordination and proprioception',
      'Scales for all levels — from assisted to L-sit climbs',
      'A benchmark CrossFit skill that tests everything',
    ],
  },
};

// ── MODAL LOGIC ──
const modalOverlay = document.getElementById('modalOverlay');
const modalClose = document.getElementById('modalClose');
const modalTitle = document.getElementById('modalTitle');
const modalTag = document.getElementById('modalTag');
const modalDesc = document.getElementById('modalDesc');
const modalBenefits = document.getElementById('modalBenefits');

document.querySelectorAll('.machine-learn').forEach(btn => {
  btn.addEventListener('click', () => {
    const key = btn.dataset.machine;
    const data = machineData[key];
    if (!data) return;

    // Split title for gold span
    const titleParts = data.title.split(' ');
    modalTitle.innerHTML = titleParts.slice(0, -1).join(' ') + ' <span>' + titleParts.slice(-1)[0] + '</span>';
    modalTag.textContent = data.tag;
    modalDesc.textContent = data.desc;
    modalBenefits.innerHTML = data.benefits.map(b => `<li>${b}</li>`).join('');

    modalOverlay.classList.add('open');
    document.body.style.overflow = 'hidden';
  });
});

function closeModal() {
  modalOverlay.classList.remove('open');
  document.body.style.overflow = '';
}

modalClose.addEventListener('click', closeModal);
modalOverlay.addEventListener('click', (e) => {
  if (e.target === modalOverlay) closeModal();
});
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') closeModal();
});
