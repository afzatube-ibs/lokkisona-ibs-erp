(function () {
    'use strict';

    var payloadEl = document.getElementById('productCatalogPayload');
    var historyEl = document.getElementById('productHistoryPayload');
    var configEl = document.getElementById('productControlConfig');
    if (!payloadEl) {
        return;
    }

    var workspaces = {};
    var historyByProduct = {};
    var pageConfig = { supplierNoteReady: false };
    try {
        workspaces = JSON.parse(payloadEl.textContent || '{}');
    } catch (e) {
        workspaces = {};
    }
    if (historyEl) {
        try {
            historyByProduct = JSON.parse(historyEl.textContent || '{}');
        } catch (e2) {
            historyByProduct = {};
        }
    }
    if (configEl) {
        try {
            pageConfig = JSON.parse(configEl.textContent || '{}');
        } catch (e3) {
            pageConfig = { supplierNoteReady: false };
        }
    }

    var modal = document.getElementById('productControlCenterModal');
    var form = document.getElementById('productControlCenterForm');
    var tableBody = document.getElementById('productCatalogTableBody');

    function openModal(id) {
        var target = document.getElementById(id);
        if (!target) {
            return;
        }
        target.hidden = false;
        target.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
    }

    function closeModal(id) {
        var target = document.getElementById(id);
        if (!target) {
            return;
        }
        target.hidden = true;
        target.setAttribute('aria-hidden', 'true');
        if (!document.querySelector('.modal-overlay:not([hidden])')) {
            document.body.classList.remove('modal-open');
        }
    }

    function setText(id, value) {
        var el = document.getElementById(id);
        if (el) {
            el.textContent = value || '—';
        }
    }

    function setInput(id, value) {
        var el = document.getElementById(id);
        if (el) {
            el.value = value === null || value === undefined ? '' : String(value);
        }
    }

    function setSelect(id, value) {
        var el = document.getElementById(id);
        if (el) {
            el.value = value === null || value === undefined ? '' : String(value);
        }
    }

    function renderBadges(containerId, badges) {
        var container = document.getElementById(containerId);
        if (!container) {
            return;
        }
        container.innerHTML = '';
        (badges || []).forEach(function (badge) {
            var span = document.createElement('span');
            span.className = 'badge badge-' + (badge.class || 'muted');
            span.textContent = badge.label || '';
            container.appendChild(span);
        });
    }

    function renderVariantAccordion(variants) {
        var root = document.getElementById('pccVariantAccordion');
        if (!root) {
            return;
        }
        root.innerHTML = '';
        if (!variants.length) {
            return;
        }

        var details = document.createElement('details');
        details.className = 'pcc-variant-accordion';
        details.open = false;

        var summary = document.createElement('summary');
        summary.className = 'pcc-variant-accordion-summary';
        summary.textContent = 'Options (' + variants.length + ') — expand to edit supplier fields';
        details.appendChild(summary);

        var tableWrap = document.createElement('div');
        tableWrap.className = 'table-scroll';

        var table = document.createElement('table');
        table.className = 'data-table pcc-variant-table';

        var noteHeader = pageConfig.supplierNoteReady ? '<th>Supplier note</th>' : '';
        table.innerHTML =
            '<thead><tr>' +
            '<th>Image</th><th>Option</th><th>Value</th><th>OC model</th><th>OC stock</th>' +
            '<th>Vendor model</th><th>Cost</th><th>Vendor stock</th>' +
            noteHeader + '<th>Health</th>' +
            '</tr></thead><tbody id="pccVariantRows"></tbody>';

        tableWrap.appendChild(table);
        details.appendChild(tableWrap);
        root.appendChild(details);

        var tbody = table.querySelector('#pccVariantRows');
        variants.forEach(function (variant, index) {
            var tr = document.createElement('tr');
            tr.dataset.variantIndex = String(index);
            tr.dataset.optionName = variant.option_name || '';
            tr.dataset.optionValue = variant.option_value || '';
            tr.dataset.variantId = String(variant.product_variant_id || '');

            var imageCell = variant.image_path
                ? '<div class="pcc-thumb"><img src="' + escapeAttr(variant.image_path) + '" alt=""></div>'
                : '<span class="pcc-thumb-empty">—</span>';
            var noteCell = '';
            if (pageConfig.supplierNoteReady) {
                noteCell = '<td><input type="text" class="form-input pcc-inline-input" data-field="supplier_note" value="' + escapeAttr(variant.supplier_note || '') + '"></td>';
            }

            tr.innerHTML =
                '<td>' + imageCell + '</td>' +
                '<td>' + escapeHtml(variant.option_name || '—') + '</td>' +
                '<td>' + escapeHtml(variant.option_value || '—') + '</td>' +
                '<td><code>' + escapeHtml(variant.source_model || '—') + '</code></td>' +
                '<td class="pcc-readonly-cell">' + escapeHtml(variant.source_stock === null || variant.source_stock === '' ? '—' : String(variant.source_stock)) + '</td>' +
                '<td><input type="text" class="form-input pcc-inline-input" data-field="supplier_model" value="' + escapeAttr(variant.supplier_model || '') + '"></td>' +
                '<td><input type="number" step="0.01" min="0" class="form-input pcc-inline-input" data-field="product_cost" value="' + escapeAttr(variant.product_cost || '') + '"></td>' +
                '<td><input type="number" min="0" class="form-input pcc-inline-input" data-field="vendor_stock" value="' + escapeAttr(variant.vendor_stock || 0) + '"></td>' +
                noteCell +
                '<td><span class="pcc-health-text">' + escapeHtml(variant.health || '—') + '</span></td>';

            tbody.appendChild(tr);
        });
    }

    function renderHistory(productId) {
        var tbody = document.getElementById('pccHistoryRows');
        if (!tbody) {
            return;
        }
        var rowsHistory = historyByProduct[String(productId)] || [];
        if (!rowsHistory.length) {
            tbody.innerHTML = '<tr><td colspan="5" class="page-description">No cost/stock history for this product yet.</td></tr>';
            return;
        }
        tbody.innerHTML = rowsHistory.map(function (row) {
            var oldCost = row.old_cost !== undefined && row.old_cost !== '' ? row.old_cost : '—';
            var newCost = row.new_cost !== undefined && row.new_cost !== '' ? row.new_cost : '—';
            var oldStock = row.old_stock !== undefined && row.old_stock !== '' ? row.old_stock : '—';
            var newStock = row.new_stock !== undefined && row.new_stock !== '' ? row.new_stock : '—';
            return '<tr>' +
                '<td>' + escapeHtml(row.variant_label || 'Product level') + '</td>' +
                '<td>' + escapeHtml(oldCost + ' → ' + newCost) + '</td>' +
                '<td>' + escapeHtml(oldStock + ' → ' + newStock) + '</td>' +
                '<td>' + escapeHtml(row.note || '—') + '</td>' +
                '<td>' + escapeHtml(row.created_at || '—') + '</td>' +
                '</tr>';
        }).join('');
    }

    function openWorkspace(productId) {
        var workspace = workspaces[String(productId)];
        if (!workspace || !form) {
            return;
        }

        setInput('pccProductId', workspace.product_id);
        setInput('pccBusinessSourceId', workspace.business_source_id || '');
        setInput('pccErpProductIdDisplay', '#' + workspace.product_id);
        setInput('pccProductNameDisplay', workspace.product_name || '—');
        setInput('pccSourceModelDisplay', workspace.source_model || '—');
        setInput('pccOwnerStockReadonlyTop', workspace.source_stock === null || workspace.source_stock === '' ? '—' : workspace.source_stock);
        setInput('pccSupplierModel', workspace.supplier_model);
        setInput('pccCategory', workspace.supplier_product_category);
        setInput('pccSourceProductIdDisplay', workspace.source_product_id || '—');
        setInput('pccLowWarning', workspace.low_warning_threshold);
        setSelect('pccStatus', workspace.status || 'active');
        if (document.getElementById('pccSupplierId')) {
            setSelect('pccSupplierId', workspace.supplier_id || '');
        }
        if (document.getElementById('pccSupplierNote')) {
            setInput('pccSupplierNote', workspace.supplier_note || '');
        }

        setText('pccSyncLastSynced', workspace.last_synced_at || '—');
        setText('pccSyncSourceId', workspace.source_product_id || '—');
        setText('pccSyncStatus', workspace.sync_status || '—');
        renderBadges('pccCompletenessBadges', workspace.badges || []);

        setText('pccModalSubtitle', '#' + workspace.product_id + ' • ' + (workspace.completeness === 'ready' ? 'Ready' : 'Needs Work') + ' • controlled product workspace');
        setText('pccProductTitle', workspace.product_name);
        var typeLabel = workspace.type === 'variable' ? 'Variable' : 'Simple';
        if (workspace.no_options_synced) {
            typeLabel += ' · No option synced';
        }
        setText('pccProductMeta', 'Product ID #' + workspace.product_id + ' • ' + typeLabel + ' • OC model ' + (workspace.source_model || '—'));

        var image = document.getElementById('pccProductImage');
        var placeholder = document.getElementById('pccImagePlaceholder');
        if (image && placeholder) {
            if (workspace.image_path) {
                image.src = workspace.image_path;
                image.hidden = false;
                placeholder.hidden = true;
            } else {
                image.hidden = true;
                placeholder.hidden = false;
            }
        }

        var simpleFields = document.getElementById('pccSimpleProductFields');
        var variantSection = document.getElementById('pccVariantSection');
        var noOptionsNotice = document.getElementById('pccNoOptionsNotice');

        if (workspace.type === 'variable') {
            if (simpleFields) {
                simpleFields.hidden = true;
            }
            if (variantSection) {
                variantSection.hidden = false;
            }
            var hasVariantLines = (workspace.variants || []).length > 0;
            if (noOptionsNotice) {
                noOptionsNotice.hidden = !workspace.no_options_synced;
            }
            renderVariantAccordion(workspace.variants || []);
        } else {
            if (simpleFields) {
                simpleFields.hidden = false;
            }
            if (variantSection) {
                variantSection.hidden = true;
            }
            if (noOptionsNotice) {
                noOptionsNotice.hidden = true;
            }
            setInput('pccProductCost', workspace.product_cost);
            setInput('pccProductVendorStock', workspace.vendor_stock);
        }

        renderHistory(workspace.product_id);
        activateTab('details');
        openModal('productControlCenterModal');
    }

    function activateTab(name) {
        document.querySelectorAll('.pcc-tab').forEach(function (tab) {
            var active = tab.getAttribute('data-pcc-tab') === name;
            tab.classList.toggle('is-active', active);
            tab.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        document.querySelectorAll('.pcc-tab-panel').forEach(function (panel) {
            var active = panel.getAttribute('data-pcc-panel') === name;
            panel.classList.toggle('is-active', active);
            panel.hidden = !active;
        });
    }

    function collectVariantsJson() {
        var tbody = document.getElementById('pccVariantRows');
        if (!tbody) {
            return '[]';
        }
        var payload = [];
        tbody.querySelectorAll('tr').forEach(function (tr) {
            var row = {
                product_variant_id: parseInt(tr.dataset.variantId || '0', 10),
                option_name: tr.dataset.optionName || '',
                option_value: tr.dataset.optionValue || '',
                supplier_model: (tr.querySelector('[data-field="supplier_model"]') || {}).value || '',
                product_cost: (tr.querySelector('[data-field="product_cost"]') || {}).value || '',
                vendor_stock: (tr.querySelector('[data-field="vendor_stock"]') || {}).value || ''
            };
            if (pageConfig.supplierNoteReady) {
                row.supplier_note = (tr.querySelector('[data-field="supplier_note"]') || {}).value || '';
            }
            payload.push(row);
        });
        return JSON.stringify(payload);
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function escapeAttr(value) {
        return escapeHtml(value).replace(/'/g, '&#39;');
    }

    if (tableBody) {
        tableBody.addEventListener('click', function (event) {
            var row = event.target.closest('[data-product-row]');
            if (!row) {
                return;
            }
            if (event.target.closest('a, button, input, select, textarea, summary')) {
                return;
            }
            openWorkspace(row.getAttribute('data-product-id'));
        });
    }

    document.querySelectorAll('.pcc-tab').forEach(function (tab) {
        tab.addEventListener('click', function () {
            activateTab(tab.getAttribute('data-pcc-tab'));
        });
    });

    if (form) {
        form.addEventListener('submit', function () {
            var variantsInput = document.getElementById('pccVariantsJson');
            if (variantsInput) {
                variantsInput.value = collectVariantsJson();
            }
        });
    }

    document.querySelectorAll('[data-open-product-workspace]').forEach(function (button) {
        button.addEventListener('click', function () {
            openWorkspace(button.getAttribute('data-open-product-workspace'));
        });
    });

    document.querySelectorAll('[data-modal-close="productControlCenterModal"]').forEach(function (button) {
        button.addEventListener('click', function () {
            closeModal('productControlCenterModal');
        });
    });

    if (modal) {
        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                closeModal('productControlCenterModal');
            }
        });
    }

    var openParam = new URLSearchParams(window.location.search).get('open');
    if (openParam && workspaces[String(openParam)]) {
        openWorkspace(openParam);
    }

    window.productControlOpenWorkspace = openWorkspace;
})();
