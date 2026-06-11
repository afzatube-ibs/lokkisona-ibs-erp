<?php use App\Domain\SupplierTerminology; ?>
<div class="page-header page-header-compact">
    <h1 class="page-title">Daily Dispatch Batch</h1>
    <p class="ops-page-subtitle"><?= !empty($isSupplierView) ? 'Batch reports created by Lokkisona — locked cost snapshots for your account.' : 'Daily dispatch batches from shipped orders — immutable cost snapshots, supplier payable checkpoint.' ?></p>
</div>

<?php view('partials.flash-messages', ['flashSuccess' => $flashSuccess ?? null, 'flashError' => $flashError ?? null]); ?>

<?php view('partials.ops-safety-strip', ['message' => !empty($isSupplierView) ? 'Cost snapshots are locked when the batch is created — they do not change if catalog prices change later.' : 'No stock deducted · No OpenCart writes · Cost snapshot immutable once locked']); ?>

<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header"><h2 class="card-title">Dispatch Batch List</h2></div>
    <div class="card-body">
        <?php if (!empty($latestReports)): ?>
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Batch Reference</th>
                        <th>Supplier</th>
                        <th>Created Date</th>
                        <th>Created By</th>
                        <th>Orders</th>
                        <th>Total Qty</th>
                        <th><?= !empty($isSupplierView) ? e(SupplierTerminology::totalSaleSnapshot()) : 'Total Cost Snapshot' ?></th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($latestReports as $report): ?>
                    <?php
                    $ref = (string) ($report['dispatch_reference'] ?? '');
                    $viewUrl = $ref !== '' ? url('/dispatch-report/' . rawurlencode($ref)) : '#';
                    $printUrl = $ref !== '' ? url('/dispatch-report/' . rawurlencode($ref) . '?print=1') : '#';
                    ?>
                    <tr>
                        <td><strong><?= e($ref) ?></strong></td>
                        <td><?= e((string) ($report['supplier_name'] ?? '—')) ?></td>
                        <td><?= e((string) ($report['created_at'] ?? $report['dispatch_date'] ?? '—')) ?></td>
                        <td><?= e((string) ($report['created_by_label'] ?? '—')) ?></td>
                        <td><?= e((string) ($report['total_orders'] ?? '0')) ?></td>
                        <td><?= e((string) ($report['total_qty'] ?? '0')) ?></td>
                        <td><?= e((string) ($report['total_product_cost'] ?? '0.00')) ?></td>
                        <td><span class="badge badge-ok"><?= e((string) ($report['status_label'] ?? 'Created / Locked')) ?></span></td>
                        <td>
                            <a href="<?= e($viewUrl) ?>" class="btn btn-sm btn-secondary">View</a>
                            <a href="<?= e($printUrl) ?>" class="btn btn-sm btn-ghost" target="_blank" rel="noopener">Print</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state"><p>No dispatch reports yet. Create one from Vendor Fulfillment (Shipped) or the form below.</p></div>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($canManageDispatch) && !empty($writeGateReady)): ?>
<div class="card">
    <div class="card-header"><h2 class="card-title">Create Dispatch Report</h2></div>
    <div class="card-body">
        <p class="page-description" style="margin-bottom: 1rem;">Select shipped orders not yet in a dispatch report. Reference format: DDMMYYYY-P1, DDMMYYYY-P2, etc. Orders with missing product cost are blocked until Product Control is updated.</p>
        <?php if (!empty($eligibleOrders)): ?>
        <form method="post" action="<?= e(url('/dispatch-reports/create')) ?>" class="js-dispatch-batch-form" data-confirm-label="Create dispatch report">
            <?= $csrfField ?? '' ?>
            <input type="hidden" name="batch_confirmed" value="0" class="js-batch-confirmed">
            <div class="table-scroll">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Select</th>
                            <th>Order</th>
                            <th>Customer</th>
                            <th>Qty</th>
                            <th>Cost Snapshot</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($eligibleOrders as $order): ?>
                        <tr class="js-dispatch-order-row"
                            data-qty="<?= e((string) ($order['preview_item_count'] ?? '0')) ?>"
                            data-cost="<?= e((string) ($order['preview_cost_snapshot'] ?? '0.00')) ?>"
                            data-missing-cost="<?= !empty($order['missing_cost']) ? '1' : '0' ?>"
                            data-courier="<?= e((string) ($order['courier_status'] ?? '')) ?>">
                            <td><input type="checkbox" name="order_ids[]" value="<?= e((string) ($order['order_id'] ?? '')) ?>" class="js-dispatch-order-select"></td>
                            <td><?= e((string) ($order['order_reference'] ?? $order['order_id'] ?? '')) ?></td>
                            <td><?= e((string) ($order['customer_name'] ?? '')) ?></td>
                            <td><?= e((string) ($order['preview_item_count'] ?? '0')) ?></td>
                            <td>
                                <?php if (!empty($order['missing_cost'])): ?>
                                <span class="badge badge-warn">Missing Cost</span>
                                <?php endif; ?>
                                <?= e((string) ($order['preview_cost_snapshot'] ?? '0.00')) ?>
                            </td>
                            <td><?= e((string) ($order['courier_status'] ?? 'Shipped')) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="js-dispatch-batch-summary workflow-info-banner" style="margin-top: 1rem;">Batch summary: 0 orders · 0 qty · 0.00 product cost</div>
            <div class="js-dispatch-missing-cost-warning workflow-info-banner" style="margin-top: 0.75rem; display: none; color: var(--color-warning, #b45309);"></div>
            <label class="workflow-confirm-checkbox" style="margin-top: 1rem;">
                <input type="checkbox" name="batch_confirm_checkbox" value="1" required>
                <span>I confirm this dispatch report snapshots supplier cost now. Normal workflow will be locked for included orders.</span>
            </label>
            <button type="submit" class="btn btn-primary js-dispatch-submit-btn" style="margin-top: 1rem;">Create Dispatch Report</button>
        </form>
        <?php else: ?>
        <p class="page-description"><span class="badge badge-warn">No eligible orders</span> No shipped orders are waiting for dispatch report, or all shipped orders are already included.</p>
        <?php endif; ?>
    </div>
</div>
<?php elseif (!empty($canManageDispatch)): ?>
<?php view('partials.write-gate-warning', ['writeGateReady' => $writeGateReady ?? false, 'writeGate' => $writeGate ?? []]); ?>
<?php endif; ?>
