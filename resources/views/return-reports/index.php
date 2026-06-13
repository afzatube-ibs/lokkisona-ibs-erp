<?php use App\Domain\SupplierTerminology; ?>
<div class="page-header page-header-compact">
    <h1 class="page-title">Return Reports</h1>
    <p class="ops-page-subtitle">Return list for SFM <strong>Returned</strong> items — return value = supplier product cost only (locked snapshot). No sale/profit calculation. Pending confirmation stays on <a href="<?= e(url('/return-receive')) ?>">Return Receive</a>.</p>
</div>

<?php view('partials.flash-messages', ['flashSuccess' => $flashSuccess ?? null, 'flashError' => $flashError ?? null]); ?>

<?php view('partials.ops-safety-strip', ['message' => 'Returns independent from Daily Dispatch · No stock movement · No ledger posting · Hub + Customer returns only']); ?>

<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header"><h2 class="card-title">Return Reports</h2></div>
    <div class="card-body">
        <?php if (!empty($latestReports)): ?>
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Statement Ref</th>
                        <th>Supplier</th>
                        <th>Business Source</th>
                        <th>Return Date</th>
                        <th>Created By</th>
                        <th>Returns</th>
                        <th>Total Qty</th>
                        <th><?= e(SupplierTerminology::returnAmount()) ?></th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($latestReports as $report): ?>
                    <?php
                    $ref = (string) ($report['return_report_reference'] ?? '');
                    $viewUrl = $ref !== '' ? url('/return-report/' . rawurlencode($ref)) : '#';
                    $printUrl = $ref !== '' ? url('/return-report/' . rawurlencode($ref) . '/print') : '#';
                    ?>
                    <tr>
                        <td><strong><?= e($ref) ?></strong></td>
                        <td><?= e((string) ($report['supplier_name'] ?? '—')) ?></td>
                        <td><?= e((string) ($report['business_source_name'] ?? '—')) ?></td>
                        <td><?= e((string) ($report['return_date'] ?? $report['created_at'] ?? '—')) ?></td>
                        <td><?= e((string) ($report['created_by_label'] ?? '—')) ?></td>
                        <td><?= e((string) ($report['total_returns'] ?? '0')) ?></td>
                        <td><?= e((string) ($report['total_quantity'] ?? '0')) ?></td>
                        <td><?= e(number_format((float) ($report['total_adjustment_amount'] ?? 0), 2)) ?></td>
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
        <div class="empty-state"><p>No Return Reports yet. Confirm returns on Return Receive, then create a statement below.</p></div>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($canManageReturns) && !empty($writeGateReady)): ?>
<div class="card">
    <div class="card-header"><h2 class="card-title">Confirmed Returns Ready for Report</h2></div>
    <div class="card-body">
        <?php
        $summary = $eligibleSummary ?? [];
        $filters = $eligibleFilters ?? [];
        $awaiting = (int) ($summary['awaiting'] ?? 0);
        $missingCost = (int) ($summary['missing_cost'] ?? 0);
        $missingReason = (int) ($summary['missing_reason'] ?? 0);
        $missingOrderNo = (int) ($summary['missing_order_no'] ?? 0);
        ?>
        <div class="workflow-info-banner" style="margin-bottom: 1rem;">
            <?= e((string) $awaiting) ?> confirmed return(s) ready for report
            <?php if ($missingCost > 0): ?>
            · <span class="badge badge-warn"><?= e((string) $missingCost) ?> missing cost</span>
            <?php endif; ?>
            <?php if ($missingReason > 0): ?>
            · <span class="badge badge-warn"><?= e((string) $missingReason) ?> missing reason</span>
            <?php endif; ?>
            <?php if ($missingOrderNo > 0): ?>
            · <span class="badge badge-warn"><?= e((string) $missingOrderNo) ?> missing order no</span>
            <?php endif; ?>
            · One report = one supplier + one business source
        </div>

        <?php if (!empty($businessSources)): ?>
        <form method="get" action="<?= e(url('/return-reports')) ?>" class="form-grid" style="margin-bottom: 1rem; max-width: 36rem;">
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
                <a href="<?= e(url('/return-reports')) ?>" class="btn btn-ghost btn-sm">Clear</a>
            </div>
        </form>
        <?php endif; ?>

        <p class="page-description" style="margin-bottom: 1rem;">Select supplier-confirmed Hub / Customer returns not yet in a Return Report. Reference format: RR-DDMMYYYY, RR-DDMMYYYY-P1, etc.</p>
        <?php if (!empty($eligibleReturns)): ?>
        <form method="post" action="<?= e(url('/return-reports/create')) ?>" class="js-return-report-form" data-confirm-label="Create and lock this Supplier Return Statement">
            <?= $csrfField ?? '' ?>
            <input type="hidden" name="batch_confirmed" value="0" class="js-batch-confirmed">
            <div class="table-scroll">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th><input type="checkbox" class="js-return-select-all" aria-label="Select all"></th>
                            <th>Return Ref</th>
                            <th>Type</th>
                            <th>Order No</th>
                            <th>Supplier</th>
                            <th>Source</th>
                            <th>Reason</th>
                            <th>Qty</th>
                            <th>Cost Snapshot</th>
                            <th>Received</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($eligibleReturns as $row): ?>
                        <?php $disabled = !empty($row['missing_cost']) || !empty($row['missing_reason']) || !empty($row['missing_order_no']); ?>
                        <tr class="js-return-report-row"
                            data-qty="<?= e((string) ($row['preview_item_count'] ?? '0')) ?>"
                            data-cost="<?= e((string) ($row['preview_cost_snapshot'] ?? '0.00')) ?>"
                            data-missing-cost="<?= !empty($row['missing_cost']) ? '1' : '0' ?>"
                            data-missing-reason="<?= !empty($row['missing_reason']) ? '1' : '0' ?>"
                            data-missing-order-no="<?= !empty($row['missing_order_no']) ? '1' : '0' ?>"
                            data-business-source="<?= e((string) ($row['business_source_id'] ?? '0')) ?>"
                            data-supplier="<?= e((string) ($row['supplier_id'] ?? '0')) ?>">
                            <td><input type="checkbox" name="return_receive_ids[]" value="<?= e((string) ($row['return_receive_id'] ?? '')) ?>" class="js-return-report-select" <?= $disabled ? 'disabled' : '' ?>></td>
                            <td><code><?= e((string) ($row['return_reference'] ?? '')) ?></code></td>
                            <td><?= e((string) ($row['return_type_label'] ?? '')) ?></td>
                            <td>
                                <?= e((string) ($row['display_order_no'] ?? '—')) ?>
                                <?php if (!empty($row['missing_order_no'])): ?>
                                <span class="badge badge-warn">Missing Order No</span>
                                <?php endif; ?>
                            </td>
                            <td><?= e((string) ($row['supplier_name'] ?? '—')) ?></td>
                            <td><?= e((string) ($row['business_source_name'] ?? '—')) ?></td>
                            <td>
                                <?php if (!empty($row['missing_reason'])): ?>
                                <span class="badge badge-warn">Missing Reason</span>
                                <?php else: ?>
                                <?= e((string) ($row['return_reason_label'] ?? '—')) ?>
                                <?php endif; ?>
                            </td>
                            <td><?= e((string) ($row['preview_item_count'] ?? '0')) ?></td>
                            <td>
                                <?php if (!empty($row['missing_cost'])): ?>
                                <span class="badge badge-warn">Missing Cost</span>
                                <?php endif; ?>
                                <?= e(number_format((float) ($row['preview_cost_snapshot'] ?? 0), 2)) ?>
                            </td>
                            <td><?= e((string) ($row['received_at'] ?? '')) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="js-return-report-summary workflow-info-banner" style="margin-top: 1rem;">Report summary: 0 returns · 0 qty · 0.00 return amount</div>
            <div class="js-return-mixed-supplier-warning workflow-info-banner" style="margin-top: 0.75rem; display: none; color: var(--color-warning, #b45309);"></div>
            <div class="js-return-mixed-source-warning workflow-info-banner" style="margin-top: 0.75rem; display: none; color: var(--color-warning, #b45309);"></div>
            <div class="js-return-missing-cost-warning workflow-info-banner" style="margin-top: 0.75rem; display: none; color: var(--color-warning, #b45309);"></div>
            <div class="js-return-missing-reason-warning workflow-info-banner" style="margin-top: 0.75rem; display: none; color: var(--color-warning, #b45309);"></div>
            <div class="js-return-missing-order-no-warning workflow-info-banner" style="margin-top: 0.75rem; display: none; color: var(--color-warning, #b45309);"></div>
            <label class="workflow-confirm-checkbox" style="margin-top: 1rem;">
                <input type="checkbox" name="batch_confirm_checkbox" value="1" required>
                <span>I confirm this Supplier Return Statement locks return amounts now. Included returns cannot be added to another report.</span>
            </label>
            <button type="submit" class="btn btn-primary js-return-report-submit-btn" style="margin-top: 1rem;">Create Return Report</button>
        </form>
        <?php else: ?>
        <p class="page-description"><span class="badge badge-warn">No eligible returns</span> Confirm returns on <a href="<?= e(url('/return-receive')) ?>">Return Receive</a> first. Lokkisona warehouse returns are excluded.</p>
        <?php endif; ?>
    </div>
</div>
<?php elseif (!empty($canManageReturns)): ?>
<?php view('partials.write-gate-warning', ['writeGateReady' => $writeGateReady ?? false, 'writeGate' => $writeGate ?? []]); ?>
<?php endif; ?>
