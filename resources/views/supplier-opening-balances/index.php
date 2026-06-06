<div class="page-header">
    <h1 class="page-title">Supplier Opening Balance &amp; Launch Cutover</h1>
    <p class="page-description">Opening balance create/approve and launch cutover lock — v<?= e($appVersion) ?> — <?= e($appReleaseLabel ?? '') ?>. Available when migration 0008 is applied.</p>
</div>

<?php view('partials.flash-messages', ['flashSuccess' => $flashSuccess ?? null, 'flashError' => $flashError ?? null]); ?>
<?php if (!empty($writeGateReady)): ?>
<div class="card" style="margin-bottom:1.5rem;"><div class="card-body">
<form method="post" action="<?= e(url('/supplier-opening-balances/create')) ?>"><?= $csrfField ?? '' ?>
<label>Supplier ID *<input type="number" name="supplier_id" required min="1"></label>
<label>Amount<input name="amount" type="number" step="0.01"></label>
<label>Balance type<select name="balance_type"><option value="payable_to_supplier">payable_to_supplier</option><option value="advance_from_supplier">advance_from_supplier</option><option value="neutral_zero_start">neutral_zero_start</option></select></label>
<label>Cut-off date<input type="date" name="cutoff_date"></label>
<button type="submit">Create opening balance draft (v0.3.5)</button></form>
<hr><form method="post" action="<?= e(url('/supplier-opening-balances/approve')) ?>"><?= $csrfField ?? '' ?>
<label>Balance ID to approve *<input type="number" name="supplier_opening_balance_id" required min="1"></label>
<button type="submit">Approve opening balance (owner)</button></form>
<?php if (empty($launchLocked)): ?>
<hr><form method="post" action="<?= e(url('/supplier-opening-balances/launch-lock')) ?>"><?= $csrfField ?? '' ?>
<label>Go-live date *<input type="date" name="go_live_date" required></label>
<label>Cut-off date *<input type="date" name="cutoff_date" required></label>
<button type="submit">Lock launch cutover (v0.3.6)</button></form>
<?php else: ?><p class="page-description">Launch is locked.</p><?php endif; ?>
</div></div>
<?php else: ?>
<?php view('partials.write-gate-warning', ['writeGateReady' => $writeGateReady ?? false, 'writeGate' => $writeGate ?? []]); ?>
<?php endif; ?>

<h2 class="section-heading" style="margin: 0 0 0.75rem;">Read-Only Opening Balance / Launch Cutover Inventory</h2>
<p class="page-description" style="margin-bottom: 1rem;">SELECT only. No database writes. No payable ledger creation. No launch lock action. No migration apply from this page.</p>

<?php view('partials.read-inventory-card', ['readInventory' => $openingBalanceReadInventory, 'cardTitle' => 'Supplier Opening Balances']); ?>
<?php view('partials.read-inventory-card', ['readInventory' => $launchCutoverReadInventory, 'cardTitle' => 'Launch Cutovers']); ?>

<h2 class="section-heading" style="margin: 1.5rem 0 1rem;">Planning Foundation</h2>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Current Safety Status</h2>
        </div>
        <div class="card-body">
            <dl class="info-list">
                <div class="info-row">
                    <dt>Access Mode</dt>
                    <dd><?= e($accessMode['mode']) ?></dd>
                </div>
                <div class="info-row">
                    <dt>Current Role</dt>
                    <dd><span class="badge badge-ok"><?= e($accessMode['role']) ?></span></dd>
                </div>
                <div class="info-row">
                    <dt>Record Mode</dt>
                    <dd><span class="badge badge-warn">Planning only</span></dd>
                </div>
                <div class="info-row">
                    <dt>Payable Impact</dt>
                    <dd>No payable ledger records, stock changes, sync/import, or invoice actions.</dd>
                </div>
            </dl>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Example Opening Balance</h2>
        </div>
        <div class="card-body">
            <dl class="info-list">
                <?php foreach ($exampleBalance as $label => $value): ?>
                <div class="info-row">
                    <dt><?= e(ucwords(str_replace('_', ' ', $label))) ?></dt>
                    <dd><?= e($value) ?></dd>
                </div>
                <?php endforeach; ?>
            </dl>
            <p class="page-description">The estimated old/manual payable is shown as a launch opening balance example, not as a normal order payable.</p>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Opening Balance Rules</h2>
    </div>
    <div class="card-body">
        <div class="permission-grid">
            <?php foreach ($rules as $rule): ?>
            <div class="permission-group">
                <h3><?= e($rule['title']) ?></h3>
                <ul>
                    <?php foreach ($rule['points'] as $point): ?>
                    <li><?= e($point) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Opening Balance Types</h2>
        </div>
        <div class="card-body">
            <div class="planned-table-grid">
                <?php foreach ($balanceTypes as $type): ?>
                    <code><?= e($type) ?></code>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Launch Cutover Checklist</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($launchChecklist as $item): ?>
                <li><?= e($item) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Planned Opening Balance Fields</h2>
        </div>
        <div class="card-body">
            <div class="planned-table-grid">
                <?php foreach ($openingBalanceFields as $field): ?>
                    <code><?= e($field) ?></code>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Planned Adjustment Fields</h2>
        </div>
        <div class="card-body">
            <div class="planned-table-grid">
                <?php foreach ($adjustmentFields as $field): ?>
                    <code><?= e($field) ?></code>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Planned Opening Balance Audit Fields</h2>
        </div>
        <div class="card-body">
            <div class="planned-table-grid">
                <?php foreach ($auditFields as $field): ?>
                    <code><?= e($field) ?></code>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Planned Launch Cutover Fields</h2>
        </div>
        <div class="card-body">
            <div class="planned-table-grid">
                <?php foreach ($launchCutoverFields as $field): ?>
                    <code><?= e($field) ?></code>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Old Balance vs New ERP Ledger</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <li>Old manual supplier payable becomes opening balance at launch.</li>
                <li>New Product Cost Payable starts after the cut-off date only.</li>
                <li>Payment Made, Return Deduction, Additional Payable, and Advance Received affect the balance after opening.</li>
                <li>No old manual payable should be mixed into new dispatch payable.</li>
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Launch Lock Boundary</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <li>Owner approval is required before launch lock.</li>
                <li>Proof attachment handling is planned for later and is not active here.</li>
                <li>Opening balance should lock after ERP real launch.</li>
                <li>This foundation does not create records, upload files, change stock, or mutate payables.</li>
            </ul>
        </div>
    </div>
</div>
