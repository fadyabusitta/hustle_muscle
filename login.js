// ── PASSWORD VISIBILITY TOGGLE ──
const togglePass = document.getElementById('togglePass');
const passwordInput = document.getElementById('password');
const eyeIcon = document.getElementById('eyeIcon');

togglePass.addEventListener('click', () => {
  const isVisible = passwordInput.type === 'text';
  passwordInput.type = isVisible ? 'password' : 'text';
  eyeIcon.className = isVisible ? 'fa-regular fa-eye' : 'fa-regular fa-eye-slash';
});

// ── FORM VALIDATION ──
const loginForm = document.getElementById('loginForm');
const emailInput = document.getElementById('user_name');
const emailGroup = document.getElementById('emailGroup');
const passGroup = document.getElementById('passGroup');
const emailError = document.getElementById('emailError');
const passError = document.getElementById('passError');
const submitBtn = document.getElementById('submitBtn');

function clearError(group, input) {
  group.classList.remove('has-error');
  input.classList.remove('error');
}

function showError(group, input) {
  group.classList.add('has-error');
  input.classList.add('error');
  // shake animation
  input.style.animation = 'shake 0.4s ease';
  input.addEventListener('animationend', () => { input.style.animation = ''; }, { once: true });
}

emailInput.addEventListener('input', () => clearError(emailGroup, emailInput));
passwordInput.addEventListener('input', () => clearError(passGroup, passwordInput));

loginForm.addEventListener('submit', (e) => {
  e.preventDefault();
  let valid = true;

  if (!emailInput.value.trim()) {
    showError(emailGroup, emailInput);
    valid = false;
  } else {
    clearError(emailGroup, emailInput);
  }

  if (!passwordInput.value.trim()) {
    showError(passGroup, passwordInput);
    valid = false;
  } else {
    clearError(passGroup, passwordInput);
  }

  if (!valid) return;

  // Loading state
  submitBtn.disabled = true;
  submitBtn.innerHTML = '<span>LOGGING IN…</span>';

  // Simulate / submit
  setTimeout(() => {
    loginForm.submit();
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
  .toggle-pass {
    position:absolute;
    right:14px; top:50%;
    transform:translateY(-50%);
    background:none; border:none;
    color:var(--grey); cursor:pointer;
    font-size:1rem;
    transition:color 0.3s;
    padding:4px;
  }
  .toggle-pass:hover { color:var(--gold); }
`;
document.head.appendChild(style);

// ── SOCIAL BUTTON PLACEHOLDER ──
document.querySelectorAll('.social-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    btn.style.opacity = '0.6';
    setTimeout(() => { btn.style.opacity = ''; }, 300);
  });
});
