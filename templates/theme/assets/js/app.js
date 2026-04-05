(function () {
  'use strict';

  function syncToggleState(btn, input) {
    var visible = input.type === 'text';
    btn.classList.toggle('password-toggle--revealed', visible);
    btn.setAttribute('aria-pressed', visible ? 'true' : 'false');
    btn.setAttribute('aria-label', visible ? 'Sembunyikan password' : 'Tampilkan password');
  }

  function bindPasswordToggle(btn) {
    var targetId = btn.getAttribute('data-password-target');
    if (!targetId) return;
    var input = document.getElementById(targetId);
    if (!input || (input.type !== 'password' && input.type !== 'text')) return;

    btn.addEventListener('click', function () {
      input.type = input.type === 'password' ? 'text' : 'password';
      syncToggleState(btn, input);
    });

    syncToggleState(btn, input);
  }

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.password-toggle[data-password-target]').forEach(bindPasswordToggle);
  });
})();
