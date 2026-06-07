<?php
$fulfillmentRows = $fulfillmentRows ?? [];
$pagination = $fulfillmentPagination ?? [];
$filters = $fulfillmentFilters ?? [];
$currentPage = max(1, (int) ($pagination['page'] ?? 1));
$totalPages = max(1, (int) ($pagination['total_pages'] ?? 1));
$perPage = (int) ($pagination['per_page'] ?? 20);
$total = (int) ($pagination['total'] ?? 0);
$from = (int) ($pagination['from'] ?? 0);
$to = (int) ($pagination['to'] ?? 0);
$canManage = !empty($canManageWorkflow);
$tableReady = !empty($tableReady);
$statusFilter = $statusFilter ?? null;
$pageQuery = array_filter([
    'status' => $statusFilter,
    'supplier_id' => !empty($isSupplierView) ? null : (($filters['supplier_id'] ?? 0) > 0 ? (string) $filters['supplier_id'] : null),
    'courier_status' => ($filters['courier_status'] ?? '') !== '' ? $filters['courier_status'] : null,
    'date_from' => ($filters['date_from'] ?? '') !== '' ? $filters['date_from'] : null,
    'date_to' => ($filters['date_to'] ?? '') !== '' ? $filters['date_to'] : null,
    'q' => ($filters['q'] ?? '') !== '' ? $filters['q'] : null,
    'per_page' => $perPage !== 20 ? (string) $perPage : null,
], static fn ($v) => $v !== '' && $v !== null);
$rangeLabel = $total > 0
    ? 'Showing ' . $from . '–' . $to . ' of ' . $total . ' orders'
    : 'Showing 0 orders';
?>
<div class="card mb-15 vf-list-card">
    <div class="card-header vf-table-header">
        <div>
            <h2 class="card-title">Vendor Fulfillment</h2>
            <p class="page-description mb-0">Local ERP order snapshot — product cost only. IBS workflow actions do not change OpenCart order status.</p>
        </div>
        <span class="vf-range-label"><?= e($rangeLabel) ?></span>
    </div>
    <div class="card-body">
        <?php if (!$tableReady): ?>
        <p class="page-description">Orders table unavailable. Apply migration 0005 manually first.</p>
        <?php elseif ($total === 0): ?>
        <p class="page-description">
            <?php if ($statusFilter !== null || ($filters['q'] ?? '') !== ''): ?>
            No orders match the current filters. <a href="<?= e(url('/order-workflow')) ?>">Clear filters</a>
            <?php else: ?>
            No orders in vendor fulfillment yet.
            <?php endif; ?>
        </p>
        <?php else: ?>
        <?php if ($canManage): ?>
        <div class="vf-bulk-bar" id="vfBulkBar" hidden>
            <span class="vf-bulk-count"><strong id="vfBulkCount">0</strong> selected</span>
            <button type="button" class="btn btn-secondary btn-sm js-vf-bulk" data-bulk-action="bulk_receive">Bulk Receive Order</button>
            <button type="button" class="btn btn-secondary btn-sm js-vf-bulk" data-bulk-action="bulk_packaging">Bulk Print &amp; Start Packaging</button>
            <button type="button" class="btn btn-secondary btn-sm js-vf-bulk" data-bulk-action="bulk_shipped">Bulk Mark Shipped</button>
            <?php if (!empty($dispatchGateReady)): ?>
            <button type="button" class="btn btn-primary btn-sm js-vf-bulk" data-bulk-action="bulk_dispatch">Bulk Create Dispatch Batch</button>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <div class="table-scroll vf-table-scroll">
            <table class="data-table vf-fulfillment-table">
                <thead>
                    <tr>
                        <?php if ($canManage): ?><th class="vf-col-check"><input type="checkbox" id="vfSelectAll" aria-label="Select all rows"></th><?php endif; ?>
                        <th>Order No</th>
                        <th>Customer</th>
                        <th class="vf-col-product">Product Card</th>
                        <th>Total Qty</th>
                        <th>Total Cost</th>
                        <th>Fulfillment Status</th>
                        <th class="vf-hide-md">Courier Status</th>
                        <th class="vf-hide-md">Consignment ID</th>
                        <th class="vf-hide-md">OC Order Status</th>
                        <th class="vf-col-actions">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($fulfillmentRows as $row): ?>
                    <?php
                    $orderId = (int) ($row['order_id'] ?? 0);
                    $selectable = !empty($row['selectable']);
                    $primary = $row['primary_action'] ?? null;
                    $secondary = $row['secondary_actions'] ?? [];
                    ?>
                    <tr class="vf-row" data-order-id="<?= e((string) $orderId) ?>" data-bulk-key="<?= e((string) ($row['bulk_action_key'] ?? '')) ?>">
                        <?php if ($canManage): ?>
                        <td class="vf-col-check">
                            <?php if ($selectable): ?>
                            <input type="checkbox" class="vf-row-check" name="order_ids[]" value="<?= e((string) $orderId) ?>" aria-label="Select order">
                            <?php endif; ?>
                        </td>
                        <?php endif; ?>
                        <td class="vf-col-order">
                            <strong class="vf-order-no"><?= e((string) ($row['order_no'] ?? '')) ?></strong>
                            <?php if (!empty($row['dispatch_report_reference'])): ?>
                            <span class="vf-batch-ref"><?= e((string) $row['dispatch_report_reference']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="vf-col-customer">
                            <span class="vf-customer-name"><?= e((string) ($row['customer_name'] ?? '')) ?></span>
                            <?php if (!empty($row['customer_phone'])): ?>
                            <span class="vf-customer-phone"><?= e((string) $row['customer_phone']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="vf-col-product">
                            <?php foreach (($row['product_lines'] ?? []) as $line): ?>
                            <div class="vf-product-card">
                                <div class="vf-product-thumb">
                                    <?php if (!empty($line['image_url'])): ?>
                                    <img src="<?= e((string) $line['image_url']) ?>" alt="" loading="lazy">
                                    <?php else: ?>
                                    <span class="vf-product-thumb-empty">—</span>
                                    <?php endif; ?>
                                </div>
                                <div class="vf-product-body">
                                    <strong class="vf-product-model"><?= e((string) ($line['model'] ?? '')) ?></strong>
                                    <span class="vf-product-cost-line">x<?= e((string) ($line['quantity'] ?? 0)) ?> = <?= e(number_format((float) ($line['cost_snapshot'] ?? 0), 2)) ?></span>
                                    <?php foreach (($line['option_chips'] ?? []) as $chip): ?>
                                    <span class="vf-option-chip">
                                        <?= e((string) ($chip['label'] ?? '')) ?>
                                        <?php if (!empty($chip['meta'])): ?>
                                        <em>[<?= e((string) $chip['meta']) ?>]</em>
                                        <?php endif; ?>
                                    </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </td>
                        <td><span class="vf-qty-pill"><?= e((string) ($row['total_quantity'] ?? 0)) ?></span></td>
                        <td class="vf-col-cost">
                            <?php if (!empty($row['missing_cost'])): ?>
                            <span class="badge badge-warn">Missing Cost</span>
                            <?php endif; ?>
                            <strong><?= e(number_format((float) ($row['total_cost_snapshot'] ?? 0), 2)) ?></strong>
                        </td>
                        <td>
                            <span class="badge vf-status-badge <?= e((string) ($row['fulfillment_status_class'] ?? '')) ?>">
                                <?= e((string) ($row['fulfillment_status_label'] ?? '')) ?>
                            </span>
                        </td>
                        <td class="vf-hide-md"><?= e((string) ($row['courier_status'] ?? '—')) ?></td>
                        <td class="vf-hide-md"><?= e((string) ($row['consignment_id'] ?? 'Not Assigned')) ?></td>
                        <td class="vf-hide-md">
                            <?php if (!empty($row['oc_order_status'])): ?>
                            <span class="badge badge-info vf-oc-badge"><?= e((string) $row['oc_order_status']) ?></span>
                            <span class="vf-oc-origin">Origin: <?= e((string) $row['oc_order_status']) ?></span>
                            <?php else: ?>
                            —
                            <?php endif; ?>
                        </td>
                        <td class="vf-col-actions">
                            <?php if ($canManage && ($primary !== null || $secondary !== [])): ?>
                            <div class="vf-row-actions">
                                <?php if ($primary !== null): ?>
                                <button type="button"
                                    class="btn btn-primary btn-sm js-vf-row-action"
                                    data-order-id="<?= e((string) $orderId) ?>"
                                    data-action-code="<?= e((string) ($primary['code'] ?? '')) ?>"
                                    data-action-label="<?= e((string) ($primary['label'] ?? '')) ?>"
                                    data-requires-note="<?= !empty($primary['requires_note']) ? '1' : '0' ?>"
                                    data-requires-checkbox="<?= !empty($primary['requires_checkbox']) ? '1' : '0' ?>"
                                    data-checkbox-label="<?= e((string) ($primary['checkbox_label'] ?? '')) ?>"
                                    data-is-delivery-stop="<?= !empty($primary['is_delivery_stop']) ? '1' : '0' ?>"
                                    data-is-bulk-dispatch="<?= !empty($primary['is_bulk_dispatch']) ? '1' : '0' ?>">
                                    <?= e((string) ($primary['label'] ?? 'Action')) ?>
                                </button>
                                <?php endif; ?>
                                <?php if ($secondary !== []): ?>
                                <div class="vf-action-menu">
                                    <button type="button" class="btn btn-ghost btn-sm vf-action-menu-toggle" aria-label="More actions">⋯</button>
                                    <div class="vf-action-menu-panel" hidden>
                                        <?php foreach ($secondary as $action): ?>
                                        <button type="button"
                                            class="vf-action-menu-item js-vf-row-action"
                                            data-order-id="<?= e((string) $orderId) ?>"
                                            data-action-code="<?= e((string) ($action['code'] ?? '')) ?>"
                                            data-action-label="<?= e((string) ($action['label'] ?? '')) ?>"
                                            data-requires-note="<?= !empty($action['requires_note']) ? '1' : '0' ?>"
                                            data-requires-checkbox="<?= !empty($action['requires_checkbox']) ? '1' : '0' ?>"
                                            data-checkbox-label="<?= e((string) ($action['checkbox_label'] ?? '')) ?>"
                                            data-is-delivery-stop="<?= !empty($action['is_delivery_stop']) ? '1' : '0' ?>"
                                            data-is-bulk-dispatch="<?= !empty($action['is_bulk_dispatch']) ? '1' : '0' ?>">
                                            <?= e((string) ($action['label'] ?? '')) ?>
                                        </button>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php else: ?>
                            <span class="page-description">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
        view('partials.sync-pagination', [
            'page' => $currentPage,
            'pageParam' => 'page',
            'baseUrl' => url('/order-workflow'),
            'pagination' => $pagination,
            'otherPageQuery' => $pageQuery,
        ]);
        ?>
        <?php endif; ?>
    </div>
</div>

<form method="post" action="<?= e(url('/order-workflow/action')) ?>" id="vfActionForm" class="vf-hidden-form">
    <?= $csrfField ?? '' ?>
    <input type="hidden" name="order_id" id="vfActionOrderId" value="">
    <input type="hidden" name="to_status" id="vfActionToStatus" value="">
    <input type="hidden" name="action_confirmed" id="vfActionConfirmed" value="0">
    <input type="hidden" name="staff_confirmation" id="vfStaffConfirmation" value="">
    <input type="hidden" name="action_note" id="vfActionNote" value="">
    <input type="hidden" name="delivery_stop_reason" id="vfDeliveryStopReason" value="">
    <?php if ($statusFilter !== null): ?>
    <input type="hidden" name="return_status" value="<?= e($statusFilter) ?>">
    <?php endif; ?>
</form>

<form method="post" action="<?= e(url('/order-workflow/bulk-action')) ?>" id="vfBulkForm" class="vf-hidden-form">
    <?= $csrfField ?? '' ?>
    <input type="hidden" name="bulk_action" id="vfBulkAction" value="">
    <input type="hidden" name="action_confirmed" id="vfBulkConfirmed" value="0">
    <input type="hidden" name="batch_confirmed" id="vfBatchConfirmed" value="">
    <input type="hidden" name="staff_confirmation" id="vfBulkStaffConfirmation" value="">
    <div id="vfBulkOrderIds"></div>
    <?php if ($statusFilter !== null): ?>
    <input type="hidden" name="return_status" value="<?= e($statusFilter) ?>">
    <?php endif; ?>
</form>

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
                    'options' => $deliveryStopReasonOptions ?? [],
                    'required' => false,
                ]); ?>
            </div>
            <label class="vf-note-label">
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
