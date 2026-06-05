(function () {
    'use strict';

    /* ── Sidebar toggle ── */
    var toggle  = document.getElementById('sidebarToggle');
    var sidebar = document.getElementById('sidebar');

    if (toggle && sidebar) {
        toggle.addEventListener('click', function () {
            sidebar.classList.toggle('open');
        });

        document.addEventListener('click', function (e) {
            if (
                sidebar.classList.contains('open') &&
                !sidebar.contains(e.target) &&
                !toggle.contains(e.target)
            ) {
                sidebar.classList.remove('open');
            }
        });
    }

    /* ── Dark / light theme toggle ── */
    var THEME_KEY   = 'ibs-theme';
    var DARK_VALUE  = 'dark';
    var themeBtn    = document.getElementById('themeToggle');

    function applyTheme(theme) {
        if (theme === DARK_VALUE) {
            document.documentElement.setAttribute('data-theme', DARK_VALUE);
        } else {
            document.documentElement.removeAttribute('data-theme');
        }
    }

    if (themeBtn) {
        themeBtn.addEventListener('click', function () {
            var current = document.documentElement.getAttribute('data-theme');
            var next    = current === DARK_VALUE ? 'light' : DARK_VALUE;
            applyTheme(next);
            try {
                localStorage.setItem(THEME_KEY, next);
            } catch (e) { /* storage blocked */ }
        });
    }

    /* Apply saved theme on load (also done inline in <head> to prevent flash) */
    try {
        var saved = localStorage.getItem(THEME_KEY);
        if (saved) { applyTheme(saved); }
    } catch (e) { /* storage blocked */ }

}());
