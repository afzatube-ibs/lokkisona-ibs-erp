(function () {
    'use strict';

    var bootstrapEl = document.getElementById('productCatalogBootstrap');
    var modal = document.getElementById('productControlCenterModal');
    if (!bootstrapEl || !modal) {
        return;
    }

    var bootstrap = {};
    try {
        bootstrap = JSON.parse(bootstrapEl.textContent || '{}');
    } catch (e) {
        bootstrap = {};
    }

    var workspaceUrl = bootstrap.workspaceUrl || '/product-control/workspace';
    var historyUrl = bootstrap.historyUrl || '/product-control/history';
    var workspaces = {};
    var baselines = {};
    var variantState = {};
    var historyByProduct = {};
    var historyLoading = {};
    var isSupplierView = !!bootstrap.isSupplierView;
    var costFieldLabel = isSupplierView ? 'Supplier Sale' : 'Supplier Cost';
    var form = document.getElementById('productControlCenterForm');
    var variantLinesBody = document.getElementById('pccVariantLinesBody');
    var variantSection = document.getElementById('pccVariantSection');
    var noOptionsNotice = document.getElementById('pccNoOptionsNotice');
    var vendorMappingCard = document.getElementById('pccVendorMappingCard');
    var supplierCostField = document.getElementById('pccProductCost');
    var supplierStockField = document.getElementById('pccProductVendorStock');
    var saveBtn = document.getElementById('pccSaveBtn');
    var cancelBtn = document.getElementById('pccCancelBtn');
    var canEditSupplier = !!saveBtn;
    var currentProductId = '';
    var isDirty = false;
    var modalLoadingEl = document.getElementById('pccModalLoading');
    var modalErrorEl = document.getElementById('pccModalError');
    var adjustPopover = document.getElementById('pccAdjustPopover');
    var adjustContext = null;

    var HISTORY_ICON = '<svg class="pcc-history-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>';

    function esc(text) {
        var div = document.createElement('div');
        div.textContent = text == null ? '' : String(text);
        return div.innerHTML;
    }

    function trimVal(value) {
        return value == null ? '' : String(value).trim();
    }

    function displayVal(value) {
        return trimVal(value) === '' ? 'Not set' : String(value);
    }

    function chipHtml(value) {
        if (trimVal(value) === '') {
            return '<span class="pcc-field-chip pcc-field-chip-empty">Not set</span>';
        }
        return '<span class="pcc-field-chip">' + esc(value) + '</span>';
    }

    function imageCellHtml(url) {
        if (url) {
            return '<div class="pcc-thumb-44">'
                + '<img src="' + esc(url) + '" alt="" loading="lazy" '
                + 'onerror="this.parentNode.replaceWith(Object.assign(document.createElement(\'div\'),{className:\'pcc-thumb-44 pcc-thumb-44-empty\',innerHTML:\'<span>No image</span>\'}))">'
                + '</div>';
        }
        return '<div class="pcc-thumb-44 pcc-thumb-44-empty"><span>No image</span></div>';
    }

    function shortHealthLabel(label) {
        var map = {
            'Healthy': 'Ready',
            'Ready': 'Ready',
            'Low Stock': 'Low',
            'Out of Stock': 'Out',
            'Missing Sale': 'Missing Cost',
            'Missing Cost': 'Missing Cost',
            'Missing Model': 'Missing Model',
            'Inactive': 'Inactive',
            'Needs Work': 'Needs Work'
        };
        return map[label] || label || '—';
    }

    function setText(id, value) {
        var el = document.getElementById(id);
        if (el) {
            el.textContent = trimVal(value) === '' ? '—' : String(value);
        }
    }

    function setInput(id, value) {
        var el = document.getElementById(id);
        if (el) {
            el.value = value == null ? '' : String(value);
        }
    }

    function setImage(url) {
        var img = document.getElementById('pccProductImage');
        var placeholder = document.getElementById('pccImagePlaceholder');
        if (!img || !placeholder) {
            return;
        }
        if (url) {
            img.onerror = function () {
                img.hidden = true;
                img.removeAttribute('src');
                placeholder.hidden = false;
            };
            img.src = url;
            img.loading = 'lazy';
            img.hidden = false;
            placeholder.hidden = true;
        } else {
            img.hidden = true;
            img.removeAttribute('src');
            placeholder.hidden = false;
        }
    }

    function showModalLoading(show) {
        if (modalLoadingEl) {
            modalLoadingEl.hidden = !show;
        }
    }

    function showModalError(message) {
        if (modalErrorEl) {
            modalErrorEl.textContent = message || '';
            modalErrorEl.hidden = !message;
        }
    }

    function renderSnapshotHealth(workspace) {
        var el = document.getElementById('pccSnapshotHealth');
        if (!el) {
            return;
        }
        var label = shortHealthLabel(workspace.health_label || '—');
        var cls = workspace.health_class || 'muted';
        el.innerHTML = '<span class="badge badge-sm badge-' + esc(cls) + '">' + esc(label) + '</span>';
    }

    function setDirty(dirty) {
        isDirty = !!dirty;
        if (saveBtn) {
            saveBtn.disabled = !isDirty;
            saveBtn.classList.toggle('pcc-save-dirty', isDirty);
        }
        if (cancelBtn) {
            cancelBtn.hidden = !isDirty;
        }
        if (form) {
            form.classList.toggle('pcc-form-dirty', isDirty);
        }
    }

    function markDirty() {
        setDirty(true);
    }

    function activateTab(tab) {
        document.querySelectorAll('.pcc-tab').forEach(function (btn) {
            var active = btn.getAttribute('data-pcc-tab') === tab;
            btn.classList.toggle('is-active', active);
            btn.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        document.querySelectorAll('.pcc-tab-panel').forEach(function (panel) {
            var active = panel.getAttribute('data-pcc-panel') === tab;
            panel.classList.toggle('is-active', active);
            panel.hidden = !active;
        });
        if (tab === 'history' && currentProductId) {
            loadHistory(currentProductId);
        }
    }

    function renderHistory(productId, variantId) {
        var tbody = document.getElementById('pccHistoryRows');
        if (!tbody) {
            return;
        }
        var rows = historyByProduct[String(productId)] || [];
        if (variantId) {
            rows = rows.filter(function (row) {
                return String(row.product_variant_id || '') === String(variantId);
            });
        }
        if (!rows.length) {
            tbody.innerHTML = '<tr><td colspan="8" class="page-description">No cost/stock history recorded' + (variantId ? ' for this option' : '') + ' yet.</td></tr>';
            return;
        }
        tbody.innerHTML = rows.map(function (row) {
            var change = (row.old_value !== '' && row.old_value != null ? row.old_value : '—')
                + ' → ' + (row.new_value !== '' && row.new_value != null ? row.new_value : '—');
            return '<tr>'
                + '<td>' + esc(row.created_at || '—') + '</td>'
                + '<td>' + esc(row.variant_label || 'Product level') + '</td>'
                + '<td>' + esc(row.field_changed || '—') + '</td>'
                + '<td>' + esc(change) + '</td>'
                + '<td>' + esc(row.change_type || '—') + '</td>'
                + '<td>' + esc(row.delta_amount || '—') + '</td>'
                + '<td>' + esc(row.note || '—') + '</td>'
                + '<td>' + esc(row.user_label || '—') + '</td>'
                + '</tr>';
        }).join('');
    }

    function loadHistory(productId, variantId) {
        var cacheKey = String(productId);
        if (historyByProduct[cacheKey]) {
            renderHistory(productId, variantId);
            return;
        }
        if (historyLoading[cacheKey]) {
            return;
        }
        historyLoading[cacheKey] = true;
        var tbody = document.getElementById('pccHistoryRows');
        if (tbody) {
            tbody.innerHTML = '<tr><td colspan="8" class="page-description">Loading history…</td></tr>';
        }
        fetch(historyUrl + '?id=' + encodeURIComponent(productId), {
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin'
        })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                historyLoading[cacheKey] = false;
                if (!data || !data.ok) {
                    if (tbody) {
                        tbody.innerHTML = '<tr><td colspan="8" class="page-description">Could not load history.</td></tr>';
                    }
                    return;
                }
                historyByProduct[cacheKey] = data.rows || [];
                renderHistory(productId, variantId);
            })
            .catch(function () {
                historyLoading[cacheKey] = false;
                if (tbody) {
                    tbody.innerHTML = '<tr><td colspan="8" class="page-description">Could not load history.</td></tr>';
                }
            });
    }

    function getVariantState(index) {
        if (!variantState[index]) {
            variantState[index] = {};
        }
        return variantState[index];
    }

    function editableCellHtml(field, value, index) {
        var display = trimVal(value) === '' ? chipHtml('') : esc(String(value));
        return '<span class="pcc-cell-value" data-editable="' + esc(field) + '" data-variant-index="' + index + '" tabindex="0" title="Double-click to edit">' + display + '</span>';
    }

    function renderVariantLinesTable(variants, workspace) {
        if (!variantLinesBody) {
            return;
        }
        variantState = {};
        variantLinesBody.innerHTML = '';

        (variants || []).forEach(function (variant, index) {
            var tr = document.createElement('tr');
            tr.setAttribute('data-variant-index', String(index));
            tr.dataset.variantId = String(variant.product_variant_id || '');
            var healthClass = esc(variant.health_class || 'muted');
            var state = getVariantState(index);
            state.supplier_model = variant.supplier_model || '';
            state.product_cost = variant.product_cost ?? '';
            state.vendor_stock = variant.vendor_stock ?? 0;
            state.cost_meta = null;
            state.stock_meta = null;

            var healthLabel = shortHealthLabel(variant.health || '—');
            tr.innerHTML =
                '<td class="pcc-vcol-image">' + imageCellHtml(variant.image_url) + '</td>'
                + '<td class="pcc-vcol-line"><span class="pcc-cell-ellipsis" title="' + esc(variant.line_label || '') + '">' + esc(variant.line_label || variant.option_value || 'Option') + '</span></td>'
                + '<td class="pcc-vcol-model"><span class="pcc-cell-ellipsis">' + esc(variant.source_model || '—') + '</span></td>'
                + '<td class="pcc-vcol-vendor">' + editableCellHtml('supplier_model', state.supplier_model, index) + '</td>'
                + '<td class="pcc-vcol-cost pcc-num">' + editableCellHtml('product_cost', state.product_cost, index) + '</td>'
                + '<td class="pcc-vcol-stock pcc-ro-cell pcc-num">' + esc(variant.source_stock ?? '—') + '</td>'
                + '<td class="pcc-vcol-vstock pcc-num">' + editableCellHtml('vendor_stock', state.vendor_stock, index) + '</td>'
                + '<td class="pcc-vcol-health"><span class="badge badge-sm badge-' + healthClass + '">' + esc(healthLabel) + '</span></td>'
                + '<td class="pcc-vcol-history"><button type="button" class="pcc-history-icon-btn pcc-row-history-btn" data-variant-id="' + esc(variant.product_variant_id || '') + '" title="History" aria-label="View option history">' + HISTORY_ICON + '</button></td>';
            variantLinesBody.appendChild(tr);
        });

        bindVariantCellEvents();
    }

    function bindVariantCellEvents() {
        if (!variantLinesBody) {
            return;
        }
        variantLinesBody.querySelectorAll('.pcc-cell-value[data-editable]').forEach(function (cell) {
            cell.addEventListener('dblclick', function () {
                if (!canEditSupplier) {
                    return;
                }
                startVariantCellEdit(cell);
            });
            cell.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    startVariantCellEdit(cell);
                }
            });
        });
        variantLinesBody.querySelectorAll('.pcc-row-history-btn').forEach(function (btn) {
            btn.addEventListener('click', function (event) {
                event.stopPropagation();
                var variantId = btn.getAttribute('data-variant-id');
                activateTab('history');
                loadHistory(currentProductId, variantId);
            });
        });
    }

    function startVariantCellEdit(cell) {
        var field = cell.getAttribute('data-editable');
        var index = parseInt(cell.getAttribute('data-variant-index') || '0', 10);
        var state = getVariantState(index);
        var current = state[field] == null ? '' : String(state[field]);
        var inputType = field === 'supplier_model' ? 'text' : 'number';
        var step = field === 'supplier_model' ? '' : (field === 'product_cost' ? ' step="0.01" min="0"' : ' min="0"');
        cell.innerHTML = '<input type="' + inputType + '" class="form-input pcc-variant-inline-input"' + step + ' value="' + esc(current) + '">';
        var input = cell.querySelector('input');
        if (!input) {
            return;
        }
        input.focus();
        input.select();
        input.addEventListener('blur', function () {
            finishVariantCellEdit(cell, field, index, input.value);
        });
        input.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                input.blur();
            }
            if (event.key === 'Escape') {
                event.preventDefault();
                renderVariantCellDisplay(cell, field, index);
            }
        });
    }

    function finishVariantCellEdit(cell, field, index, rawValue) {
        var state = getVariantState(index);
        if (field === 'vendor_stock') {
            state[field] = Math.max(0, parseInt(rawValue || '0', 10) || 0);
        } else if (field === 'product_cost') {
            state[field] = rawValue === '' ? '' : String(Math.max(0, parseFloat(rawValue) || 0));
        } else {
            state[field] = rawValue;
        }
        var row = cell.closest('tr');
        if (row) {
            row.classList.add('pcc-row-dirty');
        }
        renderVariantCellDisplay(cell, field, index);
        markDirty();
    }

    function renderVariantCellDisplay(cell, field, index) {
        var state = getVariantState(index);
        var value = state[field];
        cell.innerHTML = trimVal(value) === '' ? chipHtml('') : esc(String(value));
    }

    function collectVariants() {
        var workspace = workspaces[String(currentProductId)] || {};
        var sourceVariants = workspace.variants || [];
        var collected = [];
        (sourceVariants || []).forEach(function (source, index) {
            var state = getVariantState(index);
            var item = {
                product_variant_id: source.product_variant_id || 0,
                supplier_model: state.supplier_model != null ? state.supplier_model : (source.supplier_model || ''),
                product_cost: state.product_cost != null ? state.product_cost : (source.product_cost ?? ''),
                vendor_stock: state.vendor_stock != null ? state.vendor_stock : (source.vendor_stock ?? 0),
                status: source.status || 'active'
            };
            if (state.cost_meta) {
                item.cost_meta = state.cost_meta;
            }
            if (state.stock_meta) {
                item.stock_meta = state.stock_meta;
            }
            collected.push(item);
        });
        return collected;
    }

    function toggleVariableLayout(isVariable) {
        document.querySelectorAll('.pcc-simple-supplier-field').forEach(function (el) {
            el.hidden = isVariable;
        });
        if (variantSection) {
            variantSection.hidden = !isVariable;
        }
        var workspaceEl = document.querySelector('.pcc-v202-workspace');
        if (workspaceEl) {
            workspaceEl.classList.toggle('is-variable', isVariable);
        }
        if (vendorMappingCard) {
            vendorMappingCard.classList.toggle('is-variable-product', isVariable);
        }
    }

    function syncSupplierFieldDisplay(field, value) {
        var display = document.querySelector('.pcc-dblclick-edit[data-field="' + field + '"]');
        var inputId = display ? display.getAttribute('data-input') : null;
        if (display) {
            if (field === 'status') {
                display.textContent = value === 'inactive' ? 'Inactive' : 'Active';
            } else {
                display.textContent = displayVal(value);
            }
        }
        if (inputId) {
            setInput(inputId, value);
        }
    }

    function renderSupplierFields(workspace) {
        syncSupplierFieldDisplay('supplier_model', workspace.supplier_model || '');
        syncSupplierFieldDisplay('low_warning', workspace.low_warning_threshold ?? '');
        syncSupplierFieldDisplay('product_cost', workspace.product_cost ?? '');
        syncSupplierFieldDisplay('vendor_stock', workspace.vendor_stock ?? 0);
        syncSupplierFieldDisplay('status', workspace.status || 'active');
        setInput('pccSupplierNote', workspace.supplier_note || '');
    }

    function startSupplierFieldEdit(displayEl) {
        if (!canEditSupplier || !displayEl) {
            return;
        }
        var field = displayEl.getAttribute('data-field');
        var inputId = displayEl.getAttribute('data-input');
        var inputEl = document.getElementById(inputId);
        if (!inputEl || displayEl.classList.contains('is-editing')) {
            return;
        }
        var current = inputEl.value;
        displayEl.classList.add('is-editing');
        var inputType = (field === 'low_warning' || field === 'product_cost' || field === 'vendor_stock') ? 'number' : 'text';
        var html = '';
        if (field === 'status') {
            html = '<select class="form-input form-input-compact pcc-supplier-inline-input">'
                + '<option value="active"' + (current === 'active' ? ' selected' : '') + '>Active</option>'
                + '<option value="inactive"' + (current === 'inactive' ? ' selected' : '') + '>Inactive</option>'
                + '</select>';
        } else {
            var step = field === 'product_cost' ? ' step="0.01" min="0"' : (inputType === 'number' ? ' min="0"' : '');
            html = '<input type="' + inputType + '" class="form-input form-input-compact pcc-supplier-inline-input" value="' + esc(current) + '"' + step + '>';
        }
        displayEl.innerHTML = html;
        var control = displayEl.querySelector('input, select');
        if (!control) {
            return;
        }
        control.focus();
        if (control.select) {
            control.select();
        }
        function finish() {
            var val = control.value;
            inputEl.value = val;
            displayEl.classList.remove('is-editing');
            syncSupplierFieldDisplay(field, val);
            markDirty();
        }
        control.addEventListener('blur', finish);
        control.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                control.blur();
            }
            if (event.key === 'Escape') {
                event.preventDefault();
                displayEl.classList.remove('is-editing');
                syncSupplierFieldDisplay(field, inputEl.value);
            }
        });
        if (control.tagName === 'SELECT') {
            control.addEventListener('change', function () {
                control.blur();
            });
        }
    }

    function restoreBaseline() {
        var baseline = baselines[String(currentProductId)];
        if (!baseline) {
            return;
        }
        workspaces[String(currentProductId)] = JSON.parse(JSON.stringify(baseline));
        populateModal(baseline, currentProductId, { preserveTab: true });
        setDirty(false);
    }

    function populateModal(workspace, productId, options) {
        options = options || {};
        currentProductId = String(productId);
        if (!baselines[String(productId)]) {
            baselines[String(productId)] = JSON.parse(JSON.stringify(workspace));
        }
        setInput('pccProductId', productId);
        setInput('pccBusinessSourceId', workspace.business_source_id || '1');
        setInput('pccCategory', workspace.supplier_product_category || '');
        setImage(workspace.image_url || '');
        setText('pccProductNameMuted', workspace.product_name || '—');
        setText('pccOcProductId', workspace.source_product_id || '—');
        setText('pccOcModel', workspace.source_model || '—');
        setText('pccProductType', workspace.type === 'variable' ? 'Variable Product' : 'Simple Product');
        setText('pccTotalStock', workspace.total_stock != null ? workspace.total_stock : (workspace.source_stock ?? '—'));
        setText('pccTotalVariants', workspace.variant_count != null ? workspace.variant_count : (workspace.variants ? workspace.variants.length : 0));
        setText('pccLastSynced', workspace.last_synced_at || '—');
        renderSnapshotHealth(workspace);

        var supplierIdEl = document.getElementById('pccSupplierId');
        if (supplierIdEl) {
            setInput('pccSupplierId', workspace.supplier_id || supplierIdEl.value || '1');
        }

        renderSupplierFields(workspace);

        var isVariable = workspace.type === 'variable';
        toggleVariableLayout(isVariable);
        if (noOptionsNotice) {
            noOptionsNotice.hidden = !(isVariable && workspace.no_options_synced);
        }

        if (isVariable) {
            setInput('pccProductCost', '');
            setInput('pccProductVendorStock', '');
            setInput('pccCostMeta', '');
            setInput('pccStockMeta', '');
            renderVariantLinesTable(workspace.variants || [], workspace);
        } else {
            renderVariantLinesTable([], workspace);
        }

        if (!options.preserveTab) {
            setDirty(false);
        }

        if (options.tab === 'history') {
            loadHistory(productId, options.variantId || null);
        }
        activateTab(options.tab || 'details');
        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
    }

    function openProductModal(productId, options) {
        options = options || {};
        showModalError('');
        var cached = workspaces[String(productId)];
        if (cached) {
            populateModal(cached, productId, options);
            return;
        }
        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
        showModalLoading(true);
        fetch(workspaceUrl + '?id=' + encodeURIComponent(productId), {
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin'
        })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                showModalLoading(false);
                if (!data || !data.ok || !data.workspace) {
                    showModalError((data && data.message) ? data.message : 'Could not load product workspace.');
                    return;
                }
                workspaces[String(productId)] = data.workspace;
                populateModal(data.workspace, productId, options);
            })
            .catch(function () {
                showModalLoading(false);
                showModalError('Could not load product workspace.');
            });
    }

    function closeModal() {
        if (isDirty && !window.confirm('Discard unsaved changes?')) {
            return;
        }
        modal.hidden = true;
        modal.setAttribute('aria-hidden', 'true');
        showModalLoading(false);
        showModalError('');
        setDirty(false);
        hideAdjustPopover();
    }

    function applyCostAdjust(current, type, amount) {
        current = parseFloat(current || 0) || 0;
        amount = parseFloat(amount || 0) || 0;
        if (type === 'fixed_plus') return Math.max(0, current + amount);
        if (type === 'fixed_minus') return Math.max(0, current - amount);
        if (type === 'percent_plus') return Math.max(0, current * (1 + amount / 100));
        if (type === 'percent_minus') return Math.max(0, current * (1 - amount / 100));
        return Math.max(0, amount);
    }

    function applyStockAdjust(current, type, amount) {
        current = parseInt(current || 0, 10) || 0;
        amount = parseInt(amount || 0, 10) || 0;
        if (type === 'increase') return Math.max(0, current + amount);
        if (type === 'decrease') return Math.max(0, current - amount);
        return Math.max(0, amount);
    }

    function adjustPresetLabel(field, type, amount) {
        if (field === 'cost') {
            return ({ fixed_plus: '+' + amount, fixed_minus: '-' + amount, percent_plus: '+' + amount + '%', percent_minus: '-' + amount + '%' })[type] || type;
        }
        return ({ increase: '+' + amount, decrease: '-' + amount })[type] || type;
    }

    function showAdjustModal(context) {
        adjustContext = context;
        if (!adjustPopover) {
            return;
        }
        var title = document.getElementById('pccAdjustTitle');
        var preset = document.getElementById('pccAdjustPreset');
        document.getElementById('pccAdjustType').value = context.presetType || 'direct';
        document.getElementById('pccAdjustAmount').value = context.presetAmount || '';
        document.getElementById('pccAdjustNote').value = '';
        if (title) {
            title.textContent = context.field === 'cost' ? 'Cost Adjustment' : 'Stock Adjustment';
        }
        if (preset) {
            preset.textContent = 'Apply: ' + adjustPresetLabel(context.field, context.presetType, context.presetAmount);
        }
        updateAdjustPreview();
        adjustPopover.hidden = false;
    }

    function hideAdjustPopover() {
        if (adjustPopover) {
            adjustPopover.hidden = true;
        }
        adjustContext = null;
    }

    function updateAdjustPreview() {
        if (!adjustContext) {
            return;
        }
        var type = document.getElementById('pccAdjustType').value;
        var amount = document.getElementById('pccAdjustAmount').value;
        var preview = document.getElementById('pccAdjustPreview');
        if (!preview) {
            return;
        }
        var next = adjustContext.field === 'cost'
            ? applyCostAdjust(adjustContext.baseValue, type, amount)
            : applyStockAdjust(adjustContext.baseValue, type, amount);
        preview.textContent = 'Preview: ' + adjustContext.baseValue + ' → ' + next;
    }

    function applyAdjustPopover() {
        if (!adjustContext) {
            return;
        }
        var type = document.getElementById('pccAdjustType').value;
        var amount = document.getElementById('pccAdjustAmount').value;
        var note = trimVal(document.getElementById('pccAdjustNote').value);
        if (note === '') {
            window.alert('Note/reason is required for adjustments.');
            return;
        }
        var meta = { type: type, amount: amount, note: note };
        var next = adjustContext.field === 'cost'
            ? applyCostAdjust(adjustContext.baseValue, type, amount)
            : applyStockAdjust(adjustContext.baseValue, type, amount);

        if (adjustContext.scope === 'variant') {
            var state = getVariantState(adjustContext.variantIndex);
            if (adjustContext.field === 'cost') {
                state.product_cost = String(next);
                state.cost_meta = meta;
                syncSupplierFieldDisplay('product_cost', state.product_cost);
            } else {
                state.vendor_stock = next;
                state.stock_meta = meta;
            }
            var row = variantLinesBody.querySelector('tr[data-variant-index="' + adjustContext.variantIndex + '"]');
            if (row) {
                row.classList.add('pcc-row-dirty');
                var cellField = adjustContext.field === 'cost' ? 'product_cost' : 'vendor_stock';
                var cell = row.querySelector('.pcc-cell-value[data-editable="' + cellField + '"]');
                if (cell) {
                    renderVariantCellDisplay(cell, cellField, adjustContext.variantIndex);
                }
            }
        } else if (adjustContext.field === 'cost') {
            setInput('pccProductCost', String(next));
            syncSupplierFieldDisplay('product_cost', next);
            document.getElementById('pccCostMeta').value = JSON.stringify(meta);
        } else {
            setInput('pccProductVendorStock', String(next));
            syncSupplierFieldDisplay('vendor_stock', next);
            document.getElementById('pccStockMeta').value = JSON.stringify(meta);
        }
        markDirty();
        hideAdjustPopover();
    }

    document.querySelectorAll('.product-catalog-row').forEach(function (row) {
        row.addEventListener('click', function (event) {
            if (event.target.closest('.pcc-history-btn, .pcc-open-btn, button, a, input')) {
                return;
            }
            openProductModal(row.getAttribute('data-product-id'), { tab: 'details' });
        });
        row.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                openProductModal(row.getAttribute('data-product-id'), { tab: 'details' });
            }
        });
    });

    document.querySelectorAll('.pcc-open-btn').forEach(function (btn) {
        btn.addEventListener('click', function (event) {
            event.stopPropagation();
            openProductModal(btn.getAttribute('data-product-id'), { tab: 'details' });
        });
    });

    document.querySelectorAll('.pcc-history-btn').forEach(function (btn) {
        btn.addEventListener('click', function (event) {
            event.stopPropagation();
            openProductModal(btn.getAttribute('data-product-id'), { tab: 'history' });
        });
    });

    document.querySelectorAll('.pcc-tab').forEach(function (btn) {
        btn.addEventListener('click', function () {
            activateTab(btn.getAttribute('data-pcc-tab') || 'details');
        });
    });

    document.querySelectorAll('[data-modal-close="productControlCenterModal"]').forEach(function (btn) {
        btn.addEventListener('click', closeModal);
    });

    document.querySelectorAll('.pcc-dblclick-edit').forEach(function (el) {
        el.addEventListener('dblclick', function () {
            startSupplierFieldEdit(el);
        });
        el.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                startSupplierFieldEdit(el);
            }
        });
    });

    if (cancelBtn) {
        cancelBtn.addEventListener('click', function () {
            restoreBaseline();
        });
    }

    modal.addEventListener('click', function (event) {
        if (event.target === modal) {
            closeModal();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !modal.hidden) {
            if (adjustPopover && !adjustPopover.hidden) {
                hideAdjustPopover();
                return;
            }
            closeModal();
        }
    });

    document.querySelectorAll('.pcc-v202-adjust-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (!canEditSupplier) {
                return;
            }
            var field = btn.getAttribute('data-adjust-field');
            var base = field === 'cost'
                ? (supplierCostField ? supplierCostField.value : 0)
                : (supplierStockField ? supplierStockField.value : 0);
            showAdjustModal({
                scope: btn.getAttribute('data-adjust-scope') || 'product',
                field: field,
                baseValue: base,
                presetType: btn.getAttribute('data-adjust-type'),
                presetAmount: btn.getAttribute('data-adjust-amount')
            });
        });
    });

    var adjustApply = document.getElementById('pccAdjustApply');
    if (adjustApply) {
        adjustApply.addEventListener('click', applyAdjustPopover);
    }
    var adjustCancel = document.getElementById('pccAdjustCancel');
    if (adjustCancel) {
        adjustCancel.addEventListener('click', hideAdjustPopover);
    }

    if (form) {
        form.addEventListener('input', function (event) {
            if (event.target.closest('#pccSupplierFields, #pccVariantLinesBody, #pccSupplierNote')) {
                markDirty();
            }
        });
        form.addEventListener('submit', function () {
            var variantsJson = document.getElementById('pccVariantsJson');
            if (variantsJson) {
                variantsJson.value = JSON.stringify(collectVariants());
            }
            if (saveBtn) {
                saveBtn.disabled = true;
                saveBtn.textContent = 'Saving…';
            }
        });
    }

    var savedProductId = parseInt(bootstrap.savedProductId || '0', 10) || 0;
    if (savedProductId > 0) {
        var savedRow = document.querySelector('.product-catalog-row[data-product-id="' + savedProductId + '"]');
        if (savedRow) {
            savedRow.classList.add('pcc-row-saved-highlight');
        }
        openProductModal(String(savedProductId), { tab: 'details' });
    }
})();
