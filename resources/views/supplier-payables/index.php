<div class="page-header page-header-compact">
    <h1 class="page-title">Supplier Payables</h1>
    <p class="ops-page-subtitle"><?= !empty($isSupplierView) ? 'Your accounts with Lokkisona — sales, payments, and adjustments. Owner posts dispatch sales to the ledger.' : 'Dispatch locked cost snapshots become payable drafts — owner approval required before posting.' ?></p>
</div>

<?php view('partials.flash-messages', ['flashSuccess' => $flashSuccess ?? null, 'flashError' => $flashError ?? null]); ?>

<?php view('partials.ops-safety-strip', ['message' => !empty($isSupplierView) ? 'All entries are draft until owner posts them · Sales use locked dispatch amounts only · No live sync' : 'All entries are draft until owner posts them · Payable uses locked dispatch snapshots only · No live sync']); ?>

<div class="kpi-grid">
    <div class="kpi-card kpi-accent-primary">
        <span class="kpi-label">Net Payable</span>
        <span class="kpi-value"><?= e(number_format((float) ($ledgerSummary['net_payable'] ?? 0), 2)) ?></span>
        <span class="kpi-hint">BDT · running balance</span>
    </div>
    <div class="kpi-card kpi-accent-warn">
        <span class="kpi-label">Pending Approval</span>
        <span class="kpi-value"><?= e((string) ($ledgerSummary['draft_count'] ?? 0)) ?></span>
        <span class="kpi-hint">Draft entries awaiting post</span>
    </div>
    <div class="kpi-card kpi-accent-success">
        <span class="kpi-label">Posted Entries</span>
        <span class="kpi-value"><?= e((string) ($ledgerSummary['posted_count'] ?? 0)) ?></span>
        <span class="kpi-hint">Approved ledger rows</span>
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
            <?php if (!empty($canSelectSupplier)): ?>
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
            <?php else: ?>
            <input type="hidden" name="supplier_id" value="<?= e((string) ($selectedSupplierId ?? '')) ?>">
            <?php endif; ?>
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

<?php if (!empty($writeGateReady) && !empty($canManage) && !empty($eligibleReturnBatches)): ?>
<div class="card mb-15">
    <div class="card-header">
        <h2 class="card-title">Return Batch Deductions</h2>
    </div>
    <div class="card-body card-body-flush">
        <p class="page-description" style="padding: 1rem 1.25rem 0;">Owner-approved return batches eligible for a Return / Damage Deduction draft. Creating the draft does not post it — it still requires approval in the ledger below.</p>
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Batch Ref</th>
                        <th>Returns</th>
                        <th>Deduction Amount</th>
                        <th>Created</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($eligibleReturnBatches as $batch): ?>
                    <tr>
                        <td><code><?= e((string) ($batch['return_batch_reference'] ?? '')) ?></code></td>
                        <td><?= e((string) ($batch['total_returns'] ?? 0)) ?></td>
                        <td><?= e(number_format((float) ($batch['total_adjustment_amount'] ?? 0), 2)) ?> BDT</td>
                        <td><?= e((string) ($batch['created_at'] ?? '')) ?></td>
                        <td>
                            <form method="post" action="<?= e(url('/supplier-payables/post-return-batch')) ?>" class="inline-form">
                                <?= $csrfField ?>
                                <input type="hidden" name="return_batch_id" value="<?= e((string) ($batch['return_batch_id'] ?? '')) ?>">
                                <button type="submit" class="btn btn-sm btn-primary">Create Deduction Draft</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="card mb-15">
    <div class="card-header card-header-flex">
        <h2 class="card-title">Payable Ledger</h2>
        <?php if (!empty($canSelectSupplier) && !empty($suppliers)): ?>
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
                <p><?= !empty($isSupplierView) ? 'No ledger entries yet. Sale drafts are created when owner locks a dispatch batch.' : 'No ledger entries yet. Product Cost Payable drafts are created automatically when a dispatch report is locked.' ?></p>
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
                            <?php if (!empty($canApproveLedger) && ($row['status'] ?? '') === 'draft'): ?>
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

<?php if (!empty($isSupplierView)): ?>
<div class="card mb-15">
    <div class="card-header"><h2 class="card-title">How Your Balance Works</h2></div>
    <div class="card-body">
        <p><?= e($netPayableFormula['summary']) ?></p>
        <ul class="feature-list">
            <?php foreach ($netPayableFormula['points'] as $point): ?>
                <li><?= e($point) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
<?php endif; ?>

<?php if (empty($isSupplierView)): ?>
<details class="planning-collapsible">
    <summary class="planning-collapsible-summary">Read-Only Payable Inventory (developer reference)</summary>
    <div class="planning-collapsible-body">
        <?php view('partials.read-inventory-card', ['readInventory' => $readInventory, 'cardTitle' => 'Payable Ledgers (Dev)']); ?>
    </div>
</details>

<details class="planning-collapsible">
    <summary class="planning-collapsible-summary">Net Payable Formula &amp; Planning Foundation (reference)</summary>
    <div class="planning-collapsible-body">
        <p><?= e($netPayableFormula['summary']) ?></p>
        <ul class="feature-list">
            <?php foreach ($netPayableFormula['points'] as $point): ?>
                <li><?= e($point) ?></li>
            <?php endforeach; ?>
        </ul>
        <p class="page-description">Supplier payable is based on product cost from dispatch snapshots only — never selling price or live changing cost. Return deductions require receive confirmation plus owner approval. Supplier Tools remain independent unless owner posts a ledger entry.</p>
    </div>
</details>
<?php endif; ?>
