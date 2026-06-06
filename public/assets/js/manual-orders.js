(function () {
    'use strict';

    var itemsBody = document.getElementById('manualOrderItemsBody');
    var addRowBtn = document.getElementById('moAddRow');
    if (!itemsBody) {
        return;
    }

    var variantMap = {};
    var productCosts = {};
    try {
        variantMap = JSON.parse(document.getElementById('moVariantMap')?.textContent || '{}');
        productCosts = JSON.parse(document.getElementById('moProductCosts')?.textContent || '{}');
    } catch (e) {
        variantMap = {};
        productCosts = {};
    }

    var productOptionsHtml = document.getElementById('moProductOptionsTemplate')?.innerHTML || '';
    var rowIndex = itemsBody.querySelectorAll('.mo-item-row').length;

    function formatMoney(n) {
        return (Math.round(n * 100) / 100).toFixed(2);
    }

    function resolveRowCost(row) {
        var productSelect = row.querySelector('.mo-product');
        var variantSelect = row.querySelector('.mo-variant');
        var productId = productSelect ? parseInt(productSelect.value, 10) : 0;
        var variantId = variantSelect && variantSelect.value !== '' ? parseInt(variantSelect.value, 10) : 0;
        if (variantId > 0 && variantMap[productId]) {
            var variants = variantMap[productId];
            for (var i = 0; i < variants.length; i++) {
                if (variants[i].id === variantId) {
                    return parseFloat(variants[i].cost) || 0;
                }
            }
        }
        return productCosts[productId] || 0;
    }

    function populateVariants(row) {
        var productSelect = row.querySelector('.mo-product');
        var variantSelect = row.querySelector('.mo-variant');
        if (!productSelect || !variantSelect) {
            return;
        }
        var productId = parseInt(productSelect.value, 10);
        var previous = variantSelect.value;
        variantSelect.innerHTML = '<option value="">—</option>';
        var variants = variantMap[productId] || [];
        variants.forEach(function (v) {
            var opt = document.createElement('option');
            opt.value = String(v.id);
            opt.textContent = v.label;
            variantSelect.appendChild(opt);
        });
        if (previous && variantSelect.querySelector('option[value="' + previous + '"]')) {
            variantSelect.value = previous;
        }
    }

    function recalcTotals() {
        var lineCount = 0;
        var totalQty = 0;
        var sellingSubtotal = 0;
        var costSubtotal = 0;

        itemsBody.querySelectorAll('.mo-item-row').forEach(function (row) {
            var productSelect = row.querySelector('.mo-product');
            if (!productSelect || productSelect.value === '') {
                return;
            }
            lineCount++;
            var qty = parseFloat(row.querySelector('.mo-qty')?.value) || 0;
            var price = parseFloat(row.querySelector('.mo-price')?.value) || 0;
            var line = qty * price;
            var cost = resolveRowCost(row);
            sellingSubtotal += line;
            costSubtotal += cost * qty;
            totalQty += qty;
            var lineCell = row.querySelector('.mo-line-total');
            if (lineCell) {
                lineCell.textContent = formatMoney(line);
            }
        });

        var el;
        if ((el = document.getElementById('moLineCount'))) el.textContent = String(lineCount);
        if ((el = document.getElementById('moTotalQty'))) el.textContent = String(totalQty);
        if ((el = document.getElementById('moSellingSubtotal'))) el.textContent = formatMoney(sellingSubtotal);
        if ((el = document.getElementById('moCostSubtotal'))) el.textContent = formatMoney(costSubtotal);
    }

    function bindRowEvents(row) {
        var productSelect = row.querySelector('.mo-product');
        if (productSelect) {
            productSelect.addEventListener('change', function () {
                populateVariants(row);
                recalcTotals();
            });
        }
        var variantSelect = row.querySelector('.mo-variant');
        if (variantSelect) {
            variantSelect.addEventListener('change', recalcTotals);
        }
        row.querySelectorAll('.mo-qty, .mo-price').forEach(function (input) {
            input.addEventListener('input', recalcTotals);
        });
        var removeBtn = row.querySelector('.mo-remove-row');
        if (removeBtn) {
            removeBtn.addEventListener('click', function () {
                if (itemsBody.querySelectorAll('.mo-item-row').length <= 1) {
                    return;
                }
                row.remove();
                recalcTotals();
            });
        }
    }

    itemsBody.querySelectorAll('.mo-item-row').forEach(function (row) {
        populateVariants(row);
        bindRowEvents(row);
    });
    recalcTotals();

    if (addRowBtn) {
        addRowBtn.addEventListener('click', function () {
            var tr = document.createElement('tr');
            tr.className = 'mo-item-row';
            tr.setAttribute('data-row-index', String(rowIndex));
            tr.innerHTML = ''
                + '<td><select name="items[' + rowIndex + '][product_id]" class="form-input mo-product" required>'
                + '<option value="">Select product…</option>' + productOptionsHtml + '</select></td>'
                + '<td><select name="items[' + rowIndex + '][product_variant_id]" class="form-input mo-variant"><option value="">—</option></select></td>'
                + '<td><input type="number" name="items[' + rowIndex + '][quantity]" class="form-input mo-qty" min="1" value="1" required></td>'
                + '<td><input type="number" name="items[' + rowIndex + '][selling_price]" class="form-input mo-price" min="0" step="0.01" value="0" required></td>'
                + '<td class="mo-line-total">0.00</td>'
                + '<td><button type="button" class="btn btn-ghost btn-sm mo-remove-row" aria-label="Remove row">×</button></td>';
            itemsBody.appendChild(tr);
            bindRowEvents(tr);
            rowIndex++;
            recalcTotals();
        });
    }
})();
