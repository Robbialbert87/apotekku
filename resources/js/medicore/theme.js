/* ==========================================================================
   THEME TOGGLE — light / dark, persisted in localStorage
   The INITIAL theme is applied by a small inline script in each page's
   <head> (before CSS paints, so there's no flash) — see documentation/README.
   This file only keeps the toggle button's icon/tooltip in sync and handles
   the click to switch themes.
   ========================================================================== */
(function () {
  const STORAGE_KEY = 'hms-theme';
  const root = document.documentElement;

  function applyIcon(theme) {
    document.querySelectorAll('[data-theme-toggle] i').forEach((icon) => {
      icon.className = theme === 'dark' ? 'bi bi-sun' : 'bi bi-moon-stars';
    });
    document.querySelectorAll('[data-theme-toggle]').forEach((btn) => {
      btn.setAttribute('data-tooltip', theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode');
    });
  }

  function setStoredTheme(theme) {
    try {
      localStorage.setItem(STORAGE_KEY, theme);
    } catch (e) { /* storage unavailable (e.g. some browsers restrict it for file:// pages) */ }
  }

  // Sync the toggle button with whatever the inline head script already applied
  applyIcon(root.getAttribute('data-theme') || 'light');

  document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-theme-toggle]');
    if (!btn) return;
    const next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    root.setAttribute('data-theme', next);
    applyIcon(next);
    setStoredTheme(next);
  });

})();
