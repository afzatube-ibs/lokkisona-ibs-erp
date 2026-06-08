(function () {
    'use strict';

    var BULK_LABELS = {
        bulk_receive: 'Bulk Receive Order',
        bulk_packaging: 'Print & Start Packaging',
        bulk_shipped: 'Mark as Shipped',
        bulk_dispatch: 'Create Dispatch Report'
    };

    var modal = document.getElementById('vfActionModal');
    var actionForm = document.getElementById('vfActionForm');
    var bulkForm = document.getElementById('vfBulkForm');
    var noteForm = document.getElementById('vfNoteForm');
    var toolbar = document.getElementById('vfToolbar');
    var pending = null;
    var noteOrderId = null;

    function qs(sel, root) {
        return (root || document).querySelector(sel);
    }

    function qsa(sel, root) {
        return Array.prototype.slice.call((root || document).querySelectorAll(sel));
    }

    function showToast(message) {
        var toast = document.getElementById('vfSuccessToast');
        if (!toast || !message) {
            return;
        }
        toast.textContent = message;
        toast.hidden = false;
        window.setTimeout(function () {
            toast.hidden = true;
        }, 4500);
    }

    function flashFromBanner() {
        var banner = document.querySelector('.alert-success');
        if (banner && banner.textContent) {
            showToast(banner.textContent.trim());
        }
    }

    function openModal(config) {
        if (!modal) {
            return;
        }
        pending = config;
        qs('#vfActionModalTitle', modal).textContent = config.label || 'Confirm action';
        var desc = '';
        if (config.isBulk) {
            desc = 'Apply "' + (config.label || '') + '" to ' + (config.orderIds ? config.orderIds.length : 0) + ' selected order(s)?';
        } else if (config.actionCode === 'order_received') {
            desc = 'Receive this order into Iqbal & Brothers fulfillment? IBS status only — OpenCart order status is not changed.';
        } else if (config.actionCode === 'shipped') {
            desc = 'Confirm parcel is packed and ready to ship. IBS status only — OpenCart order status is not changed.';
        } else if (config.isDispatchCreate) {
            desc = 'Create a dispatch report batch for this shipped order? Selected rows will be locked with an immutable cost snapshot.';
        } else {
            desc = 'Confirm workflow action for order #' + (config.orderId || '') + '. IBS status only — OpenCart order status is not changed.';
        }
        qs('#vfActionModalDesc', modal).textContent = desc;
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

    function submitBulk(bulkAction, orderIds, options) {
        options = options || {};
        if (!bulkForm) {
            return;
        }
        qs('#vfBulkAction', bulkForm).value = bulkAction || '';
        qs('#vfBulkConfirmed', bulkForm).value = '1';
        qs('#vfBulkStaffConfirmation', bulkForm).value = options.staffConfirmed ? '1' : '';
        qs('#vfBatchConfirmed', bulkForm).value = bulkAction === 'bulk_dispatch' ? '1' : '';
        qs('#vfBulkActionNote', bulkForm).value = options.note || '';
        var holder = qs('#vfBulkOrderIds', bulkForm);
        holder.innerHTML = '';
        orderIds.forEach(function (id) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'order_ids[]';
            input.value = String(id);
            holder.appendChild(input);
        });
        bulkForm.submit();
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
            if (pending.bulkAction === 'bulk_packaging') {
                var ids = (pending.orderIds || []).join(',');
                window.open('/invoice-printing?order_ids=' + encodeURIComponent(ids), '_blank', 'noopener');
            }
            submitBulk(pending.bulkAction, pending.orderIds || [], {
                staffConfirmed: pending.requiresCheckbox,
                note: note
            });
            closeModal();
            return;
        }

        if (!actionForm) {
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

    function selectedRows() {
        return qsa('.vf-row-check:checked').map(function (el) {
            var row = el.closest('.vf-row');
            return {
                id: el.value,
                bulkKey: row ? row.getAttribute('data-bulk-key') || '' : '',
                ibsStatus: row ? row.getAttribute('data-ibs-status') || '' : '',
                canHoldCancel: row ? row.getAttribute('data-can-hold-cancel') === '1' : false
            };
        });
    }

    function updateToolbar() {
        var countEl = document.getElementById('vfSelectedCount');
        var forwardBtn = document.getElementById('vfBulkForwardBtn');
        var holdBtn = document.getElementById('vfBulkHoldBtn');
        var cancelBtn = document.getElementById('vfBulkCancelBtn');
        var hint = document.getElementById('vfBulkHint');
        var selected = selectedRows();
        if (countEl) {
            countEl.textContent = String(selected.length);
        }
        if (!forwardBtn) {
            return;
        }

        if (selected.length === 0) {
            forwardBtn.hidden = true;
            if (holdBtn) holdBtn.hidden = true;
            if (cancelBtn) cancelBtn.hidden = true;
            if (hint) hint.hidden = true;
            return;
        }

        var statuses = {};
        var bulkKeys = {};
        var allHoldCancel = true;
        selected.forEach(function (row) {
            statuses[row.ibsStatus] = true;
            if (row.bulkKey) {
                bulkKeys[row.bulkKey] = true;
            }
            if (!row.canHoldCancel) {
                allHoldCancel = false;
            }
        });
        var statusKeys = Object.keys(statuses);
        var homogeneous = statusKeys.length === 1;
        var bulkKey = homogeneous ? (selected[0].bulkKey || '') : '';
        var filterBulk = toolbar ? toolbar.getAttribute('data-bulk-filter') || '' : '';

        if (homogeneous && bulkKey) {
            forwardBtn.hidden = false;
            forwardBtn.setAttribute('data-bulk-action', bulkKey);
            forwardBtn.textContent = BULK_LABELS[bulkKey] || 'Bulk action';
            if (hint) hint.hidden = true;
        } else if (homogeneous && filterBulk && filterBulk === selected[0].bulkKey) {
            forwardBtn.hidden = false;
            forwardBtn.setAttribute('data-bulk-action', filterBulk);
            forwardBtn.textContent = BULK_LABELS[filterBulk] || 'Bulk action';
            if (hint) hint.hidden = true;
        } else {
            forwardBtn.hidden = true;
            if (hint) hint.hidden = !homogeneous;
        }

        if (holdBtn) {
            holdBtn.hidden = !(homogeneous && allHoldCancel);
        }
        if (cancelBtn) {
            cancelBtn.hidden = !(homogeneous && (allHoldCancel || statusKeys[0] === 'hold'));
        }
    }

    function fetchSelectionPreview(orderIds, callback) {
        if (orderIds.length === 0) {
            callback(null);
            return;
        }
        var xhr = new XMLHttpRequest();
        xhr.open('GET', '/order-workflow/selection-preview?ids=' + encodeURIComponent(orderIds.join(',')));
        xhr.onload = function () {
            try {
                callback(JSON.parse(xhr.responseText));
            } catch (e) {
                callback(null);
            }
        };
        xhr.onerror = function () { callback(null); };
        xhr.send();
    }

    function openPackModal(orderIds) {
        var packModal = document.getElementById('vfPackModal');
        if (!packModal) {
            submitBulk('bulk_packaging', orderIds, { staffConfirmed: true });
            return;
        }
        fetchSelectionPreview(orderIds, function (data) {
            qs('#vfPackTotalOrders', packModal).textContent = String(data ? data.order_count : orderIds.length);
            qs('#vfPackTotalQty', packModal).textContent = String(data ? data.total_quantity : 0);
            qs('#vfPackTotalProducts', packModal).textContent = String(data ? data.total_products : 0);
            qs('#vfPackStaffCheck', packModal).checked = false;
            packModal.hidden = false;
            packModal._orderIds = orderIds;
        });
    }

    function openDispatchConfirmModal(orderIds) {
        openModal({
            isBulk: true,
            bulkAction: 'bulk_dispatch',
            orderIds: orderIds,
            label: BULK_LABELS.bulk_dispatch,
            requiresCheckbox: false,
            requiresNote: false,
            isDeliveryStop: false
        });
    }

    function openBulkForward(bulkAction, orderIds) {
        if (bulkAction === 'bulk_packaging') {
            openPackModal(orderIds);
            return;
        }
        if (bulkAction === 'bulk_dispatch') {
            openDispatchConfirmModal(orderIds);
            return;
        }
        var requiresCheckbox = bulkAction === 'bulk_shipped';
        openModal({
            isBulk: true,
            bulkAction: bulkAction,
            orderIds: orderIds,
            label: BULK_LABELS[bulkAction] || 'Bulk action',
            requiresCheckbox: requiresCheckbox,
            checkboxLabel: 'I confirm parcel packed and ready to ship.',
            requiresNote: false,
            isDeliveryStop: false
        });
    }

    function openHubReturnModal(orderId) {
        var hubModal = document.getElementById('vfHubReturnModal');
        if (!hubModal) {
            return;
        }
        hubModal.hidden = false;
        hubModal._orderId = orderId;
        qs('#vfHubParcelCheck', hubModal).checked = false;
        qs('#vfHubQtyCheck', hubModal).checked = false;
        qs('#vfHubPackCheck', hubModal).checked = false;
        qs('#vfHubReturnNote', hubModal).value = '';
    }

    function openTimelineModal(orderId) {
        var timelineModal = document.getElementById('vfTimelineModal');
        if (!timelineModal) {
            return;
        }
        timelineModal.hidden = false;
        qs('#vfTimelineLoading', timelineModal).hidden = false;
        qs('#vfTimelineTableWrap', timelineModal).hidden = true;
        qs('#vfTimelineTitle', timelineModal).textContent = 'Order #' + orderId + ' timeline';
        var xhr = new XMLHttpRequest();
        xhr.open('GET', '/order-workflow/history?id=' + encodeURIComponent(String(orderId)));
        xhr.onload = function () {
            qs('#vfTimelineLoading', timelineModal).hidden = true;
            var body = qs('#vfTimelineBody', timelineModal);
            body.innerHTML = '';
            try {
                var data = JSON.parse(xhr.responseText);
                qs('#vfTimelineTitle', timelineModal).textContent = (data.order_reference || ('#' + orderId)) + ' timeline';
                (data.rows || []).forEach(function (row) {
                    var tr = document.createElement('tr');
                    tr.innerHTML = '<td>' + (row.from_label || '') + '</td><td>' + (row.to_label || '') + '</td><td>' + (row.action_note || '—') + '</td><td>' + (row.changed_by || '—') + '</td><td>' + (row.changed_at || '') + '</td>';
                    body.appendChild(tr);
                });
                qs('#vfTimelineTableWrap', timelineModal).hidden = false;
            } catch (e) {
                qs('#vfTimelineLoading', timelineModal).textContent = 'Could not load timeline.';
            }
        };
        xhr.send();
    }

    function openNoteModal(orderId) {
        noteOrderId = orderId;
        var noteModal = document.getElementById('vfNoteModal');
        if (!noteModal) {
            return;
        }
        qs('#vfNoteModalText', noteModal).value = '';
        noteModal.hidden = false;
    }

    qsa('.js-vf-row-action').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var code = btn.getAttribute('data-action-code') || '';
            var orderId = btn.getAttribute('data-order-id');
            if (btn.getAttribute('data-is-menu-only') === '1') {
                if (code === 'view_timeline') {
                    openTimelineModal(orderId);
                    return;
                }
                if (code === 'add_note') {
                    openNoteModal(orderId);
                    return;
                }
            }
            if (btn.getAttribute('data-is-hub-return') === '1') {
                openHubReturnModal(orderId);
                return;
            }
            if (code === 'create_dispatch_report') {
                openDispatchConfirmModal([orderId]);
                return;
            }
            openModal({
                orderId: orderId,
                actionCode: code,
                label: btn.getAttribute('data-action-label'),
                requiresNote: btn.getAttribute('data-requires-note') === '1',
                requiresCheckbox: btn.getAttribute('data-requires-checkbox') === '1',
                checkboxLabel: btn.getAttribute('data-checkbox-label') || '',
                isDeliveryStop: btn.getAttribute('data-is-delivery-stop') === '1',
                isDispatchCreate: btn.getAttribute('data-is-dispatch-create') === '1',
                isBulkDispatch: false
            });
        });
    });

    var forwardBtn = document.getElementById('vfBulkForwardBtn');
    if (forwardBtn) {
        forwardBtn.addEventListener('click', function () {
            var selected = selectedRows();
            if (selected.length === 0) {
                window.alert('Select at least one order row.');
                return;
            }
            openBulkForward(forwardBtn.getAttribute('data-bulk-action'), selected.map(function (r) { return r.id; }));
        });
    }

    var holdBtn = document.getElementById('vfBulkHoldBtn');
    if (holdBtn) {
        holdBtn.addEventListener('click', function () {
            var ids = selectedRows().map(function (r) { return r.id; });
            if (ids.length === 0) {
                return;
            }
            var note = window.prompt('Hold reason (required):');
            if (note === null) {
                return;
            }
            if (note.trim() === '') {
                window.alert('Hold reason is required.');
                return;
            }
            submitBulk('bulk_hold', ids, { note: note.trim() });
        });
    }

    var cancelBtn = document.getElementById('vfBulkCancelBtn');
    if (cancelBtn) {
        cancelBtn.addEventListener('click', function () {
            var ids = selectedRows().map(function (r) { return r.id; });
            if (ids.length === 0) {
                return;
            }
            var note = window.prompt('Cancel reason (required):');
            if (note === null) {
                return;
            }
            if (note.trim() === '') {
                window.alert('Cancel reason is required.');
                return;
            }
            submitBulk('bulk_cancel', ids, { note: note.trim() });
        });
    }

    var selectAll = document.getElementById('vfSelectAll');
    if (selectAll) {
        selectAll.addEventListener('change', function () {
            qsa('.vf-row-check').forEach(function (box) {
                box.checked = selectAll.checked;
            });
            updateToolbar();
        });
    }

    qsa('.vf-row-check').forEach(function (box) {
        box.addEventListener('change', updateToolbar);
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

    qsa('.js-vf-pack-close').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var packModal = document.getElementById('vfPackModal');
            if (packModal) packModal.hidden = true;
        });
    });

    var packSubmit = document.getElementById('vfPackSubmit');
    if (packSubmit) {
        packSubmit.addEventListener('click', function () {
            var packModal = document.getElementById('vfPackModal');
            if (!packModal || !packModal._orderIds) {
                return;
            }
            if (!qs('#vfPackStaffCheck', packModal).checked) {
                window.alert('Confirm invoice check before moving to Processing.');
                return;
            }
            var ids = packModal._orderIds.join(',');
            window.open('/invoice-printing?order_ids=' + encodeURIComponent(ids), '_blank', 'noopener');
            submitBulk('bulk_packaging', packModal._orderIds, { staffConfirmed: true });
            packModal.hidden = true;
        });
    }

    qsa('.js-vf-dispatch-close').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var dispatchModal = document.getElementById('vfDispatchModal');
            if (dispatchModal) dispatchModal.hidden = true;
        });
    });

    qsa('.js-vf-hub-close').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var hubModal = document.getElementById('vfHubReturnModal');
            if (hubModal) hubModal.hidden = true;
        });
    });

    var hubSubmit = document.getElementById('vfHubSubmit');
    if (hubSubmit) {
        hubSubmit.addEventListener('click', function () {
            var hubModal = document.getElementById('vfHubReturnModal');
            if (!hubModal || !hubModal._orderId) {
                return;
            }
            if (!qs('#vfHubParcelCheck', hubModal).checked || !qs('#vfHubQtyCheck', hubModal).checked) {
                window.alert('Parcel received and quantity checked are required.');
                return;
            }
            var note = qs('#vfHubReturnNote', hubModal).value.trim();
            if (note === '') {
                window.alert('Return note is required.');
                return;
            }
            if (!actionForm) {
                return;
            }
            qs('#vfActionOrderId', actionForm).value = String(hubModal._orderId);
            qs('#vfActionToStatus', actionForm).value = 'hub_return';
            qs('#vfActionConfirmed', actionForm).value = '1';
            qs('#vfStaffConfirmation', actionForm).value = '';
            qs('#vfActionNote', actionForm).value = note;
            qs('#vfDeliveryStopReason', actionForm).value = '';
            actionForm.submit();
        });
    }

    qsa('.js-vf-timeline-close').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var timelineModal = document.getElementById('vfTimelineModal');
            if (timelineModal) timelineModal.hidden = true;
        });
    });

    qsa('.js-vf-note-close').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var noteModal = document.getElementById('vfNoteModal');
            if (noteModal) noteModal.hidden = true;
        });
    });

    var noteSubmit = document.getElementById('vfNoteSubmit');
    if (noteSubmit) {
        noteSubmit.addEventListener('click', function () {
            var noteModal = document.getElementById('vfNoteModal');
            var text = noteModal ? qs('#vfNoteModalText', noteModal).value.trim() : '';
            if (!noteForm || !noteOrderId || text === '') {
                window.alert('Note text is required.');
                return;
            }
            qs('#vfNoteOrderId', noteForm).value = String(noteOrderId);
            qs('#vfNoteText', noteForm).value = text;
            noteForm.submit();
        });
    }

    if (window.__vfDispatchFlash) {
        var flash = window.__vfDispatchFlash;
        var dispatchModal = document.getElementById('vfDispatchModal');
        if (dispatchModal) {
            qs('#vfDispatchRef', dispatchModal).textContent = flash.reference || '—';
            qs('#vfDispatchOrders', dispatchModal).textContent = String(flash.total_orders || 0);
            qs('#vfDispatchQty', dispatchModal).textContent = String(flash.total_qty || 0);
            qs('#vfDispatchCost', dispatchModal).textContent = Number(flash.total_product_cost || 0).toFixed(2);
            var openReport = qs('#vfDispatchOpenReport', dispatchModal);
            var printLink = qs('#vfDispatchPrint', dispatchModal);
            if (openReport && flash.report_id) {
                openReport.href = '/dispatch-reports?report_id=' + flash.report_id;
            }
            if (printLink && flash.report_id) {
                printLink.href = '/dispatch-reports?report_id=' + flash.report_id + '&print=1';
            }
            dispatchModal.hidden = false;
        }
    }

    flashFromBanner();
    updateToolbar();
}());
