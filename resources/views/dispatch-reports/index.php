<?php use App\Domain\SupplierTerminology; ?>
<div class="page-header page-header-compact">
    <h1 class="page-title">Dispatch Reports</h1>
    <p class="ops-page-subtitle"><?= !empty($isSupplierView) ? 'Batch reports created by Lokkisona — locked sale amounts for your account.' : 'Daily dispatch batches from shipped orders — max 50 per batch, locked cost snapshots.' ?></p>
</div>

<?php view('partials.flash-messages', ['flashSuccess' => $flashSuccess ?? null, 'flashError' => $flashError ?? null]); ?>

<?php view('partials.ops-safety-strip', ['message' => !empty($isSupplierView) ? 'Sale amounts are locked when owner creates the batch — they do not change if catalog prices change later.' : 'No payable · No stock deducted · No invoice · No live sync · Cost snapshot is immutable once locked']); ?>

<?php if (!empty($canManageDispatch) && !empty($writeGateReady)): ?>
<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header"><h2 class="card-title">Create Daily Dispatch Report</h2></div>
    <div class="card-body">
        <p class="page-description" style="margin-bottom: 1rem;">Select shipped orders not yet in a dispatch report. Reference format: DDMMYYYY or DDMMYYYY-P1/P2. Dispatch Report is the official supplier payable checkpoint — payable starts here, not at Delivered.</p>
        <?php if (!empty($eligibleOrders)): ?>
        <form method="post" action="<?= e(url('/dispatch-reports/create')) ?>" class="js-dispatch-batch-form" data-confirm-label="Create daily dispatch report">
            <?= $csrfField ?? '' ?>
            <input type="hidden" name="batch_confirmed" value="0" class="js-batch-confirmed">
            <div class="table-scroll">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Select</th>
                            <th>Order ID</th>
                            <th>Reference</th>
                            <th>Customer</th>
                            <th>Qty</th>
                            <th>Cost Snapshot Preview</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($eligibleOrders as $order): ?>
                        <tr class="js-dispatch-order-row" data-qty="<?= e((string) ($order['preview_item_count'] ?? '0')) ?>" data-cost="<?= e((string) ($order['preview_cost_snapshot'] ?? '0.00')) ?>" data-courier="<?= e((string) ($order['courier_status'] ?? '')) ?>">
                            <td><input type="checkbox" name="order_ids[]" value="<?= e((string) ($order['order_id'] ?? '')) ?>" class="js-dispatch-order-select"></td>
                            <td><?= e((string) ($order['order_id'] ?? '')) ?></td>
                            <td><?= e((string) ($order['order_reference'] ?? '')) ?></td>
                            <td><?= e((string) ($order['customer_name'] ?? '')) ?></td>
                            <td><?= e((string) ($order['preview_item_count'] ?? '0')) ?></td>
                            <td><?= e((string) ($order['preview_cost_snapshot'] ?? '0.00')) ?></td>
                            <td><?= e((string) ($order['courier_status'] ?? 'Shipped')) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="js-dispatch-batch-summary workflow-info-banner" style="margin-top: 1rem;">Batch summary: 0 orders · 0 qty · 0.00 product cost · courier pending selection</div>
            <label class="workflow-confirm-checkbox" style="margin-top: 1rem;">
                <input type="checkbox" name="batch_confirm_checkbox" value="1" required>
                <span>I confirm this dispatch report snapshots supplier cost now and does not create payable, stock, invoice, or sync actions.</span>
            </label>
            <button type="submit" class="btn btn-primary" style="margin-top: 1rem;">Create Dispatch Report</button>
        </form>
        <?php else: ?>
        <p class="page-description"><span class="badge badge-warn">No eligible orders</span> No shipped orders are waiting for dispatch report, or all shipped orders are already included.</p>
        <?php endif; ?>
    </div>
</div>
<?php else: ?>
<?php view('partials.write-gate-warning', ['writeGateReady' => $writeGateReady ?? false, 'writeGate' => $writeGate ?? []]); ?>
<?php endif; ?>

<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header"><h2 class="card-title">Latest Dispatch Reports</h2></div>
    <div class="card-body">
        <?php if (!empty($latestReports)): ?>
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Vendor</th>
                        <th>Date</th>
                        <th>Orders</th>
                        <th>Qty</th>
                        <th><?= !empty($isSupplierView) ? e(SupplierTerminology::totalSaleSnapshot()) : 'Total Vendor Cost' ?></th>
                        <th>Status</th>
                        <th>Print</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($latestReports as $report): ?>
                    <?php $reportId = (int) ($report['dispatch_report_id'] ?? 0); ?>
                    <tr>
                        <td><strong><?= e((string) ($report['dispatch_reference'] ?? '')) ?></strong></td>
                        <td><?= e((string) ($report['vendor_name'] ?? '—')) ?></td>
                        <td><?= e((string) ($report['dispatch_date'] ?? '')) ?></td>
                        <td><?= e((string) ($report['total_orders'] ?? '0')) ?></td>
                        <td><?= e((string) ($report['total_qty'] ?? '0')) ?></td>
                        <td><?= e((string) ($report['total_product_cost'] ?? '0.00')) ?></td>
                        <td><span class="badge <?= ($report['status'] ?? '') === 'locked' ? 'badge-ok' : 'badge-warn' ?>"><?= e((string) ($report['status_label'] ?? $report['status'] ?? '')) ?></span></td>
                        <td>
                            <a href="<?= e(url('/dispatch-reports?report_id=' . $reportId . '&print=1')) ?>" class="btn btn-sm btn-secondary" target="_blank" rel="noopener">Print</a>
                            <a href="<?= e(url('/dispatch-reports?report_id=' . $reportId)) ?>" class="btn btn-sm btn-ghost">View</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state"><p>No dispatch reports yet. Create one above from shipped orders.</p></div>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($reportDetail['report'])): ?>
<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header"><h2 class="card-title">Report Detail: <?= e((string) ($reportDetail['report']['dispatch_reference'] ?? '')) ?></h2></div>
    <div class="card-body">
        <dl class="info-list" style="margin-bottom: 1rem;">
            <div class="info-row"><dt>Reference</dt><dd><?= e((string) ($reportDetail['report']['dispatch_reference'] ?? '')) ?></dd></div>
            <div class="info-row"><dt>Total Orders</dt><dd><?= e((string) ($reportDetail['report']['total_orders'] ?? '0')) ?></dd></div>
            <div class="info-row"><dt><?= !empty($isSupplierView) ? e(SupplierTerminology::totalSaleSnapshot()) : 'Total Cost Snapshot' ?></dt><dd><?= e((string) ($reportDetail['report']['total_product_cost'] ?? '0.00')) ?></dd></div>
            <div class="info-row"><dt>Status</dt><dd><span class="badge <?= ($reportDetail['report']['status'] ?? '') === 'locked' ? 'badge-ok' : 'badge-warn' ?>"><?= e((string) ($reportDetail['report']['status_label'] ?? $reportDetail['report']['status'] ?? '')) ?></span></dd></div>
            <?php if (!empty($reportDetail['report']['locked_at'])): ?>
            <div class="info-row"><dt>Locked At</dt><dd><?= e((string) $reportDetail['report']['locked_at']) ?></dd></div>
            <?php endif; ?>
            <div class="info-row"><dt>Created</dt><dd><?= e((string) ($reportDetail['report']['created_at'] ?? '')) ?></dd></div>
        </dl>
        <?php foreach ($reportDetail['items'] ?? [] as $item): ?>
        <div class="card workflow-order-card" style="margin-bottom: 1rem;">
            <div class="card-body">
                <p><strong>Order <?= e((string) ($item['order_reference'] ?? $item['erp_order_reference'] ?? '')) ?></strong> — <?= e((string) ($item['customer_name'] ?? '')) ?></p>
                <p class="page-description"><?= !empty($isSupplierView) ? 'Stored sale amount' : 'Stored cost snapshot' ?>: <strong><?= e((string) ($item['product_cost_snapshot'] ?? '0.00')) ?></strong> · Qty: <?= e((string) ($item['item_count'] ?? '0')) ?> · Status: <?= e((string) ($item['ibs_status'] ?? '')) ?></p>
                <?php if (!empty($item['product_lines'])): ?>
                <div class="table-scroll">
                    <table class="data-table">
                        <thead><tr><th>Product</th><th>Variant</th><th>Qty</th><th><?= !empty($isSupplierView) ? e(SupplierTerminology::lineSaleSnapshot()) : 'Line supplier_cost_snapshot (informational)' ?></th></tr></thead>
                        <tbody>
                            <?php foreach ($item['product_lines'] as $line): ?>
                            <tr>
                                <td><?= e((string) ($line['product_name'] ?? '')) ?></td>
                                <td><?= e((string) ($line['variant_label'] ?? '-')) ?></td>
                                <td><?= e((string) ($line['quantity'] ?? '')) ?></td>
                                <td><?= e((string) ($line['supplier_cost_snapshot'] ?? '')) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php if (empty($isSupplierView)): ?>
<details class="planning-collapsible">
    <summary class="planning-collapsible-summary">Read-Only Dispatch Report Inventory (developer reference)</summary>
    <div class="planning-collapsible-body">
        <p class="page-description" style="margin-bottom: 1rem;">SELECT only from raw inventory below. Batch create uses the form above.</p>
        <?php view('partials.read-inventory-card', ['readInventory' => $readInventory, 'cardTitle' => 'Dispatch Reports']); ?>
    </div>
</details>

<details class="planning-collapsible">
    <summary class="planning-collapsible-summary">Planning Foundation (reference)</summary>
    <div class="planning-collapsible-body">

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Current Dispatch Context</h2>
        </div>
        <div class="card-body">
            <dl class="info-list">
                <div class="info-row">
                    <dt>Primary Supplier</dt>
                    <dd><?= e($currentContext['supplier']) ?></dd>
                </div>
                <div class="info-row">
                    <dt>Primary Source</dt>
                    <dd><?= e($currentContext['source']) ?></dd>
                </div>
            </dl>
            <p class="page-description"><?= e($currentContext['summary']) ?></p>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Dispatch Report Purpose</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($purpose as $item): ?>
                    <li><?= e($item) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><?= e($dispatchGate['title']) ?></h2>
        </div>
        <div class="card-body">
            <p><?= e($dispatchGate['summary']) ?></p>
            <ul class="feature-list">
                <?php foreach ($dispatchGate['points'] as $point): ?>
                    <li><?= e($point) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><?= e($eligibleRule['title']) ?></h2>
        </div>
        <div class="card-body">
            <p><?= e($eligibleRule['summary']) ?></p>
            <ul class="feature-list">
                <?php foreach ($eligibleRule['points'] as $point): ?>
                    <li><?= e($point) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><?= e($singleSupplierRule['title']) ?></h2>
        </div>
        <div class="card-body">
            <p><?= e($singleSupplierRule['summary']) ?></p>
            <ul class="feature-list">
                <?php foreach ($singleSupplierRule['points'] as $point): ?>
                    <li><?= e($point) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><?= e($batchLockRule['title']) ?></h2>
        </div>
        <div class="card-body">
            <p><?= e($batchLockRule['summary']) ?></p>
            <ul class="feature-list">
                <?php foreach ($batchLockRule['points'] as $point): ?>
                    <li><?= e($point) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><?= e($batchReferenceRule['title']) ?></h2>
        </div>
        <div class="card-body">
            <p><?= e($batchReferenceRule['summary']) ?></p>
            <ul class="feature-list">
                <?php foreach ($batchReferenceRule['points'] as $point): ?>
                    <li><?= e($point) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><?= e($costSnapshotRule['title']) ?></h2>
        </div>
        <div class="card-body">
            <p><?= e($costSnapshotRule['summary']) ?></p>
            <ul class="feature-list">
                <?php foreach ($costSnapshotRule['points'] as $point): ?>
                    <li><?= e($point) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><?= e($deliveryStopRule['title']) ?></h2>
        </div>
        <div class="card-body">
            <p><?= e($deliveryStopRule['summary']) ?></p>
            <ul class="feature-list">
                <?php foreach ($deliveryStopRule['points'] as $point): ?>
                    <li><?= e($point) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Future Report Output Plan</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($reportOutputPlan as $item): ?>
                    <li><?= e($item) ?></li>
                <?php endforeach; ?>
            </ul>
            <p class="page-description">See <a href="<?= e(url('/invoice-printing')) ?>">Invoice Printing planning foundation</a> for packing slip, dispatch batch report, supplier product summary, and print log rules.</p>
        </div>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Future Performance Rules</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($performanceRules as $rule): ?>
                    <li><?= e($rule) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Launch Cutover Payable Boundary</h2>
        </div>
        <div class="card-body">
            <p>Dispatch payable begins only after the ERP launch cut-off date.</p>
            <p class="page-description">Old supplier payable before launch belongs in Supplier Opening Balance planning, not dispatch batches.</p>
        </div>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Current Access Mode</h2>
        </div>
        <div class="card-body">
            <dl class="info-list">
                <div class="info-row">
                    <dt>Mode</dt>
                    <dd><?= e($accessMode['mode']) ?></dd>
                </div>
                <div class="info-row">
                    <dt>Current Role</dt>
                    <dd><?= e($accessMode['role']) ?></dd>
                </div>
            </dl>
            <p class="page-description">Owner and admin can view the Dispatch Report planning foundation now. Staff may view/manage later based on permission. Supplier role should only see supplier-specific dispatch later, not all dispatch reports.</p>
        </div>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Planned Dispatch Report Fields</h2>
        </div>
        <div class="card-body">
            <p class="page-description">Documented only. No dispatch tables are created automatically and no records are written.</p>
            <ul class="feature-list">
                <?php foreach ($plannedReportFields as $field): ?>
                    <li><code><?= e($field) ?></code></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Planned Dispatch Report Item Fields</h2>
        </div>
        <div class="card-body">
            <p class="page-description">Documented only. No dispatch tables are created automatically and no records are written.</p>
            <ul class="feature-list">
                <?php foreach ($plannedItemFields as $field): ?>
                    <li><code><?= e($field) ?></code></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

    </div>
</details>
<?php endif; ?>
