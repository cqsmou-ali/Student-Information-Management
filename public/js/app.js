// No inline scripts, so the Content-Security-Policy stays strict.
(function () {
  // Mobile sidebar toggle
  var btn = document.getElementById('menuToggle');
  var bar = document.getElementById('sidebar');
  if (btn && bar) {
    btn.addEventListener('click', function () {
      var open = bar.classList.toggle('open');
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }
  // Show / hide password buttons
  document.querySelectorAll('.pw-toggle').forEach(function (b) {
    b.addEventListener('click', function () {
      var input = document.getElementById(b.getAttribute('data-target'));
      if (!input) return;
      var show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      b.textContent = show ? 'Hide' : 'Show';
      b.setAttribute('aria-label', (show ? 'Hide' : 'Show') + ' password');
    });
  });
  // Print buttons (no inline onclick because of the CSP)
  document.querySelectorAll('[data-print]').forEach(function (b) {
    b.addEventListener('click', function () { window.print(); });
  });
})();
