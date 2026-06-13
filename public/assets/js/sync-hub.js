(function () {
    'use strict';

    var page = document.body.classList.contains('sync-hub-page') ? document.body : document.querySelector('.sync-hub-page');
    if (!page) {
        return;
    }

    var tabs = page.querySelectorAll('[data-sync-hub-tab]');
    var panels = page.querySelectorAll('[data-sync-hub-panel]');
    var allowedTabs = ['connection', 'mapping', 'products', 'sync'];

    function normalizeTab(tab) {
        tab = (tab || 'connection').toLowerCase();
        return allowedTabs.indexOf(tab) >= 0 ? tab : 'connection';
    }

    function updateMappingAlertVisibility(tab) {
        var alert = page.querySelector('[data-sync-hub-alert-tabs]');
        if (!alert) {
            return;
        }
        var allowed = (alert.getAttribute('data-sync-hub-alert-tabs') || '')
            .split(',')
            .map(function (item) { return item.trim(); })
            .filter(Boolean);
        alert.hidden = allowed.indexOf(tab) < 0;
    }

    function activateTab(tab, pushState) {
        tab = normalizeTab(tab);
        tabs.forEach(function (btn) {
            var isActive = btn.getAttribute('data-sync-hub-tab') === tab;
            btn.classList.toggle('is-active', isActive);
            btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });
        panels.forEach(function (panel) {
            panel.hidden = panel.getAttribute('data-sync-hub-panel') !== tab;
        });
        updateMappingAlertVisibility(tab);
        if (pushState !== false && window.history && window.history.replaceState) {
            var url = new URL(window.location.href);
            url.searchParams.set('tab', tab);
            window.history.replaceState({}, '', url.toString());
        }
    }

    tabs.forEach(function (btn) {
        btn.addEventListener('click', function () {
            activateTab(btn.getAttribute('data-sync-hub-tab'), true);
        });
    });

    var initial = normalizeTab(new URLSearchParams(window.location.search).get('tab'));
    activateTab(initial, false);

    var searchInput = page.querySelector('[data-sync-hub-status-search]');
    var showAllToggle = page.querySelector('[data-sync-hub-show-all]');
    var statusTable = page.querySelector('[data-sync-hub-status-table]');

    function filterStatusRows() {
        if (!statusTable) {
            return;
        }
        var query = searchInput ? searchInput.value.trim().toLowerCase() : '';
        var showAll = showAllToggle ? showAllToggle.checked : false;
        statusTable.querySelectorAll('tbody tr').forEach(function (row) {
            var isQueue = row.getAttribute('data-queue-row') === '1';
            var text = (row.getAttribute('data-search') || row.textContent || '').toLowerCase();
            var matchesSearch = query === '' || text.indexOf(query) >= 0;
            var matchesQueue = showAll || isQueue;
            row.hidden = !(matchesSearch && matchesQueue);
        });
    }

    if (searchInput) {
        searchInput.addEventListener('input', filterStatusRows);
    }
    if (showAllToggle) {
        showAllToggle.addEventListener('change', filterStatusRows);
    }
    filterStatusRows();

    var productConfirm = page.querySelector('[data-sync-hub-product-confirm]');
    var productSubmit = page.querySelector('[data-sync-hub-product-submit]');

    function syncProductImportControls() {
        if (!productConfirm || !productSubmit) {
            return;
        }
        productSubmit.disabled = productConfirm.disabled || !productConfirm.checked;
    }

    if (productConfirm) {
        productConfirm.addEventListener('change', syncProductImportControls);
        syncProductImportControls();
    }

    var connectionForm = page.querySelector('#sync-hub-connection-form');
    var dirtyBar = page.querySelector('[data-sync-hub-dirty-bar]');
    var discardBtn = page.querySelector('[data-sync-hub-discard]');
    var initialSnapshot = '';

    if (connectionForm) {
        initialSnapshot = connectionForm.innerHTML;
        connectionForm.addEventListener('input', function () {
            if (!dirtyBar) {
                return;
            }
            dirtyBar.hidden = false;
        });
        connectionForm.addEventListener('change', function () {
            if (!dirtyBar) {
                return;
            }
            dirtyBar.hidden = false;
        });
    }

    if (discardBtn && connectionForm) {
        discardBtn.addEventListener('click', function () {
            connectionForm.innerHTML = initialSnapshot;
            if (dirtyBar) {
                dirtyBar.hidden = true;
            }
            var mode = document.getElementById('source_mode');
            var url = document.getElementById('api_base_url');
            if (mode && url) {
                mode.dispatchEvent(new Event('change'));
            }
        });
    }

    var mode = document.getElementById('source_mode');
    var url = document.getElementById('api_base_url');
    if (mode && url) {
        mode.addEventListener('change', function () {
            var v = mode.value;
            if (v === 'staging' && mode.dataset.stagingUrl) {
                url.value = mode.dataset.stagingUrl;
            } else if (v === 'live' && mode.dataset.liveUrl) {
                url.value = mode.dataset.liveUrl;
            } else if (v === 'demo') {
                url.value = '';
            }
        });
    }
})();
