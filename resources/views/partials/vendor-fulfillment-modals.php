<?php
$deliveryStopReasonOptions = $deliveryStopReasonOptions ?? [];
$csrfField = $csrfField ?? '';
$dispatchFlash = $dispatchFlash ?? null;
?>
<form method="post" action="<?= e(url('/order-workflow/note')) ?>" id="vfNoteForm" class="vf-hidden-form">
    <?= $csrfField ?>
    <input type="hidden" name="order_id" id="vfNoteOrderId" value="">
    <input type="hidden" name="action_note" id="vfNoteText" value="">
    <?php if (!empty($statusFilter)): ?>
    <input type="hidden" name="return_status" value="<?= e((string) $statusFilter) ?>">
    <?php endif; ?>
</form>

<div class="modal-overlay" id="vfPackModal" hidden>
    <div class="modal-card vf-founder-modal">
        <div class="modal-header">
            <h3 class="modal-title">Print &amp; Move to Processing</h3>
            <button type="button" class="modal-close js-vf-pack-close" aria-label="Close">&times;</button>
        </div>
        <div class="modal-body">
            <dl class="vf-modal-totals">
                <div><dt>Total Orders</dt><dd id="vfPackTotalOrders">0</dd></div>
                <div><dt>Total Quantity</dt><dd id="vfPackTotalQty">0</dd></div>
                <div><dt>Total Products</dt><dd id="vfPackTotalProducts">0</dd></div>
            </dl>
            <p class="page-description vf-pack-notice">One invoice tab will open with all selected invoices. Vendor can scroll, recheck, then use Print All or Ctrl+P.</p>
            <label class="workflow-confirm-checkbox">
                <input type="checkbox" id="vfPackStaffCheck">
                <span>I checked order/product/options and invoice is correct</span>
            </label>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary js-vf-pack-close">Cancel</button>
            <button type="button" class="btn btn-primary" id="vfPackSubmit">Open Invoices &amp; Move to Processing</button>
        </div>
    </div>
</div>

<div class="modal-overlay" id="vfDispatchModal" hidden>
    <div class="modal-card vf-founder-modal">
        <div class="modal-header">
            <h3 class="modal-title">Dispatch Report Created</h3>
            <button type="button" class="modal-close js-vf-dispatch-close" aria-label="Close">&times;</button>
        </div>
        <div class="modal-body">
            <dl class="vf-modal-totals">
                <div><dt>Report No</dt><dd id="vfDispatchRef">—</dd></div>
                <div><dt>Total Orders</dt><dd id="vfDispatchOrders">0</dd></div>
                <div><dt>Total Quantity</dt><dd id="vfDispatchQty">0</dd></div>
                <div><dt>Product Cost Total</dt><dd id="vfDispatchCost">0.00</dd></div>
            </dl>
        </div>
        <div class="modal-footer">
            <a href="#" class="btn btn-primary btn-sm" id="vfDispatchOpenReport" target="_blank" rel="noopener">Open Dispatch Report</a>
            <a href="#" class="btn btn-secondary btn-sm" id="vfDispatchPrint" target="_blank" rel="noopener">Print / Export</a>
            <button type="button" class="btn btn-ghost btn-sm js-vf-dispatch-close">Back to Orders</button>
        </div>
    </div>
</div>

<div class="modal-overlay" id="vfHubReturnModal" hidden>
    <div class="modal-card vf-founder-modal">
        <div class="modal-header">
            <h3 class="modal-title">Confirm Hub Return Receive</h3>
            <button type="button" class="modal-close js-vf-hub-close" aria-label="Close">&times;</button>
        </div>
        <div class="modal-body">
            <p class="page-description">Hub Return is only after physical return from courier/hub — not customer return.</p>
            <label class="workflow-confirm-checkbox">
                <input type="checkbox" id="vfHubParcelCheck">
                <span>Parcel physically received</span>
            </label>
            <label class="workflow-confirm-checkbox">
                <input type="checkbox" id="vfHubQtyCheck">
                <span>Product quantity checked</span>
            </label>
            <label class="workflow-confirm-checkbox vf-recommended-check">
                <input type="checkbox" id="vfHubPackCheck">
                <span>Packaging condition checked (recommended)</span>
            </label>
            <label class="vf-note-label">
                Return note
                <textarea id="vfHubReturnNote" class="form-input" rows="3"></textarea>
            </label>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary js-vf-hub-close">Cancel</button>
            <button type="button" class="btn btn-primary" id="vfHubSubmit">Confirm Warehouse Receive</button>
        </div>
    </div>
</div>

<div class="modal-overlay" id="vfTimelineModal" hidden>
    <div class="modal-card vf-founder-modal vf-timeline-modal">
        <div class="modal-header">
            <h3 class="modal-title" id="vfTimelineTitle">Order timeline</h3>
            <button type="button" class="modal-close js-vf-timeline-close" aria-label="Close">&times;</button>
        </div>
        <div class="modal-body">
            <div id="vfTimelineLoading" class="page-description">Loading…</div>
            <div class="table-scroll" id="vfTimelineTableWrap" hidden>
                <table class="data-table vf-timeline-table">
                    <thead>
                        <tr>
                            <th>From</th>
                            <th>To</th>
                            <th>Action</th>
                            <th>Note</th>
                            <th>Batch</th>
                            <th>User</th>
                            <th>At</th>
                        </tr>
                    </thead>
                    <tbody id="vfTimelineBody"></tbody>
                </table>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary js-vf-timeline-close">Close</button>
        </div>
    </div>
</div>

<div class="modal-overlay" id="vfNoteModal" hidden>
    <div class="modal-card vf-founder-modal">
        <div class="modal-header">
            <h3 class="modal-title">Add note</h3>
            <button type="button" class="modal-close js-vf-note-close" aria-label="Close">&times;</button>
        </div>
        <div class="modal-body">
            <label class="vf-note-label">
                Note
                <textarea id="vfNoteModalText" class="form-input" rows="4" required></textarea>
            </label>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary js-vf-note-close">Cancel</button>
            <button type="button" class="btn btn-primary" id="vfNoteSubmit">Save note</button>
        </div>
    </div>
</div>

<div class="modal-overlay" id="vfActionModal" hidden>
    <div class="modal-card vf-action-modal">
        <div class="modal-header">
            <h3 class="modal-title" id="vfActionModalTitle">Confirm action</h3>
            <button type="button" class="modal-close js-vf-modal-close" aria-label="Close">&times;</button>
        </div>
        <div class="modal-body">
            <p class="page-description" id="vfActionModalDesc"></p>
            <label id="vfCheckboxWrap" class="workflow-confirm-checkbox" hidden>
                <input type="checkbox" id="vfModalStaffCheck">
                <span id="vfCheckboxLabel"></span>
            </label>
            <div id="vfDeliveryStopWrap" hidden>
                <?php view('partials.choice-cards', [
                    'name' => 'vf_delivery_stop_reason_ui',
                    'legend' => 'Delivery Stop reason',
                    'options' => $deliveryStopReasonOptions,
                    'required' => false,
                ]); ?>
            </div>
            <label class="vf-note-label" id="vfActionNoteWrap">
                Note <span id="vfNoteRequiredMark" hidden>*</span>
                <textarea id="vfModalNote" class="form-input" rows="3"></textarea>
            </label>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary js-vf-modal-close">Cancel</button>
            <button type="button" class="btn btn-primary" id="vfActionModalSubmit">Confirm</button>
        </div>
    </div>
</div>

<div class="vf-toast-success" id="vfSuccessToast" hidden role="status"></div>

<?php if (!empty($dispatchFlash)): ?>
<script>
window.__vfDispatchFlash = <?= json_encode($dispatchFlash, JSON_UNESCAPED_UNICODE) ?>;
</script>
<?php endif; ?>
