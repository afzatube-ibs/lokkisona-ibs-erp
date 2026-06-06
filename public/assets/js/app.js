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

    /* Apply saved theme on load (also done inline in <head> to prevent flash).
       If nothing saved, follow the OS preference. */
    try {
        var saved = localStorage.getItem(THEME_KEY);
        if (saved) {
            applyTheme(saved);
        } else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            applyTheme(DARK_VALUE);
        }
    } catch (e) { /* storage blocked */ }

    /* ── Dispatch batch selection summary ── */
    document.querySelectorAll('.js-dispatch-batch-form').forEach(function (form) {
        var summary = form.querySelector('.js-dispatch-batch-summary');
        var selects = form.querySelectorAll('.js-dispatch-order-select');

        function refreshDispatchSummary() {
            if (!summary) {
                return;
            }
            var orderCount = 0;
            var totalQty = 0;
            var totalCost = 0;
            var couriers = {};
            selects.forEach(function (checkbox) {
                if (!checkbox.checked) {
                    return;
                }
                var row = checkbox.closest('.js-dispatch-order-row');
                if (!row) {
                    return;
                }
                orderCount += 1;
                totalQty += parseInt(row.getAttribute('data-qty') || '0', 10) || 0;
                totalCost += parseFloat(row.getAttribute('data-cost') || '0') || 0;
                var courier = (row.getAttribute('data-courier') || '').trim();
                if (courier !== '') {
                    couriers[courier] = true;
                }
            });
            var courierLabel = Object.keys(couriers).join(', ');
            if (courierLabel === '') {
                courierLabel = 'not selected';
            }
            summary.textContent = 'Batch summary: ' + orderCount + ' orders · ' + totalQty + ' qty · '
                + totalCost.toFixed(2) + ' product cost · courier: ' + courierLabel;
        }

        selects.forEach(function (checkbox) {
            checkbox.addEventListener('change', refreshDispatchSummary);
        });
        refreshDispatchSummary();
    });

    /* ── Workflow action confirmation ── */
    document.querySelectorAll('.js-dispatch-batch-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            var label = form.getAttribute('data-confirm-label') || 'this dispatch batch';
            var confirmedField = form.querySelector('.js-batch-confirmed');
            if (!confirmedField || confirmedField.value === '1') {
                return;
            }
            e.preventDefault();
            if (window.confirm('Confirm: ' + label + '?')) {
                confirmedField.value = '1';
                form.submit();
            }
        });
    });

    document.querySelectorAll('.js-workflow-action-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            var label = form.getAttribute('data-confirm-label') || 'this action';
            var confirmedField = form.querySelector('.js-action-confirmed');
            if (!confirmedField || confirmedField.value === '1') {
                return;
            }
            e.preventDefault();
            if (window.confirm('Confirm workflow action: ' + label + '?')) {
                confirmedField.value = '1';
                form.submit();
            }
        });
    });

    document.querySelectorAll('.js-return-receive-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            var label = form.getAttribute('data-confirm-label') || 'Return Received';
            var confirmedField = form.querySelector('.js-receive-confirmed');
            if (!confirmedField || confirmedField.value === '1') {
                return;
            }
            e.preventDefault();
            if (window.confirm('Confirm: ' + label + '?')) {
                confirmedField.value = '1';
                form.submit();
            }
        });
    });

}());
