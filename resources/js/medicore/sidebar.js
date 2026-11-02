/* ==========================================================================
   SIDEBAR — collapse (desktop), offcanvas (mobile), submenus
   ========================================================================== */
(function () {
  const shell = document.querySelector('.app-shell');
  if (!shell) return;

  const STORAGE_KEY = 'hms-sidebar-collapsed';

  // Restore collapsed state on desktop
  try {
    if (localStorage.getItem(STORAGE_KEY) === '1' && window.innerWidth >= 992) {
      shell.classList.add('is-sidebar-collapsed');
    }
  } catch (e) { /* ignore */ }

  document.addEventListener('click', (e) => {
    // Desktop collapse toggle
    if (e.target.closest('[data-sidebar-collapse]')) {
      shell.classList.toggle('is-sidebar-collapsed');
      try {
        localStorage.setItem(STORAGE_KEY, shell.classList.contains('is-sidebar-collapsed') ? '1' : '0');
      } catch (err) { /* ignore */ }
      return;
    }

    // Mobile offcanvas toggle
    if (e.target.closest('[data-sidebar-toggle]')) {
      shell.classList.toggle('is-mobile-nav-open');
      return;
    }

    // Backdrop / close
    if (e.target.closest('[data-sidebar-close]')) {
      shell.classList.remove('is-mobile-nav-open');
      return;
    }

    // Submenu expand/collapse
    const submenuTrigger = e.target.closest('[data-submenu-trigger]');
    if (submenuTrigger) {
      e.preventDefault();
      const item = submenuTrigger.closest('.nav-item');
      const wasOpen = item.classList.contains('is-open');
      // Close sibling submenus for a tidy single-open accordion
      item.parentElement.querySelectorAll('.nav-item.is-open').forEach((sibling) => {
        if (sibling !== item) sibling.classList.remove('is-open');
      });
      item.classList.toggle('is-open', !wasOpen);
    }
  });

  // Close mobile nav on Escape
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') shell.classList.remove('is-mobile-nav-open');
  });

  // Reset mobile state when resizing to desktop
  window.addEventListener('resize', () => {
    if (window.innerWidth >= 992) shell.classList.remove('is-mobile-nav-open');
  });
})();
