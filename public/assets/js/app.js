/* ── Unwinded Public JS ──────────────────────────────────────────── */
(function () {
  'use strict';

  // ── Mobile nav toggle ────────────────────────────────────────────
  const menuBtn = document.querySelector('.nav-menu-btn');
  const navLinks = document.querySelector('.site-nav__links');

  if (menuBtn && navLinks) {
    menuBtn.addEventListener('click', function () {
      const open = navLinks.classList.toggle('is-open');
      menuBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
    });

    // Close on outside click
    document.addEventListener('click', function (e) {
      if (!e.target.closest('.site-nav')) {
        navLinks.classList.remove('is-open');
        menuBtn.setAttribute('aria-expanded', 'false');
      }
    });
  }

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

})();
