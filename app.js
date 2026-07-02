// app.js - UI interactions (menu open/close + theme toggle + accessibility)
const menuToggle = document.getElementById('menuToggle');
const slideMenu = document.getElementById('slideMenu');
const menuClose = document.getElementById('menuClose');
const themeToggleBtn = document.getElementById('themeToggleBtn');
const root = document.documentElement;

// Open/close menu
if (menuToggle) {
  menuToggle.addEventListener('click', () => {
    slideMenu.classList.toggle('open');
    const open = slideMenu.classList.contains('open');
    slideMenu.setAttribute('aria-hidden', open ? 'false' : 'true');
    if (open) {
      // focus the close button for keyboard users
      const closeBtn = slideMenu.querySelector('.menu-close');
      if (closeBtn) closeBtn.focus();
    } else {
      menuToggle.focus();
    }
  });
}

// Close button inside menu
if (menuClose) {
  menuClose.addEventListener('click', () => {
    slideMenu.classList.remove('open');
    slideMenu.setAttribute('aria-hidden', 'true');
    if (menuToggle) menuToggle.focus();
  });
}

// Close menu on Escape key
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape' && slideMenu.classList.contains('open')) {
    slideMenu.classList.remove('open');
    slideMenu.setAttribute('aria-hidden', 'true');
    if (menuToggle) menuToggle.focus();
  }
});

// Theme toggle logic (persists in localStorage)
(function initTheme() {
  const saved = localStorage.getItem('zama-theme');
  const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
  const theme = saved || (prefersDark ? 'dark' : 'light');
  root.setAttribute('data-theme', theme);
  updateThemeButton(theme);
})();

function updateThemeButton(theme) {
  if (!themeToggleBtn) return;
  const isDark = theme === 'dark';
  themeToggleBtn.textContent = isDark ? 'Switch to Light' : 'Switch to Dark';
  themeToggleBtn.setAttribute('aria-pressed', isDark ? 'true' : 'false');
}

if (themeToggleBtn) {
  themeToggleBtn.addEventListener('click', () => {
    const current = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    root.setAttribute('data-theme', current);
    localStorage.setItem('zama-theme', current);
    updateThemeButton(current);
  });
}
