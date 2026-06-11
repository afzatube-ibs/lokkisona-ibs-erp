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

    /* ── Sidebar tier expand/collapse persistence ── */
    var NAV_TIER_PREFIX = 'ibs-nav-';

    document.querySelectorAll('details[data-nav-tier]').forEach(function (detailsEl) {
        var key = detailsEl.getAttribute('data-nav-tier');
        if (!key) {
            return;
        }

        var storageKey = NAV_TIER_PREFIX + key;
        var hasActiveChild = !!detailsEl.querySelector('.nav-item.active, .nav-item-button.active');

        try {
            var saved = localStorage.getItem(storageKey);
            var defaultOpen = detailsEl.getAttribute('data-nav-default-open') === '1';
            if (key === 'future-plans') {
                detailsEl.open = hasActiveChild;
            } else if (saved === 'open') {
                detailsEl.open = true;
            } else if (saved === 'closed' && !hasActiveChild) {
                detailsEl.open = false;
            } else if (defaultOpen && saved === null && !hasActiveChild) {
                detailsEl.open = true;
            }
        } catch (e) { /* storage blocked */ }

        detailsEl.addEventListener('toggle', function () {
            try {
                localStorage.setItem(storageKey, detailsEl.open ? 'open' : 'closed');
            } catch (e) { /* storage blocked */ }
        });
    });

    /* ── Dispatch batch selection summary ── */
    document.querySelectorAll('.js-dispatch-batch-form').forEach(function (form) {
        var summary = form.querySelector('.js-dispatch-batch-summary');
        var missingWarning = form.querySelector('.js-dispatch-missing-cost-warning');
        var missingOrderNoWarning = form.querySelector('.js-dispatch-missing-order-no-warning');
        var mixedSourceWarning = form.querySelector('.js-dispatch-mixed-source-warning');
        var submitBtn = form.querySelector('.js-dispatch-submit-btn');
        var selects = form.querySelectorAll('.js-dispatch-order-select');

        function refreshDispatchSummary() {
            if (!summary) {
                return;
            }
            var orderCount = 0;
            var totalQty = 0;
            var totalCost = 0;
            var missingCount = 0;
            var missingOrderNoCount = 0;
            var couriers = {};
            var businessSources = {};
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
                if (row.getAttribute('data-missing-cost') === '1') {
                    missingCount += 1;
                }
                if (row.getAttribute('data-missing-order-no') === '1') {
                    missingOrderNoCount += 1;
                }
                var courier = (row.getAttribute('data-courier') || '').trim();
                if (courier !== '') {
                    couriers[courier] = true;
                }
                var sourceKey = (row.getAttribute('data-business-source') || '0').trim();
                businessSources[sourceKey] = true;
            });
            var courierLabel = Object.keys(couriers).join(', ');
            if (courierLabel === '') {
                courierLabel = 'not selected';
            }
            var sourceCount = Object.keys(businessSources).length;
            summary.textContent = 'Batch summary: ' + orderCount + ' orders · ' + totalQty + ' qty · '
                + totalCost.toFixed(2) + ' cost snapshot · ' + sourceCount + ' business source(s)';

            var mixedSources = sourceCount > 1;
            if (mixedSourceWarning) {
                if (mixedSources) {
                    mixedSourceWarning.style.display = 'block';
                    mixedSourceWarning.textContent = 'Cannot create statement: selected orders span multiple business sources. Select orders from one source only.';
                } else {
                    mixedSourceWarning.style.display = 'none';
                    mixedSourceWarning.textContent = '';
                }
            }

            if (missingWarning) {
                if (missingCount > 0) {
                    var itemWord = missingCount === 1 ? 'order' : 'orders';
                    missingWarning.style.display = 'block';
                    missingWarning.textContent = 'Cannot create statement: missing cost — update Products first ('
                        + missingCount + ' selected ' + itemWord + ').';
                } else {
                    missingWarning.style.display = 'none';
                    missingWarning.textContent = '';
                }
            }
            if (missingOrderNoWarning) {
                if (missingOrderNoCount > 0) {
                    var orderWord = missingOrderNoCount === 1 ? 'order' : 'orders';
                    missingOrderNoWarning.style.display = 'block';
                    missingOrderNoWarning.textContent = 'Cannot create statement: '
                        + missingOrderNoCount + ' selected ' + orderWord
                        + ' missing order number (Lokkisona source no or sales invoice no).';
                } else {
                    missingOrderNoWarning.style.display = 'none';
                    missingOrderNoWarning.textContent = '';
                }
            }
            if (submitBtn) {
                submitBtn.disabled = missingCount > 0 || missingOrderNoCount > 0 || mixedSources;
            }
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

    document.querySelectorAll('.js-return-report-form').forEach(function (form) {
        var summary = form.querySelector('.js-return-report-summary');
        var missingCostWarning = form.querySelector('.js-return-missing-cost-warning');
        var missingReasonWarning = form.querySelector('.js-return-missing-reason-warning');
        var missingOrderNoWarning = form.querySelector('.js-return-missing-order-no-warning');
        var mixedSourceWarning = form.querySelector('.js-return-mixed-source-warning');
        var mixedSupplierWarning = form.querySelector('.js-return-mixed-supplier-warning');
        var submitBtn = form.querySelector('.js-return-report-submit-btn');
        var selects = form.querySelectorAll('.js-return-report-select');
        var selectAll = form.querySelector('.js-return-select-all');

        function refreshReturnSummary() {
            if (!summary) {
                return;
            }
            var returnCount = 0;
            var totalQty = 0;
            var totalCost = 0;
            var missingCostCount = 0;
            var missingReasonCount = 0;
            var missingOrderNoCount = 0;
            var businessSources = {};
            var suppliers = {};
            selects.forEach(function (checkbox) {
                if (!checkbox.checked) {
                    return;
                }
                var row = checkbox.closest('.js-return-report-row');
                if (!row) {
                    return;
                }
                returnCount += 1;
                totalQty += parseInt(row.getAttribute('data-qty') || '0', 10) || 0;
                totalCost += parseFloat(row.getAttribute('data-cost') || '0') || 0;
                if (row.getAttribute('data-missing-cost') === '1') {
                    missingCostCount += 1;
                }
                if (row.getAttribute('data-missing-reason') === '1') {
                    missingReasonCount += 1;
                }
                if (row.getAttribute('data-missing-order-no') === '1') {
                    missingOrderNoCount += 1;
                }
                var sourceKey = (row.getAttribute('data-business-source') || '0').trim();
                businessSources[sourceKey] = true;
                var supplierKey = (row.getAttribute('data-supplier') || '0').trim();
                if (supplierKey !== '0') {
                    suppliers[supplierKey] = true;
                }
            });
            var sourceCount = Object.keys(businessSources).length;
            var supplierCount = Object.keys(suppliers).length;
            summary.textContent = 'Report summary: ' + returnCount + ' returns · ' + totalQty + ' qty · '
                + totalCost.toFixed(2) + ' return amount · ' + sourceCount + ' business source(s)';

            var mixedSources = sourceCount > 1;
            var mixedSuppliers = supplierCount > 1;
            if (mixedSupplierWarning) {
                if (mixedSuppliers) {
                    mixedSupplierWarning.style.display = 'block';
                    mixedSupplierWarning.textContent = 'Cannot create report: selected returns span multiple suppliers. Select returns from one supplier only.';
                } else {
                    mixedSupplierWarning.style.display = 'none';
                    mixedSupplierWarning.textContent = '';
                }
            }
            if (mixedSourceWarning) {
                if (mixedSources) {
                    mixedSourceWarning.style.display = 'block';
                    mixedSourceWarning.textContent = 'Cannot create report: selected returns span multiple business sources. Select returns from one source only.';
                } else {
                    mixedSourceWarning.style.display = 'none';
                    mixedSourceWarning.textContent = '';
                }
            }
            if (missingCostWarning) {
                if (missingCostCount > 0) {
                    missingCostWarning.style.display = 'block';
                    missingCostWarning.textContent = 'Cannot create report: missing cost — update Products first (' + missingCostCount + ' selected).';
                } else {
                    missingCostWarning.style.display = 'none';
                    missingCostWarning.textContent = '';
                }
            }
            if (missingReasonWarning) {
                if (missingReasonCount > 0) {
                    missingReasonWarning.style.display = 'block';
                    missingReasonWarning.textContent = 'Cannot create report: missing return reason on selected rows.';
                } else {
                    missingReasonWarning.style.display = 'none';
                    missingReasonWarning.textContent = '';
                }
            }
            if (missingOrderNoWarning) {
                if (missingOrderNoCount > 0) {
                    missingOrderNoWarning.style.display = 'block';
                    missingOrderNoWarning.textContent = 'Cannot create report: selected returns missing order number.';
                } else {
                    missingOrderNoWarning.style.display = 'none';
                    missingOrderNoWarning.textContent = '';
                }
            }
            if (submitBtn) {
                submitBtn.disabled = missingCostCount > 0 || missingReasonCount > 0 || missingOrderNoCount > 0 || mixedSources || mixedSuppliers;
            }
        }

        if (selectAll) {
            selectAll.addEventListener('change', function () {
                selects.forEach(function (checkbox) {
                    if (!checkbox.disabled) {
                        checkbox.checked = selectAll.checked;
                    }
                });
                refreshReturnSummary();
            });
        }

        selects.forEach(function (checkbox) {
            checkbox.addEventListener('change', refreshReturnSummary);
        });
        refreshReturnSummary();

        form.addEventListener('submit', function (e) {
            var label = form.getAttribute('data-confirm-label') || 'this return report';
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
