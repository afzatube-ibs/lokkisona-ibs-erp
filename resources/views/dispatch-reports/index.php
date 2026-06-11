<?php use App\Domain\SupplierTerminology; ?>
<div class="page-header page-header-compact">
    <h1 class="page-title">Daily Dispatch</h1>
    <p class="ops-page-subtitle"><?= !empty($isSupplierView) ? 'Locked sale snapshots prepared by Lokkisona for your account — Daily Dispatch Statements only.' : 'One-step lock from shipped orders — one supplier, one business source, one dispatch date per batch. Payable checkpoint draft only (no ledger posting here).' ?></p>
</div>

<?php view('partials.flash-messages', ['flashSuccess' => $flashSuccess ?? null, 'flashError' => $flashError ?? null]); ?>

<?php view('partials.ops-safety-strip', ['message' => !empty($isSupplierView) ? 'Snapshots lock when the batch is created — amounts do not change if catalog prices change later.' : 'No stock deducted · No OpenCart writes · One-step lock · Payable checkpoint draft on Supplier Payables only']); ?>

<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header"><h2 class="card-title">Daily Dispatch Statements</h2></div>
    <div class="card-body">
        <?php if (!empty($latestReports)): ?>
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Statement Ref</th>
                        <th>Supplier</th>
                        <th>Business Source</th>
                        <th>Dispatch Date</th>
                        <th>Created By</th>
                        <th>Orders</th>
                        <th>Total Qty</th>
                        <th><?= !empty($isSupplierView) ? e(SupplierTerminology::totalSaleSnapshot()) : e(SupplierTerminology::totalCostSnapshot()) ?></th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($latestReports as $report): ?>
                    <?php
                    $ref = (string) ($report['dispatch_reference'] ?? '');
                    $viewUrl = $ref !== '' ? url('/dispatch-report/' . rawurlencode($ref)) : '#';
                    $printUrl = $ref !== '' ? url('/dispatch-report/' . rawurlencode($ref) . '/print') : '#';
                    ?>
                    <tr>
                        <td><strong><?= e($ref) ?></strong></td>
                        <td><?= e((string) ($report['supplier_name'] ?? '—')) ?></td>
                        <td><?= e((string) ($report['business_source_name'] ?? '—')) ?></td>
                        <td><?= e((string) ($report['dispatch_date'] ?? $report['created_at'] ?? '—')) ?></td>
                        <td><?= e((string) ($report['created_by_label'] ?? '—')) ?></td>
                        <td><?= e((string) ($report['total_orders'] ?? '0')) ?></td>
                        <td><?= e((string) ($report['total_qty'] ?? '0')) ?></td>
                        <td><?= e((string) ($report['total_product_cost'] ?? '0.00')) ?></td>
                        <td><span class="badge badge-ok"><?= e((string) ($report['status_label'] ?? 'Created / Locked')) ?></span></td>
                        <td>
                            <a href="<?= e($viewUrl) ?>" class="btn btn-sm btn-secondary">View</a>
                            <a href="<?= e($printUrl) ?>" class="btn btn-sm btn-ghost" target="_blank" rel="noopener">Print Statement</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state"><p>No Daily Dispatch Statements yet. Create one from Order List (Shipped) or the form below.</p></div>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($canManageDispatch) && !empty($writeGateReady)): ?>
<div class="card">
    <div class="card-header"><h2 class="card-title">Create Daily Dispatch Statement</h2></div>
    <div class="card-body">
        <?php
        $summary = $eligibleSummary ?? [];
        $filters = $eligibleFilters ?? [];
        $awaiting = (int) ($summary['awaiting'] ?? 0);
        $missingCost = (int) ($summary['missing_cost'] ?? 0);
        $missingOrderNo = (int) ($summary['missing_order_no'] ?? 0);
        ?>
        <div class="workflow-info-banner" style="margin-bottom: 1rem;">
            <?= e((string) $awaiting) ?> shipped order(s) awaiting dispatch
            <?php if ($missingCost > 0): ?>
            · <span class="badge badge-warn"><?= e((string) $missingCost) ?> missing cost</span>
            <?php endif; ?>
            <?php if ($missingOrderNo > 0): ?>
            · <span class="badge badge-warn"><?= e((string) $missingOrderNo) ?> missing order no</span>
            <?php endif; ?>
            · One batch = one supplier + one business source + today&apos;s dispatch date
        </div>

        <?php if (!empty($businessSources)): ?>
        <form method="get" action="<?= e(url('/dispatch-reports')) ?>" class="form-grid" style="margin-bottom: 1rem; max-width: 36rem;">
            <div class="form-group">
                <label for="business_source_id">Business Source</label>
                <select name="business_source_id" id="business_source_id" class="form-input">
                    <option value="0">All sources (filter list)</option>
                    <?php foreach (($businessSources ?? []) as $sourceId => $sourceName): ?>
                    <option value="<?= e((string) $sourceId) ?>" <?= (int) ($filters['business_source_id'] ?? 0) === (int) $sourceId ? 'selected' : '' ?>><?= e((string) $sourceName) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group form-actions">
                <button type="submit" class="btn btn-secondary btn-sm">Apply Filter</button>
                <a href="<?= e(url('/dispatch-reports')) ?>" class="btn btn-ghost btn-sm">Clear</a>
            </div>
        </form>
        <?php endif; ?>

        <p class="page-description" style="margin-bottom: 1rem;">Select shipped orders not yet in a statement. Reference format: DDMMYYYY-P1, DDMMYYYY-P2, etc. Mixed business sources are blocked at submit.</p>
        <?php if (!empty($eligibleOrders)): ?>
        <form method="post" action="<?= e(url('/dispatch-reports/create')) ?>" class="js-dispatch-batch-form" data-confirm-label="Create and lock this Daily Dispatch Statement">
            <?= $csrfField ?? '' ?>
            <input type="hidden" name="batch_confirmed" value="0" class="js-batch-confirmed">
            <div class="table-scroll">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Select</th>
                            <th>Order</th>
                            <th>Customer</th>
                            <th>Source</th>
                            <th>Qty</th>
                            <th><?= e(SupplierTerminology::totalCostSnapshot()) ?></th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($eligibleOrders as $order): ?>
                        <tr class="js-dispatch-order-row"
                            data-qty="<?= e((string) ($order['preview_item_count'] ?? '0')) ?>"
                            data-cost="<?= e((string) ($order['preview_cost_snapshot'] ?? '0.00')) ?>"
                            data-missing-cost="<?= !empty($order['missing_cost']) ? '1' : '0' ?>"
                            data-missing-order-no="<?= !empty($order['missing_order_no']) ? '1' : '0' ?>"
                            data-courier="<?= e((string) ($order['courier_status'] ?? '')) ?>"
                            data-business-source="<?= e((string) ($order['business_source_id'] ?? '0')) ?>">
                            <td><input type="checkbox" name="order_ids[]" value="<?= e((string) ($order['order_id'] ?? '')) ?>" class="js-dispatch-order-select" <?= !empty($order['missing_cost']) || !empty($order['missing_order_no']) ? 'disabled' : '' ?>></td>
                            <td>
                                <?= e((string) ($order['display_order_no'] ?? $order['order_reference'] ?? $order['order_id'] ?? '')) ?>
                                <?php if (!empty($order['missing_order_no'])): ?>
                                <span class="badge badge-warn">Missing Order No</span>
                                <?php endif; ?>
                            </td>
                            <td><?= e((string) ($order['customer_name'] ?? '')) ?></td>
                            <td><?= e((string) ($order['business_source_name'] ?? '—')) ?></td>
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
            <div class="js-dispatch-batch-summary workflow-info-banner" style="margin-top: 1rem;">Batch summary: 0 orders · 0 qty · 0.00 cost snapshot</div>
            <div class="js-dispatch-mixed-source-warning workflow-info-banner" style="margin-top: 0.75rem; display: none; color: var(--color-warning, #b45309);"></div>
            <div class="js-dispatch-missing-cost-warning workflow-info-banner" style="margin-top: 0.75rem; display: none; color: var(--color-warning, #b45309);"></div>
            <div class="js-dispatch-missing-order-no-warning workflow-info-banner" style="margin-top: 0.75rem; display: none; color: var(--color-warning, #b45309);"></div>
            <label class="workflow-confirm-checkbox" style="margin-top: 1rem;">
                <input type="checkbox" name="batch_confirm_checkbox" value="1" required>
                <span>I confirm this Daily Dispatch Statement locks supplier cost now. Included orders move to Created Report and cannot be re-batched.</span>
            </label>
            <button type="submit" class="btn btn-primary js-dispatch-submit-btn" style="margin-top: 1rem;">Create &amp; Lock Statement</button>
        </form>
        <?php else: ?>
        <p class="page-description"><span class="badge badge-warn">No eligible orders</span> No shipped orders are waiting for a Daily Dispatch Statement, or all shipped orders are already included.</p>
        <?php endif; ?>
    </div>
</div>
<?php elseif (!empty($canManageDispatch)): ?>
<?php view('partials.write-gate-warning', ['writeGateReady' => $writeGateReady ?? false, 'writeGate' => $writeGate ?? []]); ?>
<?php endif; ?>
