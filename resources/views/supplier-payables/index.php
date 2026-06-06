<div class="page-header">
    <h1 class="page-title">Supplier Payables</h1>
    <p class="page-description">Supplier Account / Payable Ledger — v0.4.7.0. Dispatch locked cost snapshots become Product Cost Payable drafts. Owner approval required before entries post to running balance.</p>
</div>

<?php view('partials.flash-messages', ['flashSuccess' => $flashSuccess ?? null, 'flashError' => $flashError ?? null]); ?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon stat-icon-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        </div>
        <div class="stat-content">
            <span class="stat-label">Net Payable</span>
            <span class="stat-value"><?= e(number_format((float) ($ledgerSummary['net_payable'] ?? 0), 2)) ?> BDT</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon stat-icon-warn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
        </div>
        <div class="stat-content">
            <span class="stat-label">Pending Approval</span>
            <span class="stat-value"><?= e((string) ($ledgerSummary['draft_count'] ?? 0)) ?> draft(s)</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon stat-icon-success">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        </div>
        <div class="stat-content">
            <span class="stat-label">Posted Entries</span>
            <span class="stat-value"><?= e((string) ($ledgerSummary['posted_count'] ?? 0)) ?></span>
        </div>
    </div>
</div>

<div class="card mb-15">
    <div class="card-header">
        <h2 class="card-title">Net Payable Formula</h2>
    </div>
    <div class="card-body">
        <p><?= e($netPayableFormula['summary']) ?></p>
        <ul class="feature-list">
            <?php foreach ($netPayableFormula['points'] as $point): ?>
                <li><?= e($point) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<?php
$writeGateMessage = null;
view('partials.write-gate-warning', [
    'writeGateReady' => $writeGateReady ?? false,
    'writeGate' => $writeGate ?? [],
    'writeGateMessage' => $writeGateMessage,
]);
?>

<?php if (!empty($writeGateReady) && !empty($canManage)): ?>
<div class="card mb-15">
    <div class="card-header">
        <h2 class="card-title">Manual Ledger Entry</h2>
    </div>
    <div class="card-body">
        <form method="post" action="<?= e(url('/supplier-payables/create')) ?>" class="form-grid">
            <?= $csrfField ?>
            <div class="form-group">
                <label for="supplier_id">Supplier</label>
                <select name="supplier_id" id="supplier_id" class="form-input" required>
                    <option value="">Select supplier</option>
                    <?php foreach ($suppliers as $supplier): ?>
                        <option value="<?= e((string) ($supplier['supplier_id'] ?? '')) ?>" <?= (int) ($supplier['supplier_id'] ?? 0) === (int) $selectedSupplierId ? 'selected' : '' ?>>
                            <?= e((string) ($supplier['supplier_name'] ?? 'Supplier')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="ledger_type">Entry Type</label>
                <select name="ledger_type" id="ledger_type" class="form-input" required>
                    <?php foreach ($manualEntryTypes as $type): ?>
                        <option value="<?= e($type) ?>"><?= e($ledgerTypes[$type] ?? $type) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="amount">Amount (BDT)</label>
                <input type="number" name="amount" id="amount" class="form-input" step="0.01" min="0.01" required>
            </div>
            <div class="form-group">
                <label for="source_reference">Reference (optional)</label>
                <input type="text" name="source_reference" id="source_reference" class="form-input" placeholder="Invoice no, payment ref, etc.">
            </div>
            <div class="form-group form-group-full">
                <label for="return_receive_id">Return Receive (for Return Deduction only)</label>
                <select name="return_receive_id" id="return_receive_id" class="form-input">
                    <option value="">— Not applicable —</option>
                    <?php foreach ($supplierReturns as $ret): ?>
                        <option value="<?= e((string) ($ret['return_receive_id'] ?? '')) ?>">
                            <?= e((string) ($ret['return_reference'] ?? '')) ?> — <?= e((string) ($ret['return_type'] ?? '')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group form-group-full">
                <label for="note">Note (optional)</label>
                <textarea name="note" id="note" class="form-input" rows="2" placeholder="Owner note for audit"></textarea>
            </div>
            <div class="form-actions form-group-full">
                <button type="submit" class="btn btn-primary">Create Draft Entry</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="card mb-15">
    <div class="card-header card-header-flex">
        <h2 class="card-title">Payable Ledger</h2>
        <?php if (!empty($suppliers)): ?>
        <form method="get" action="<?= e(url('/supplier-payables')) ?>" class="inline-filter-form">
            <select name="supplier_id" class="form-input form-input-sm" onchange="this.form.submit()">
                <option value="">All suppliers</option>
                <?php foreach ($suppliers as $supplier): ?>
                    <option value="<?= e((string) ($supplier['supplier_id'] ?? '')) ?>" <?= (int) ($supplier['supplier_id'] ?? 0) === (int) $selectedSupplierId ? 'selected' : '' ?>>
                        <?= e((string) ($supplier['supplier_name'] ?? '')) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
        <?php endif; ?>
    </div>
    <div class="card-body card-body-flush">
        <?php if (empty($ledgerRows)): ?>
            <div class="empty-state">
                <p>No ledger entries yet. Product Cost Payable drafts are created automatically when a dispatch report is locked.</p>
            </div>
        <?php else: ?>
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Reference</th>
                        <th>Type</th>
                        <th>Description</th>
                        <th>Debit</th>
                        <th>Credit</th>
                        <th>Balance</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ledgerRows as $row): ?>
                    <tr>
                        <td><?= e((string) ($row['created_at'] ?? '')) ?></td>
                        <td><code><?= e((string) ($row['ledger_reference'] ?? '')) ?></code></td>
                        <td><?= e((string) ($row['type_label'] ?? '')) ?></td>
                        <td><?= e((string) ($row['description'] ?? '')) ?></td>
                        <td><?= (float) ($row['debit_amount'] ?? 0) > 0 ? e(number_format((float) $row['debit_amount'], 2)) : '—' ?></td>
                        <td><?= (float) ($row['credit_amount'] ?? 0) > 0 ? e(number_format((float) $row['credit_amount'], 2)) : '—' ?></td>
                        <td><?= ($row['balance_after'] ?? null) !== null ? e(number_format((float) $row['balance_after'], 2)) : '—' ?></td>
                        <td><span class="badge badge-<?= ($row['status'] ?? '') === 'posted' ? 'success' : (($row['status'] ?? '') === 'draft' ? 'warn' : 'muted') ?>"><?= e((string) ($row['status'] ?? '')) ?></span></td>
                        <td>
                            <?php if (!empty($canManage) && ($row['status'] ?? '') === 'draft'): ?>
                            <form method="post" action="<?= e(url('/supplier-payables/approve')) ?>" class="inline-form">
                                <?= $csrfField ?>
                                <input type="hidden" name="payable_ledger_id" value="<?= e((string) ($row['payable_ledger_id'] ?? '')) ?>">
                                <button type="submit" class="btn btn-sm btn-primary">Post</button>
                            </form>
                            <form method="post" action="<?= e(url('/supplier-payables/reject')) ?>" class="inline-form">
                                <?= $csrfField ?>
                                <input type="hidden" name="payable_ledger_id" value="<?= e((string) ($row['payable_ledger_id'] ?? '')) ?>">
                                <button type="submit" class="btn btn-sm btn-secondary">Reject</button>
                            </form>
                            <?php else: ?>
                            <span class="text-muted">—</span>
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

<details class="dev-collapse mb-15">
    <summary>Developer / Read Inventory</summary>
    <div class="dev-collapse-body">
        <?php view('partials.read-inventory-card', ['readInventory' => $readInventory, 'cardTitle' => 'Payable Ledgers (Dev)']); ?>
    </div>
</details>

<details class="dev-collapse">
    <summary>Planning Foundation (collapsed)</summary>
    <div class="dev-collapse-body">
        <p class="page-description">Supplier payable is based on product cost from dispatch snapshots only — never selling price or live changing cost. Return deductions require receive confirmation plus owner approval. Supplier Tools remain independent unless owner posts a ledger entry.</p>
    </div>
</details>
