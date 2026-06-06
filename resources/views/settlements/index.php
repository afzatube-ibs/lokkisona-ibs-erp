<div class="page-header">
    <h1 class="page-title">Settlements</h1>
    <p class="page-description">Supplier settlement workflow — v<?= e($appVersion) ?> — <?= e($appReleaseLabel ?? '') ?>. Draft → Prepared → Owner Approved → Paid → Closed. Apply migration 0009 manually before write forms appear.</p>
</div>

<?php view('partials.flash-messages', ['flashSuccess' => $flashSuccess ?? null, 'flashError' => $flashError ?? null]); ?>

<?php
$settlementRows = $settlements ?? [];
$openCount = 0;
$awaitingPayment = 0;
$closedCount = 0;
$openValue = 0.0;
foreach ($settlementRows as $sRow) {
    $st = (string) ($sRow['workflow_status'] ?? '');
    if (in_array($st, ['draft', 'prepared', 'approved'], true)) {
        $openCount++;
        $openValue += (float) ($sRow['closing_balance'] ?? 0);
    }
    if ($st === 'approved') {
        $awaitingPayment++;
    }
    if ($st === 'closed') {
        $closedCount++;
    }
}
?>
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon stat-icon-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
        </div>
        <div class="stat-content">
            <span class="stat-label">Open Periods</span>
            <span class="stat-value"><?= e((string) $openCount) ?></span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon stat-icon-warn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
        <div class="stat-content">
            <span class="stat-label">Awaiting Payment</span>
            <span class="stat-value"><?= e((string) $awaitingPayment) ?></span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon stat-icon-info">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        </div>
        <div class="stat-content">
            <span class="stat-label">Open Closing Value</span>
            <span class="stat-value"><?= e(number_format($openValue, 2)) ?> BDT</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon stat-icon-success">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        </div>
        <div class="stat-content">
            <span class="stat-label">Closed Periods</span>
            <span class="stat-value"><?= e((string) $closedCount) ?></span>
        </div>
    </div>
</div>

<?php
view('partials.write-gate-warning', [
    'writeGateReady' => $writeGateReady ?? false,
    'writeGate' => $writeGate ?? [],
    'writeGateMessage' => null,
]);
?>

<?php if (!empty($canManage) && !empty($writeGateReady)): ?>
<div class="card mb-15">
    <div class="card-header"><h2 class="card-title">Prepare Settlement</h2></div>
    <div class="card-body">
        <form method="post" action="<?= e(url('/settlements/prepare')) ?>" class="form-grid">
            <?= $csrfField ?? '' ?>
            <label>Supplier
                <select name="supplier_id" required>
                    <option value="">Select supplier</option>
                    <?php foreach (($suppliers ?? []) as $supplier): ?>
                    <option value="<?= e((string) ($supplier['supplier_id'] ?? '')) ?>" <?= (int) ($selectedSupplierId ?? 0) === (int) ($supplier['supplier_id'] ?? 0) ? 'selected' : '' ?>>
                        <?= e((string) ($supplier['supplier_name'] ?? $supplier['name'] ?? 'Supplier')) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Period Type
                <select name="period_type" required>
                    <?php foreach (($periodTypes ?? []) as $key => $label): ?>
                    <option value="<?= e($key) ?>"><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Custom Start<input type="date" name="period_start"></label>
            <label>Custom End<input type="date" name="period_end"></label>
            <label>Notes<textarea name="notes" rows="2" placeholder="Optional settlement note"></textarea></label>
            <button type="submit" class="btn btn-primary">Prepare Settlement</button>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="card mb-15">
    <div class="card-header"><h2 class="card-title">Settlement Periods</h2></div>
    <div class="card-body card-body-flush">
        <?php if (empty($settlements)): ?>
            <div class="empty-state">
                <p>No settlement periods yet. Prepare a period above once payable ledger entries are posted on <a href="<?= e(url('/supplier-payables')) ?>">Supplier Payables</a>.</p>
            </div>
        <?php else: ?>
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Reference</th><th>Supplier</th><th>Period</th><th>Closing</th><th>Status</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($settlements as $row): ?>
                    <tr>
                        <td><?= e((string) ($row['settlement_reference'] ?? '')) ?></td>
                        <td><?= e((string) ($row['supplier_id'] ?? '')) ?></td>
                        <td><?= e((string) ($row['period_start'] ?? '')) ?> — <?= e((string) ($row['period_end'] ?? '')) ?></td>
                        <td><?= e(number_format((float) ($row['closing_balance'] ?? 0), 2)) ?></td>
                        <td><?= e($workflowLabels[$row['workflow_status'] ?? ''] ?? (string) ($row['workflow_status'] ?? '')) ?></td>
                        <td>
                            <?php if (!empty($canManage) && !empty($writeGateReady)): ?>
                                <?php if (($row['workflow_status'] ?? '') === 'prepared'): ?>
                                <form method="post" action="<?= e(url('/settlements/approve')) ?>" style="display:inline;">
                                    <?= $csrfField ?? '' ?>
                                    <input type="hidden" name="settlement_id" value="<?= e((string) ($row['settlement_id'] ?? '')) ?>">
                                    <button type="submit" class="btn btn-sm">Approve</button>
                                </form>
                                <?php elseif (($row['workflow_status'] ?? '') === 'approved'): ?>
                                <form method="post" action="<?= e(url('/settlements/mark-paid')) ?>" style="display:inline;">
                                    <?= $csrfField ?? '' ?>
                                    <input type="hidden" name="settlement_id" value="<?= e((string) ($row['settlement_id'] ?? '')) ?>">
                                    <button type="submit" class="btn btn-sm">Mark Paid</button>
                                </form>
                                <?php elseif (($row['workflow_status'] ?? '') === 'paid'): ?>
                                <form method="post" action="<?= e(url('/settlements/close')) ?>" style="display:inline;">
                                    <?= $csrfField ?? '' ?>
                                    <input type="hidden" name="settlement_id" value="<?= e((string) ($row['settlement_id'] ?? '')) ?>">
                                    <button type="submit" class="btn btn-sm">Close</button>
                                </form>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
