/* ── Unwinded Admin JS ───────────────────────────────────────────── */
(function () {
  'use strict';

  // ── Sidebar toggle (mobile) ──────────────────────────────────────
  const sidebar = document.querySelector('.admin-sidebar');
  const toggle  = document.querySelector('.sidebar-toggle');
  const close   = document.querySelector('.sidebar-close');
  const overlay = document.createElement('div');

  overlay.className = 'sidebar-overlay';
  overlay.style.cssText = [
    'display:none', 'position:fixed', 'inset:0',
    'background:rgba(0,0,0,.5)', 'z-index:199', 'cursor:pointer',
  ].join(';');
  document.body.appendChild(overlay);

  function openSidebar() {
    sidebar && sidebar.classList.add('is-open');
    overlay.style.display = 'block';
    document.body.style.overflow = 'hidden';
  }

  function closeSidebar() {
    sidebar && sidebar.classList.remove('is-open');
    overlay.style.display = 'none';
    document.body.style.overflow = '';
  }

  toggle  && toggle.addEventListener('click', openSidebar);
  close   && close.addEventListener('click', closeSidebar);
  overlay.addEventListener('click', closeSidebar);

  // Close on Escape
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeSidebar();
  });

  // ── Alert dismiss ────────────────────────────────────────────────
  document.addEventListener('click', function (e) {
    const btn = e.target.closest('.alert__close');
    if (!btn) return;
    const alert = btn.closest('.alert');
    if (alert) {
      alert.style.transition = 'opacity .2s';
      alert.style.opacity = '0';
      setTimeout(() => alert.remove(), 200);
    }
  });

  // ── Auto-dismiss flash alerts after 5 s ─────────────────────────
  document.querySelectorAll('.alert[data-auto-dismiss]').forEach(function (el) {
    setTimeout(function () {
      el.style.transition = 'opacity .4s';
      el.style.opacity = '0';
      setTimeout(() => el.remove(), 400);
    }, 5000);
  });

  // ── Confirm-on-delete forms ──────────────────────────────────────
  document.addEventListener('submit', function (e) {
    const form = e.target.closest('form[data-confirm]');
    if (!form) return;
    const msg = form.dataset.confirm || 'Are you sure?';
    if (!window.confirm(msg)) e.preventDefault();
  });

  // ── Confirm-on-click links/buttons ───────────────────────────────
  document.addEventListener('click', function (e) {
    const el = e.target.closest('[data-confirm]');
    if (!el || el.tagName === 'FORM') return;
    const msg = el.dataset.confirm || 'Are you sure?';
    if (!window.confirm(msg)) e.preventDefault();
  });

})();
