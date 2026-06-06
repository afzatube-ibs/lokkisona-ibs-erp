(function () {
    'use strict';

    function openModal(id) {
        var modal = document.getElementById(id);
        if (!modal) return;
        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
    }

    function closeModal(id) {
        var modal = document.getElementById(id);
        if (!modal) return;
        modal.hidden = true;
        modal.setAttribute('aria-hidden', 'true');
        if (!document.querySelector('.modal-overlay:not([hidden])')) {
            document.body.classList.remove('modal-open');
        }
    }

    document.querySelectorAll('[data-open-modal]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            openModal(btn.getAttribute('data-open-modal'));
        });
    });

    document.querySelectorAll('[data-modal-close]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            closeModal(btn.getAttribute('data-modal-close'));
        });
    });

    document.querySelectorAll('.modal-overlay').forEach(function (overlay) {
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) {
                closeModal(overlay.id);
            }
        });
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay:not([hidden])').forEach(function (modal) {
                closeModal(modal.id);
            });
        }
    });

    /* ── Calculator ── */
    var calcState = {
        display: '0',
        expression: '',
        pendingOp: null,
        pendingValue: null,
        fresh: true
    };

    var calcResult = document.getElementById('calcResult');
    var calcExpression = document.getElementById('calcExpression');

    function renderCalc() {
        if (calcResult) calcResult.textContent = calcState.display;
        if (calcExpression) calcExpression.textContent = calcState.expression;
    }

    function parseDisplay() {
        return parseFloat(calcState.display) || 0;
    }

    function setDisplay(val) {
        calcState.display = String(val);
        if (calcState.display.length > 14) {
            calcState.display = parseFloat(calcState.display).toPrecision(10).replace(/\.?0+$/, '');
        }
        renderCalc();
    }

    function compute(a, b, op) {
        if (op === '+') return a + b;
        if (op === '-') return a - b;
        if (op === '*') return a * b;
        if (op === '/') return b === 0 ? 0 : a / b;
        return b;
    }

    function applyPending() {
        if (calcState.pendingOp === null || calcState.pendingValue === null) return;
        var result = compute(calcState.pendingValue, parseDisplay(), calcState.pendingOp);
        calcState.pendingValue = result;
        setDisplay(result);
        calcState.pendingOp = null;
        calcState.expression = '';
        calcState.fresh = true;
    }

    function handleCalcAction(action, value, opLabel) {
            if (action === 'clear') {
                calcState = { display: '0', expression: '', pendingOp: null, pendingValue: null, fresh: true };
                renderCalc();
                return;
            }
            if (action === 'clear-entry') {
                setDisplay('0');
                calcState.fresh = true;
                return;
            }
            if (action === 'backspace') {
                if (calcState.fresh) return;
                calcState.display = calcState.display.length > 1 ? calcState.display.slice(0, -1) : '0';
                renderCalc();
                return;
            }
            if (action === 'digit') {
                if (calcState.fresh || calcState.display === '0') {
                    setDisplay(value);
                    calcState.fresh = false;
                } else {
                    setDisplay(calcState.display + value);
                }
                return;
            }
            if (action === 'decimal') {
                if (calcState.fresh) {
                    setDisplay('0.');
                    calcState.fresh = false;
                } else if (calcState.display.indexOf('.') === -1) {
                    setDisplay(calcState.display + '.');
                }
                return;
            }
            if (action === 'percent') {
                setDisplay(parseDisplay() / 100);
                calcState.fresh = true;
                return;
            }
            if (action === 'op') {
                if (calcState.pendingOp !== null && !calcState.fresh) {
                    applyPending();
                } else if (calcState.pendingValue === null) {
                    calcState.pendingValue = parseDisplay();
                }
                calcState.pendingOp = value;
                calcState.expression = calcState.pendingValue + ' ' + (opLabel || value);
                calcState.fresh = true;
                renderCalc();
                return;
            }
            if (action === 'equals') {
                if (calcState.pendingOp !== null && calcState.pendingValue !== null) {
                    var result = compute(calcState.pendingValue, parseDisplay(), calcState.pendingOp);
                    calcState.expression = calcState.pendingValue + ' ' + (calcState.pendingOp === '*' ? '×' : calcState.pendingOp === '/' ? '÷' : calcState.pendingOp) + ' ' + calcState.display + ' =';
                    setDisplay(result);
                    calcState.pendingOp = null;
                    calcState.pendingValue = null;
                    calcState.fresh = true;
                }
            }
    }

    document.querySelectorAll('[data-calc-action]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            handleCalcAction(btn.getAttribute('data-calc-action'), btn.getAttribute('data-calc-value'), btn.textContent);
        });
    });

    function isCalculatorOpen() {
        var modal = document.getElementById('supplierCalculatorModal');
        return modal && !modal.hidden;
    }

    document.addEventListener('keydown', function (e) {
        if (!isCalculatorOpen() || e.ctrlKey || e.metaKey || e.altKey) return;
        var key = e.key;

        if (/^[0-9]$/.test(key)) {
            e.preventDefault();
            handleCalcAction('digit', key, null);
            return;
        }
        if (key === '.') {
            e.preventDefault();
            handleCalcAction('decimal', null, null);
            return;
        }
        if (key === '+') {
            e.preventDefault();
            handleCalcAction('op', '+', '+');
            return;
        }
        if (key === '-') {
            e.preventDefault();
            handleCalcAction('op', '-', '−');
            return;
        }
        if (key === '*') {
            e.preventDefault();
            handleCalcAction('op', '*', '×');
            return;
        }
        if (key === '/') {
            e.preventDefault();
            handleCalcAction('op', '/', '÷');
            return;
        }
        if (key === 'Enter' || key === '=') {
            e.preventDefault();
            handleCalcAction('equals', null, null);
            return;
        }
        if (key === 'Backspace') {
            e.preventDefault();
            handleCalcAction('backspace', null, null);
            return;
        }
        if (key === '%') {
            e.preventDefault();
            handleCalcAction('percent', null, null);
        }
    });

    /* ── Quick Invoice line items ── */
    var itemsBody = document.getElementById('quickInvoiceItemsBody');
    var addRowBtn = document.getElementById('qiAddRow');
    var discountInput = document.getElementById('qiDiscount');
    var advanceInput = document.getElementById('qiAdvance');
    var rowIndex = 1;

    function formatMoney(n) {
        return (Math.round(n * 100) / 100).toFixed(2);
    }

    function recalcInvoiceTotals() {
        var subtotal = 0;
        if (itemsBody) {
            itemsBody.querySelectorAll('.qi-item-row').forEach(function (row) {
                var qty = parseFloat(row.querySelector('.qi-qty')?.value) || 0;
                var price = parseFloat(row.querySelector('.qi-price')?.value) || 0;
                var line = qty * price;
                subtotal += line;
                var lineCell = row.querySelector('.qi-line-total');
                if (lineCell) lineCell.textContent = formatMoney(line);
            });
        }
        var discount = Math.max(0, parseFloat(discountInput?.value) || 0);
        if (discount > subtotal) discount = subtotal;
        var afterDisc = subtotal - discount;
        var advance = Math.max(0, parseFloat(advanceInput?.value) || 0);
        if (advance > afterDisc) advance = afterDisc;
        var balance = afterDisc - advance;

        var el;
        if ((el = document.getElementById('qiSubtotal'))) el.textContent = formatMoney(subtotal);
        if ((el = document.getElementById('qiAfterDiscount'))) el.textContent = formatMoney(afterDisc);
        if ((el = document.getElementById('qiBalanceDue'))) el.textContent = formatMoney(balance);
    }

    function bindRowEvents(row) {
        row.querySelectorAll('.qi-qty, .qi-price').forEach(function (input) {
            input.addEventListener('input', recalcInvoiceTotals);
        });
        var removeBtn = row.querySelector('.qi-remove-row');
        if (removeBtn) {
            removeBtn.addEventListener('click', function () {
                if (itemsBody.querySelectorAll('.qi-item-row').length <= 1) return;
                row.remove();
                recalcInvoiceTotals();
            });
        }
    }

    if (itemsBody) {
        itemsBody.querySelectorAll('.qi-item-row').forEach(bindRowEvents);
        recalcInvoiceTotals();
    }

    if (addRowBtn && itemsBody) {
        addRowBtn.addEventListener('click', function () {
            var tr = document.createElement('tr');
            tr.className = 'qi-item-row';
            tr.innerHTML = ''
                + '<td><input type="text" name="items[' + rowIndex + '][name]" class="form-input" required placeholder="Product name"></td>'
                + '<td><input type="number" name="items[' + rowIndex + '][qty]" class="form-input qi-qty" min="1" value="1" required></td>'
                + '<td><input type="number" name="items[' + rowIndex + '][unit_price]" class="form-input qi-price" min="0" step="0.01" value="0" required></td>'
                + '<td class="qi-line-total">0.00</td>'
                + '<td><button type="button" class="btn btn-ghost btn-sm qi-remove-row">×</button></td>';
            itemsBody.appendChild(tr);
            bindRowEvents(tr);
            rowIndex++;
            recalcInvoiceTotals();
        });
    }

    if (discountInput) discountInput.addEventListener('input', recalcInvoiceTotals);
    if (advanceInput) advanceInput.addEventListener('input', recalcInvoiceTotals);

    var quickInvoiceForm = document.getElementById('quickInvoiceForm');
    if (quickInvoiceForm) {
        quickInvoiceForm.addEventListener('submit', function (e) {
            var customer = quickInvoiceForm.querySelector('[name="customer_name"]');
            var invoiceDate = quickInvoiceForm.querySelector('[name="invoice_date"]');
            var hasLine = false;
            if (itemsBody) {
                itemsBody.querySelectorAll('.qi-item-row').forEach(function (row) {
                    var name = (row.querySelector('[name*="[name]"]')?.value || '').trim();
                    var qty = parseFloat(row.querySelector('.qi-qty')?.value) || 0;
                    if (name !== '' && qty > 0) hasLine = true;
                });
            }
            if (!customer || customer.value.trim() === '') {
                e.preventDefault();
                alert('Customer name is required.');
                return;
            }
            if (!invoiceDate || invoiceDate.value.trim() === '') {
                e.preventDefault();
                alert('Invoice date is required.');
                return;
            }
            if (!hasLine) {
                e.preventDefault();
                alert('Add at least one product line with quantity.');
            }
        });
    }

    var clearBtn = document.getElementById('qiClearForm');
    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            var form = document.getElementById('quickInvoiceForm');
            if (form) form.reset();
            if (itemsBody) {
                itemsBody.innerHTML = ''
                    + '<tr class="qi-item-row">'
                    + '<td><input type="text" name="items[0][name]" class="form-input" required placeholder="Product name"></td>'
                    + '<td><input type="number" name="items[0][qty]" class="form-input qi-qty" min="1" value="1" required></td>'
                    + '<td><input type="number" name="items[0][unit_price]" class="form-input qi-price" min="0" step="0.01" value="0" required></td>'
                    + '<td class="qi-line-total">0.00</td><td></td></tr>';
                itemsBody.querySelectorAll('.qi-item-row').forEach(bindRowEvents);
                rowIndex = 1;
                recalcInvoiceTotals();
            }
        });
    }
})();
