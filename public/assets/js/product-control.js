(function () {
    'use strict';

    var payloadEl = document.getElementById('productCatalogPayload');
    var modal = document.getElementById('productControlCenterModal');
    if (!payloadEl || !modal) {
        return;
    }

    var payload = {};
    try {
        payload = JSON.parse(payloadEl.textContent || '{}');
    } catch (e) {
        payload = {};
    }

    var workspaces = payload.workspaces || {};
    var historyByProduct = payload.historyByProduct || {};
    var isSupplierView = !!payload.isSupplierView;
    var costFieldLabel = isSupplierView ? 'Supplier Sale' : 'Supplier Cost';
    var form = document.getElementById('productControlCenterForm');
    var variantLinesBody = document.getElementById('pccVariantLinesBody');
    var variantSection = document.getElementById('pccVariantSection');
    var noOptionsNotice = document.getElementById('pccNoOptionsNotice');
    var supplierView = document.getElementById('pccSupplierView');
    var supplierEdit = document.getElementById('pccSupplierEdit');
    var supplierEditBtn = document.getElementById('pccSupplierEditBtn');
    var vendorMappingCard = document.getElementById('pccVendorMappingCard');
    var supplierCostField = document.getElementById('pccProductCost');
    var supplierStockField = document.getElementById('pccProductVendorStock');
    var canEditSupplier = !!document.getElementById('pccSaveBtn');
    var currentProductId = '';
    var supplierEditMode = false;

    function esc(text) {
        var div = document.createElement('div');
        div.textContent = text == null ? '' : String(text);
        return div.innerHTML;
    }

    function displayValue(value) {
        return value == null || value === '' ? '—' : String(value);
    }

    function setText(id, value) {
        var el = document.getElementById(id);
        if (el) {
            el.textContent = displayValue(value);
        }
    }

    function setInput(id, value) {
        var el = document.getElementById(id);
        if (el) {
            el.value = value == null ? '' : String(value);
        }
    }

    function setSelect(id, value) {
        var el = document.getElementById(id);
        if (el) {
            el.value = value == null || value === '' ? 'active' : String(value);
        }
    }

    function setImage(url) {
        var img = document.getElementById('pccProductImage');
        var placeholder = document.getElementById('pccImagePlaceholder');
        if (!img || !placeholder) {
            return;
        }
        if (url) {
            img.src = url;
            img.hidden = false;
            placeholder.hidden = true;
        } else {
            img.hidden = true;
            img.removeAttribute('src');
            placeholder.hidden = false;
        }
    }

    function renderBadges(badges) {
        var row = document.getElementById('pccCompletenessBadges');
        if (!row) {
            return;
        }
        row.innerHTML = '';
        (badges || []).forEach(function (badge) {
            var span = document.createElement('span');
            span.className = 'badge badge-' + esc(badge.class || 'muted');
            span.textContent = badge.label || '';
            row.appendChild(span);
        });
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
    }

    function renderHistory(productId) {
        var tbody = document.getElementById('pccHistoryRows');
        if (!tbody) {
            return;
        }
        var rows = historyByProduct[String(productId)] || historyByProduct[productId] || [];
        if (!rows.length) {
            tbody.innerHTML = '<tr><td colspan="5" class="page-description">No cost/stock history recorded for this product yet.</td></tr>';
            return;
        }
        tbody.innerHTML = rows.map(function (row) {
            var costChange = (row.old_cost !== '' && row.old_cost != null ? row.old_cost : '—')
                + ' → ' + (row.new_cost !== '' && row.new_cost != null ? row.new_cost : '—');
            var stockChange = (row.old_stock !== '' && row.old_stock != null ? row.old_stock : '—')
                + ' → ' + (row.new_stock !== '' && row.new_stock != null ? row.new_stock : '—');
            return '<tr>'
                + '<td>' + esc(row.variant_label || 'Product level') + '</td>'
                + '<td>' + esc(costChange) + '</td>'
                + '<td>' + esc(stockChange) + '</td>'
                + '<td>' + esc(row.note || '—') + '</td>'
                + '<td>' + esc(row.created_at || '—') + '</td>'
                + '</tr>';
        }).join('');
    }

    function renderVariantLinesTable(variants) {
        if (!variantLinesBody) {
            return;
        }
        variantLinesBody.innerHTML = '';
        (variants || []).forEach(function (variant, index) {
            var tr = document.createElement('tr');
            tr.setAttribute('data-variant-index', String(index));
            tr.dataset.variantId = String(variant.product_variant_id || '');
            var imageCell = variant.image_url
                ? '<div class="pcc-list-thumb pcc-variant-thumb"><img src="' + esc(variant.image_url) + '" alt=""></div>'
                : '<span class="pcc-list-thumb-empty">—</span>';
            var healthClass = esc(variant.health_class || 'muted');
            tr.innerHTML =
                '<td class="pcc-vcol-line"><span class="pcc-cell-ellipsis" title="' + esc(variant.line_label || '') + '">' + esc(variant.line_label || variant.option_name || 'Option') + '</span></td>'
                + '<td class="pcc-vcol-image">' + imageCell + '</td>'
                + '<td class="pcc-vcol-model"><code class="pcc-cell-ellipsis" title="' + esc(variant.source_model || '') + '">' + esc(variant.source_model || '—') + '</code></td>'
                + '<td class="pcc-vcol-vendor"><input type="text" class="form-input pcc-variant-input" data-field="supplier_model" value="' + esc(variant.supplier_model || '') + '"></td>'
                + '<td class="pcc-vcol-price pcc-ro-cell">' + esc(variant.source_price_label || 'Not stored in ERP') + '</td>'
                + '<td class="pcc-vcol-cost"><input type="number" step="0.01" min="0" class="form-input pcc-variant-input" data-field="product_cost" value="' + esc(variant.product_cost ?? '') + '"></td>'
                + '<td class="pcc-vcol-owner pcc-ro-cell">' + esc(variant.source_stock ?? '—') + '</td>'
                + '<td class="pcc-vcol-vstock"><input type="number" min="0" class="form-input pcc-variant-input" data-field="vendor_stock" value="' + esc(variant.vendor_stock ?? 0) + '"></td>'
                + '<td class="pcc-vcol-warn pcc-ro-cell">' + esc(variant.warning || '—') + '</td>'
                + '<td class="pcc-vcol-health"><span class="badge badge-' + healthClass + '">' + esc(variant.health || '—') + '</span></td>';
            variantLinesBody.appendChild(tr);
        });
    }

    function collectVariants() {
        if (!variantLinesBody) {
            return [];
        }
        var workspace = workspaces[String(currentProductId)] || {};
        var sourceVariants = workspace.variants || [];
        var collected = [];
        variantLinesBody.querySelectorAll('tr[data-variant-index]').forEach(function (row) {
            var index = parseInt(row.getAttribute('data-variant-index') || '0', 10);
            var source = sourceVariants[index] || {};
            var item = {
                product_variant_id: source.product_variant_id || row.dataset.variantId || 0,
                supplier_model: '',
                product_cost: '',
                vendor_stock: 0,
                status: source.status || 'active'
            };
            row.querySelectorAll('.pcc-variant-input').forEach(function (input) {
                var field = input.getAttribute('data-field');
                if (field) {
                    item[field] = input.value;
                }
            });
            collected.push(item);
        });
        return collected;
    }

    function toggleSimpleSupplierFields(isVariable) {
        var simpleViewRows = document.querySelectorAll('.pcc-simple-supplier-view');
        var simpleEditWraps = document.querySelectorAll('.pcc-simple-supplier-edit');
        simpleViewRows.forEach(function (row) {
            row.hidden = isVariable;
        });
        simpleEditWraps.forEach(function (wrap) {
            wrap.hidden = isVariable;
        });
        [supplierCostField, supplierStockField].forEach(function (field) {
            if (!field) {
                return;
            }
            field.disabled = isVariable;
        });
    }

    function renderSupplierFieldViews(workspace) {
        var productName = workspace.product_name || '';
        setText('pccSupplierProductNameView', productName);
        setText('pccSupplierProductNameEdit', productName);
        setText('pccSupplierModelView', workspace.supplier_model || '');
        setText('pccLowWarningView', workspace.low_warning_threshold ?? '');
        setText('pccProductCostView', workspace.product_cost ?? '');
        setText('pccProductVendorStockView', workspace.vendor_stock ?? '');
    }

    function supplierHasSavedData(workspace) {
        return trimVal(workspace.supplier_model) !== ''
            || trimVal(workspace.low_warning_threshold) !== ''
            || trimVal(workspace.product_cost) !== ''
            || trimVal(workspace.vendor_stock) !== ''
            || trimVal(workspace.supplier_note) !== '';
    }

    function trimVal(value) {
        return value == null ? '' : String(value).trim();
    }

    function setSupplierPanelMode(mode) {
        supplierEditMode = mode === 'edit';
        if (supplierView) {
            supplierView.hidden = supplierEditMode;
        }
        if (supplierEdit) {
            supplierEdit.hidden = !supplierEditMode;
        }
        if (vendorMappingCard) {
            vendorMappingCard.classList.toggle('is-editing', supplierEditMode);
        }
        if (supplierEditBtn) {
            supplierEditBtn.textContent = supplierEditMode ? 'View' : 'Edit';
        }
    }

    function openProductModal(productId, options) {
        options = options || {};
        var workspace = workspaces[String(productId)];
        if (!workspace) {
            return;
        }

        currentProductId = String(productId);
        setInput('pccProductId', productId);
        setInput('pccBusinessSourceId', workspace.business_source_id || '1');
        setInput('pccCategory', workspace.supplier_product_category || '');
        setText('pccModalSubtitle', '#' + (workspace.source_product_id || productId)
            + ' • ' + (workspace.source_model || '—')
            + ' • Product Control Center');

        renderBadges(workspace.badges || []);
        setImage(workspace.image_url || '');
        setText('pccOcProductId', workspace.source_product_id || '—');
        setText('pccOcModel', workspace.source_model || '—');
        setText('pccOcStock', workspace.source_stock ?? '—');
        setText('pccOcPrice', 'Not stored in ERP');
        setText('pccLastSynced', workspace.last_synced_at || '—');

        setInput('pccSupplierModel', workspace.supplier_model || '');
        setInput('pccLowWarning', workspace.low_warning_threshold ?? '');
        setSelect('pccStatus', workspace.status || 'active');
        setInput('pccSupplierNote', workspace.supplier_note || '');
        var supplierIdEl = document.getElementById('pccSupplierId');
        if (supplierIdEl) {
            setInput('pccSupplierId', workspace.supplier_id || supplierIdEl.value || '1');
        }

        renderSupplierFieldViews(workspace);

        var isVariable = workspace.type === 'variable';
        toggleSimpleSupplierFields(isVariable);
        if (variantSection) {
            variantSection.hidden = !isVariable;
        }
        if (noOptionsNotice) {
            noOptionsNotice.hidden = !(isVariable && workspace.no_options_synced);
        }

        if (!isVariable) {
            setInput('pccProductCost', workspace.product_cost ?? '');
            setInput('pccProductVendorStock', workspace.vendor_stock ?? 0);
            renderVariantLinesTable([]);
        } else {
            setInput('pccProductCost', '');
            setInput('pccProductVendorStock', '');
            renderVariantLinesTable(workspace.variants || []);
        }

        if (canEditSupplier) {
            setSupplierPanelMode(supplierHasSavedData(workspace) ? 'view' : 'edit');
        } else {
            setSupplierPanelMode('view');
        }

        renderHistory(productId);
        activateTab(options.tab || 'details');
        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
    }

    function closeModal() {
        modal.hidden = true;
        modal.setAttribute('aria-hidden', 'true');
        setSupplierPanelMode('view');
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

    if (supplierEditBtn) {
        supplierEditBtn.addEventListener('click', function () {
            setSupplierPanelMode(supplierEditMode ? 'view' : 'edit');
        });
    }

    if (vendorMappingCard && canEditSupplier) {
        vendorMappingCard.addEventListener('dblclick', function (event) {
            if (event.target.closest('input, select, textarea, button')) {
                return;
            }
            setSupplierPanelMode('edit');
        });
    }

    modal.addEventListener('click', function (event) {
        if (event.target === modal) {
            closeModal();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !modal.hidden) {
            if (supplierEditMode && canEditSupplier) {
                setSupplierPanelMode('view');
                return;
            }
            closeModal();
        }
    });

    if (form) {
        form.addEventListener('submit', function () {
            var variantsJson = document.getElementById('pccVariantsJson');
            if (variantsJson) {
                variantsJson.value = JSON.stringify(collectVariants());
            }
            var saveBtn = document.getElementById('pccSaveBtn');
            if (saveBtn) {
                saveBtn.disabled = true;
                saveBtn.textContent = 'Saving…';
            }
        });
    }
})();
