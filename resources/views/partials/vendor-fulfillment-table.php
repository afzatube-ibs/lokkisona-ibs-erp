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
?>
<div class="vf-table-panel">
    <?php if (!$tableReady): ?>
    <p class="page-description vf-table-empty">Orders table unavailable. Apply migration 0005 manually first.</p>
    <?php elseif ($total === 0): ?>
    <p class="page-description vf-table-empty">
            <?php if ($statusFilter !== null || ($filters['q'] ?? '') !== ''): ?>
            No orders match the current filters. <a href="<?= e(url('/order-workflow')) ?>">Clear filters</a>
            <?php else: ?>
            No orders in vendor fulfillment yet.
            <?php endif; ?>
        </p>
        <?php else: ?>
        <div class="table-scroll vf-table-scroll">
            <table class="data-table vf-fulfillment-table">
                <thead>
                    <tr>
                        <?php if ($canManage): ?><th class="vf-col-check"><input type="checkbox" id="vfSelectAll" aria-label="Select all rows"></th><?php endif; ?>
                        <th>Order No</th>
                        <th>Customer</th>
                        <th class="vf-col-product">Product Card</th>
                        <th>Qty</th>
                        <th>Cost</th>
                        <th>Fulfillment Status</th>
                        <th>Courier Status</th>
                        <th>Consignment</th>
                        <th>OC Status</th>
                        <th class="vf-col-actions">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($fulfillmentRows as $row): ?>
                    <?php
                    $orderId = (int) ($row['order_id'] ?? 0);
                    $selectable = !empty($row['selectable']);
                    $primary = $row['primary_action'] ?? null;
                    $menuItems = $row['menu_items'] ?? [];
                    $ibsStatus = (string) ($row['ibs_status_raw'] ?? ($row['fulfillment_status'] ?? ''));
                    ?>
                    <tr class="vf-row" data-order-id="<?= e((string) $orderId) ?>" data-bulk-key="<?= e((string) ($row['bulk_action_key'] ?? '')) ?>" data-ibs-status="<?= e($ibsStatus) ?>" data-can-hold-cancel="<?= !empty($row['can_hold_cancel']) ? '1' : '0' ?>">
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
                            <?php if (!empty($row['created_report_note'])): ?>
                            <span class="vf-created-report-note"><?= e((string) $row['created_report_note']) ?></span>
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
                                    <span class="vf-option-chip<?= !empty($chip['empty_option']) ? ' vf-option-chip-empty' : '' ?>">
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
                        <td><?= e((string) ($row['courier_status'] ?? '—')) ?></td>
                        <td><?= e((string) ($row['consignment_id'] ?? 'Not Assigned')) ?></td>
                        <td>
                            <?php if (!empty($row['oc_order_status'])): ?>
                            <span class="badge badge-info vf-oc-badge"><?= e((string) $row['oc_order_status']) ?></span>
                            <span class="vf-oc-origin">Origin: <?= e((string) $row['oc_order_status']) ?></span>
                            <?php else: ?>
                            —
                            <?php endif; ?>
                        </td>
                        <td class="vf-col-actions">
                            <div class="vf-row-actions">
                                <button type="button"
                                    class="btn btn-ghost btn-sm vf-timeline-btn js-vf-timeline-open"
                                    data-order-id="<?= e((string) $orderId) ?>"
                                    title="Workflow timeline"
                                    aria-label="Workflow timeline">⏱</button>
                            <?php if ($canManage && ($primary !== null || $menuItems !== [])): ?>
                                <?php if ($primary !== null && !empty($primary['is_link']) && !empty($primary['url'])): ?>
                                <a href="<?= e((string) $primary['url']) ?>" class="btn btn-primary btn-sm"><?= e((string) ($primary['label'] ?? 'View Report')) ?></a>
                                <?php elseif ($primary !== null): ?>
                                <button type="button"
                                    class="btn btn-primary btn-sm js-vf-row-action"
                                    data-order-id="<?= e((string) $orderId) ?>"
                                    data-action-code="<?= e((string) ($primary['code'] ?? '')) ?>"
                                    data-action-label="<?= e((string) ($primary['label'] ?? '')) ?>"
                                    data-requires-note="<?= !empty($primary['requires_note']) ? '1' : '0' ?>"
                                    data-requires-checkbox="<?= !empty($primary['requires_checkbox']) ? '1' : '0' ?>"
                                    data-checkbox-label="<?= e((string) ($primary['checkbox_label'] ?? '')) ?>"
                                    data-is-delivery-stop="<?= !empty($primary['is_delivery_stop']) ? '1' : '0' ?>"
                                    data-is-hub-return="<?= !empty($primary['is_hub_return']) ? '1' : '0' ?>"
                                    data-is-dispatch-create="<?= !empty($primary['is_dispatch_create']) ? '1' : '0' ?>">
                                    <?= e((string) ($primary['label'] ?? 'Action')) ?>
                                </button>
                                <?php endif; ?>
                                <?php if ($menuItems !== []): ?>
                                <div class="vf-action-menu">
                                    <button type="button" class="btn btn-ghost btn-sm vf-action-menu-toggle" aria-label="More actions">⋯</button>
                                    <div class="vf-action-menu-panel" hidden>
                                        <?php foreach ($menuItems as $action): ?>
                                        <button type="button"
                                            class="vf-action-menu-item js-vf-row-action"
                                            data-order-id="<?= e((string) $orderId) ?>"
                                            data-action-code="<?= e((string) ($action['code'] ?? '')) ?>"
                                            data-action-label="<?= e((string) ($action['label'] ?? '')) ?>"
                                            data-requires-note="<?= !empty($action['requires_note']) ? '1' : '0' ?>"
                                            data-requires-checkbox="<?= !empty($action['requires_checkbox']) ? '1' : '0' ?>"
                                            data-checkbox-label="<?= e((string) ($action['checkbox_label'] ?? '')) ?>"
                                            data-is-delivery-stop="<?= !empty($action['is_delivery_stop']) ? '1' : '0' ?>"
                                            data-is-hub-return="<?= !empty($action['is_hub_return']) ? '1' : '0' ?>"
                                            data-is-menu-only="<?= !empty($action['menu_only']) ? '1' : '0' ?>">
                                            <?= e((string) ($action['label'] ?? '')) ?>
                                        </button>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            <?php elseif (!$canManage): ?>
                            <span class="page-description">—</span>
                            <?php endif; ?>
                            </div>
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
    <input type="hidden" name="action_note" id="vfBulkActionNote" value="">
    <div id="vfBulkOrderIds"></div>
    <?php if ($statusFilter !== null): ?>
    <input type="hidden" name="return_status" value="<?= e($statusFilter) ?>">
    <?php endif; ?>
</form>
