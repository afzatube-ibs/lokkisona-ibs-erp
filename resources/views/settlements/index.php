<div class="page-header">
    <h1 class="page-title">Settlements</h1>
    <p class="page-description">Supplier settlement workflow (v0.5.9). Draft → Prepared → Owner Approved → Paid → Closed. Apply migration 0009 manually before write forms appear.</p>
</div>

<?php view('partials.flash-messages', ['flashSuccess' => $flashSuccess ?? null, 'flashError' => $flashError ?? null]); ?>

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
        <form method="post" action="/settlements/prepare" class="form-grid">
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
            <p class="page-description" style="padding:1rem;">No settlements yet. Prepare a period after payable ledger entries are posted.</p>
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
                                <form method="post" action="/settlements/approve" style="display:inline;">
                                    <?= $csrfField ?? '' ?>
                                    <input type="hidden" name="settlement_id" value="<?= e((string) ($row['settlement_id'] ?? '')) ?>">
                                    <button type="submit" class="btn btn-sm">Approve</button>
                                </form>
                                <?php elseif (($row['workflow_status'] ?? '') === 'approved'): ?>
                                <form method="post" action="/settlements/mark-paid" style="display:inline;">
                                    <?= $csrfField ?? '' ?>
                                    <input type="hidden" name="settlement_id" value="<?= e((string) ($row['settlement_id'] ?? '')) ?>">
                                    <button type="submit" class="btn btn-sm">Mark Paid</button>
                                </form>
                                <?php elseif (($row['workflow_status'] ?? '') === 'paid'): ?>
                                <form method="post" action="/settlements/close" style="display:inline;">
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
