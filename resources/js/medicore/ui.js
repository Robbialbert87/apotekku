/* ==========================================================================
   UI INTERACTIONS — dropdowns, tabs, modals, toasts
   Framework-free so this logic maps 1:1 onto React state later
   (isOpen / activeTab / isVisible become component state).
   ========================================================================== */

/* ---- Dropdowns (topbar user menu, notifications, row actions) ---- */
(function initDropdowns() {
  document.addEventListener('click', (e) => {
    const trigger = e.target.closest('[data-dropdown-trigger]');
    const openMenu = document.querySelector('.menu-custom.is-open, .dropdown-menu-custom.is-open');

    if (trigger) {
      const targetId = trigger.getAttribute('data-dropdown-trigger');
      const menu = document.getElementById(targetId);
      const isSameMenu = menu === openMenu;
      if (openMenu) openMenu.classList.remove('is-open');
      if (menu && !isSameMenu) menu.classList.add('is-open');
      return;
    }

    // Click outside closes any open menu
    if (openMenu && !e.target.closest('.menu-custom, .dropdown-menu-custom')) {
      openMenu.classList.remove('is-open');
    }
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      document.querySelectorAll('.menu-custom.is-open, .dropdown-menu-custom.is-open')
        .forEach((m) => m.classList.remove('is-open'));
    }
  });
})();

/* ---- Tabs ---- */
(function initTabs() {
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.tab-btn');
    if (!btn) return;
    const group = btn.closest('.tabs-custom');
    const panelId = btn.getAttribute('data-tab-target');
    const panelsSelector = btn.getAttribute('data-tab-panels');
    const panelContainer = panelsSelector ? document.querySelector(panelsSelector) : null;

    group.querySelectorAll('.tab-btn').forEach((b) => b.classList.remove('is-active'));
    btn.classList.add('is-active');

    if (panelContainer && panelId) {
      panelContainer.querySelectorAll('.tab-panel').forEach((p) => p.classList.remove('is-active'));
      const target = document.getElementById(panelId);
      if (target) target.classList.add('is-active');
    }
  });
})();

/* ---- Modals ---- */
(function initModals() {
  document.addEventListener('click', (e) => {
    const opener = e.target.closest('[data-modal-open]');
    if (opener) {
      if (opener.tagName === 'A') e.preventDefault();
      const modal = document.getElementById(opener.getAttribute('data-modal-open'));
      if (modal) modal.classList.add('is-open');
      return;
    }
    const closer = e.target.closest('[data-modal-close]');
    if (closer) {
      const modal = closer.closest('.modal-overlay');
      if (modal) modal.classList.remove('is-open');
      return;
    }
    // Click on the dark overlay itself closes it
    if (e.target.classList.contains('modal-overlay')) {
      e.target.classList.remove('is-open');
    }
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      document.querySelectorAll('.modal-overlay.is-open').forEach((m) => m.classList.remove('is-open'));
    }
  });
})();

/* ---- Toasts ---- */
window.HMS = window.HMS || {};
window.HMS.showToast = function showToast({ type = 'info', title, message, duration = 4000 }) {
  const stack = document.querySelector('.toast-stack');
  if (!stack) return;

  const icons = { success: 'bi-check-circle-fill', warning: 'bi-exclamation-triangle-fill', danger: 'bi-x-circle-fill', info: 'bi-info-circle-fill' };
  const toast = document.createElement('div');
  toast.className = `toast-custom toast-${type}`;
  toast.innerHTML = `
    <i class="bi ${icons[type] || icons.info} toast-icon"></i>
    <div>
      ${title ? `<div class="alert-title">${title}</div>` : ''}
      <div class="text-secondary-custom text-small">${message || ''}</div>
    </div>
    <i class="bi bi-x toast-close" role="button" aria-label="Dismiss"></i>
  `;
  stack.appendChild(toast);

  const remove = () => toast.remove();
  toast.querySelector('.toast-close').addEventListener('click', remove);
  if (duration) setTimeout(remove, duration);
};

/* Demo toast triggers, e.g. <button data-toast-demo="success"> */
document.addEventListener('click', (e) => {
  const btn = e.target.closest('[data-toast-demo]');
  if (!btn) return;
  const type = btn.getAttribute('data-toast-demo');
  const copy = {
    success: { title: 'Saved', message: 'Changes were saved successfully.' },
    warning: { title: 'Check the form', message: 'One field needs your attention.' },
    danger: { title: 'Something went wrong', message: 'The request could not be completed.' },
    info: { title: 'Heads up', message: 'A new update is available.' },
  }[type];
  window.HMS.showToast({ type, ...copy });
});
