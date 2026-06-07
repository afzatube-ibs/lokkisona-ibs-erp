(function () {
    'use strict';

    var modal = document.getElementById('vfActionModal');
    var actionForm = document.getElementById('vfActionForm');
    var bulkForm = document.getElementById('vfBulkForm');
    var pending = null;

    function qs(sel, root) {
        return (root || document).querySelector(sel);
    }

    function qsa(sel, root) {
        return Array.prototype.slice.call((root || document).querySelectorAll(sel));
    }

    function openModal(config) {
        if (!modal) {
            return;
        }
        pending = config;
        qs('#vfActionModalTitle', modal).textContent = config.label || 'Confirm action';
        qs('#vfActionModalDesc', modal).textContent = config.isBulk
            ? 'Apply "' + (config.label || '') + '" to ' + (config.orderIds ? config.orderIds.length : 0) + ' selected order(s)?'
            : 'Confirm workflow action for order #' + (config.orderId || '') + '. IBS status only — OpenCart order status is not changed.';
        var checkboxWrap = qs('#vfCheckboxWrap', modal);
        var staffCheck = qs('#vfModalStaffCheck', modal);
        if (config.requiresCheckbox) {
            checkboxWrap.hidden = false;
            qs('#vfCheckboxLabel', modal).textContent = config.checkboxLabel || 'Staff confirmation required.';
            staffCheck.checked = false;
        } else {
            checkboxWrap.hidden = true;
            staffCheck.checked = false;
        }
        var stopWrap = qs('#vfDeliveryStopWrap', modal);
        stopWrap.hidden = !config.isDeliveryStop;
        qsa('input[name="vf_delivery_stop_reason_ui"]', modal).forEach(function (input) {
            input.checked = false;
        });
        var note = qs('#vfModalNote', modal);
        note.value = '';
        qs('#vfNoteRequiredMark', modal).hidden = !config.requiresNote;
        modal.hidden = false;
    }

    function closeModal() {
        if (modal) {
            modal.hidden = true;
        }
        pending = null;
    }

    function submitPending() {
        if (!pending) {
            return;
        }
        var noteEl = qs('#vfModalNote', modal);
        var note = noteEl ? noteEl.value.trim() : '';
        if (pending.requiresNote && note === '') {
            window.alert('Action note is required.');
            return;
        }
        if (pending.requiresCheckbox) {
            var staffCheck = qs('#vfModalStaffCheck', modal);
            if (!staffCheck || !staffCheck.checked) {
                window.alert('Staff confirmation checkbox is required.');
                return;
            }
        }
        var deliveryReason = '';
        if (pending.isDeliveryStop) {
            var selected = qs('input[name="vf_delivery_stop_reason_ui"]:checked', modal);
            if (!selected) {
                window.alert('Delivery Stop reason is required.');
                return;
            }
            deliveryReason = selected.value;
        }

        if (pending.isBulk) {
            if (!bulkForm) {
                return;
            }
            qs('#vfBulkAction', bulkForm).value = pending.bulkAction || '';
            qs('#vfBulkConfirmed', bulkForm).value = '1';
            qs('#vfBulkStaffConfirmation', bulkForm).value = pending.requiresCheckbox ? '1' : '';
            qs('#vfBatchConfirmed', bulkForm).value = pending.bulkAction === 'bulk_dispatch' ? '1' : '';
            var holder = qs('#vfBulkOrderIds', bulkForm);
            holder.innerHTML = '';
            (pending.orderIds || []).forEach(function (id) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'order_ids[]';
                input.value = String(id);
                holder.appendChild(input);
            });
            if (pending.bulkAction === 'bulk_packaging' && (pending.orderIds || []).length > 0) {
                var ids = pending.orderIds.join(',');
                window.open('/invoice-printing?order_ids=' + encodeURIComponent(ids), '_blank', 'noopener');
            }
            bulkForm.submit();
            return;
        }

        if (!actionForm) {
            return;
        }
        if (pending.isBulkDispatch) {
            if (!bulkForm) {
                return;
            }
            qs('#vfBulkAction', bulkForm).value = 'bulk_dispatch';
            qs('#vfBulkConfirmed', bulkForm).value = '1';
            qs('#vfBatchConfirmed', bulkForm).value = '1';
            var wrap = qs('#vfBulkOrderIds', bulkForm);
            wrap.innerHTML = '';
            var inputOne = document.createElement('input');
            inputOne.type = 'hidden';
            inputOne.name = 'order_ids[]';
            inputOne.value = String(pending.orderId);
            wrap.appendChild(inputOne);
            bulkForm.submit();
            return;
        }

        qs('#vfActionOrderId', actionForm).value = String(pending.orderId || '');
        qs('#vfActionToStatus', actionForm).value = pending.actionCode || '';
        qs('#vfActionConfirmed', actionForm).value = '1';
        qs('#vfStaffConfirmation', actionForm).value = pending.requiresCheckbox ? '1' : '';
        qs('#vfActionNote', actionForm).value = note;
        qs('#vfDeliveryStopReason', actionForm).value = deliveryReason;
        actionForm.submit();
    }

    function updateBulkBar() {
        var bar = document.getElementById('vfBulkBar');
        var countEl = document.getElementById('vfBulkCount');
        if (!bar || !countEl) {
            return;
        }
        var checked = qsa('.vf-row-check:checked');
        countEl.textContent = String(checked.length);
        bar.hidden = checked.length === 0;
    }

    qsa('.js-vf-row-action').forEach(function (btn) {
        btn.addEventListener('click', function () {
            openModal({
                orderId: btn.getAttribute('data-order-id'),
                actionCode: btn.getAttribute('data-action-code'),
                label: btn.getAttribute('data-action-label'),
                requiresNote: btn.getAttribute('data-requires-note') === '1',
                requiresCheckbox: btn.getAttribute('data-requires-checkbox') === '1',
                checkboxLabel: btn.getAttribute('data-checkbox-label') || '',
                isDeliveryStop: btn.getAttribute('data-is-delivery-stop') === '1',
                isBulkDispatch: btn.getAttribute('data-is-bulk-dispatch') === '1',
            });
        });
    });

    qsa('.js-vf-bulk').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var bulkAction = btn.getAttribute('data-bulk-action');
            var checked = qsa('.vf-row-check:checked');
            if (checked.length === 0) {
                window.alert('Select at least one order row.');
                return;
            }
            var orderIds = checked.map(function (el) { return el.value; });
            var requiresCheckbox = bulkAction === 'bulk_packaging' || bulkAction === 'bulk_shipped';
            openModal({
                isBulk: true,
                bulkAction: bulkAction,
                orderIds: orderIds,
                label: btn.textContent.trim(),
                requiresCheckbox: requiresCheckbox,
                checkboxLabel: bulkAction === 'bulk_packaging'
                    ? 'I confirm product checked before packaging.'
                    : 'I confirm parcel packed and ready to ship.',
                requiresNote: false,
                isDeliveryStop: false,
            });
        });
    });

    var selectAll = document.getElementById('vfSelectAll');
    if (selectAll) {
        selectAll.addEventListener('change', function () {
            qsa('.vf-row-check').forEach(function (box) {
                box.checked = selectAll.checked;
            });
            updateBulkBar();
        });
    }

    qsa('.vf-row-check').forEach(function (box) {
        box.addEventListener('change', updateBulkBar);
    });

    qsa('.vf-action-menu-toggle').forEach(function (toggle) {
        toggle.addEventListener('click', function (e) {
            e.stopPropagation();
            var panel = toggle.parentElement ? toggle.parentElement.querySelector('.vf-action-menu-panel') : null;
            if (!panel) {
                return;
            }
            var open = !panel.hidden;
            qsa('.vf-action-menu-panel').forEach(function (p) { p.hidden = true; });
            panel.hidden = open;
        });
    });

    document.addEventListener('click', function () {
        qsa('.vf-action-menu-panel').forEach(function (panel) {
            panel.hidden = true;
        });
    });

    qsa('.js-vf-modal-close').forEach(function (btn) {
        btn.addEventListener('click', closeModal);
    });

    var submitBtn = document.getElementById('vfActionModalSubmit');
    if (submitBtn) {
        submitBtn.addEventListener('click', submitPending);
    }

    if (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === modal) {
                closeModal();
            }
        });
    }
}());
