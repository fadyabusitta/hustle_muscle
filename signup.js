// ── STEP MANAGEMENT ──
let currentStep = 1;
const totalSteps = 3;

const steps = {
  1: document.getElementById('formStep1'),
  2: document.getElementById('formStep2'),
  3: document.getElementById('formStep3'),
};
const stepDots = {
  1: document.getElementById('step1'),
  2: document.getElementById('step2'),
  3: document.getElementById('step3'),
};
const lines = {
  1: document.getElementById('line1'),
  2: document.getElementById('line2'),
};

function goToStep(n) {
  // Hide current
  steps[currentStep].style.display = 'none';
  // Update dots
  for (let i = 1; i <= totalSteps; i++) {
    stepDots[i].classList.remove('active', 'done');
    if (i < n) stepDots[i].classList.add('done'), (stepDots[i].innerHTML = '<i class="fa-solid fa-check"></i>');
    else if (i === n) stepDots[i].classList.add('active');
    else stepDots[i].innerHTML = i;
  }
  // Update lines
  for (let l = 1; l <= 2; l++) {
    lines[l].classList.toggle('filled', l < n);
  }
  currentStep = n;
  steps[currentStep].style.display = 'flex';
  // Animate in
  steps[currentStep].style.opacity = '0';
  steps[currentStep].style.transform = 'translateY(12px)';
  requestAnimationFrame(() => {
    steps[currentStep].style.transition = 'opacity 0.4s ease, transform 0.4s ease';
    steps[currentStep].style.opacity = '1';
    steps[currentStep].style.transform = 'translateY(0)';
  });
}

// ── FIELD HELPERS ──
function getGroup(input) { return input.closest('.field-group'); }

function showErr(input) {
  const g = getGroup(input);
  g.classList.add('has-error');
  input.classList.add('error');
  input.style.animation = 'shake 0.4s ease';
  input.addEventListener('animationend', () => { input.style.animation = ''; }, { once: true });
}
function clearErr(input) {
  const g = getGroup(input);
  g && g.classList.remove('has-error');
  input.classList.remove('error');
}

// ── STEP 1 VALIDATION ──
const nameInput = document.getElementById('full_name');
const emailInput = document.getElementById('user_email');

nameInput.addEventListener('input', () => clearErr(nameInput));
emailInput.addEventListener('input', () => clearErr(emailInput));

document.getElementById('nextBtn1').addEventListener('click', () => {
  let ok = true;
  if (!nameInput.value.trim()) { showErr(nameInput); ok = false; }
  const emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailRe.test(emailInput.value.trim())) {
    showErr(emailInput);
    document.getElementById('emailError').textContent = emailInput.value.trim()
      ? 'Please enter a valid email address.'
      : 'Email is required.';
    ok = false;
  }
  if (ok) goToStep(2);
});

// ── PASSWORD TOGGLE ──
const passInput   = document.getElementById('password');
const eyeIcon     = document.getElementById('eyeIcon');
document.getElementById('togglePass').addEventListener('click', () => {
  const show = passInput.type === 'password';
  passInput.type = show ? 'text' : 'password';
  eyeIcon.className = show ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye';
});

// ── PASSWORD STRENGTH ──
const segs     = [document.getElementById('seg1'), document.getElementById('seg2'),
                  document.getElementById('seg3'), document.getElementById('seg4')];
const strengthLabel = document.getElementById('strengthLabel');

function calcStrength(pw) {
  let score = 0;
  if (pw.length >= 8)  score++;
  if (/[A-Z]/.test(pw)) score++;
  if (/[0-9]/.test(pw)) score++;
  if (/[^A-Za-z0-9]/.test(pw)) score++;
  return score;
}

passInput.addEventListener('input', () => {
  clearErr(passInput);
  const pw = passInput.value;
  const score = pw ? calcStrength(pw) : 0;
  const labels = ['', 'Weak', 'Fair', 'Good', 'Strong'];
  const classes = ['', 'weak', 'weak', 'medium', 'strong'];
  segs.forEach((s, i) => {
    s.className = 'strength-seg';
    if (pw && i < score) s.classList.add(classes[score]);
  });
  strengthLabel.textContent = pw ? labels[score] : 'Enter a password';
  strengthLabel.style.color = score >= 3 ? '#4caf50' : score === 2 ? 'var(--gold)' : 'var(--red-accent)';
});

const confirmInput = document.getElementById('confirm_pass');
confirmInput.addEventListener('input', () => clearErr(confirmInput));

// ── STEP 2 VALIDATION ──
const userInput = document.getElementById('user_name');
userInput.addEventListener('input', () => clearErr(userInput));

document.getElementById('nextBtn2').addEventListener('click', () => {
  let ok = true;
  if (userInput.value.trim().length < 3) { showErr(userInput); ok = false; }
  if (passInput.value.trim().length < 6) { showErr(passInput); ok = false; }
  if (confirmInput.value !== passInput.value) {
    showErr(confirmInput);
    document.getElementById('confirmError').textContent = 'Passwords do not match.';
    ok = false;
  }
  if (ok) goToStep(3);
});

document.getElementById('backBtn2').addEventListener('click', () => goToStep(1));
document.getElementById('backBtn3').addEventListener('click', () => goToStep(2));

// ── STEP 3 / FINAL SUBMIT ──
const submitBtn = document.getElementById('submitBtn');
const termsBox  = document.getElementById('terms');
const termsError = document.getElementById('termsError');

termsBox.addEventListener('change', () => {
  termsError.style.display = 'none';
});

document.getElementById('signupForm').addEventListener('submit', (e) => {
  e.preventDefault();
  let ok = true;

  if (!termsBox.checked) {
    termsError.style.display = 'block';
    ok = false;
  }

  if (!ok) return;

  submitBtn.disabled = true;
  submitBtn.innerHTML = '<span>CREATING ACCOUNT…</span>';

  setTimeout(() => {
    document.getElementById('signupForm').submit();
  }, 800);
});

// ── SHAKE KEYFRAME ──
const style = document.createElement('style');
style.textContent = `
  @keyframes shake {
    0%,100%{transform:translateX(0)}
    20%{transform:translateX(-8px)}
    40%{transform:translateX(8px)}
    60%{transform:translateX(-6px)}
    80%{transform:translateX(6px)}
  }
  select.field-input option {
    background: var(--dark2);
    color: var(--white);
  }
`;
document.head.appendChild(style);
