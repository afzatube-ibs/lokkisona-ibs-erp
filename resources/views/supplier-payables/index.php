<div class="page-header">
    <h1 class="page-title">Supplier Payables</h1>
    <p class="page-description">Supplier Payable &amp; Settlement Planning Foundation. No payable/settlement tables, no order/dispatch links, no OpenCart connection, and no payable records are written in this release.</p>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Current Supplier Context</h2>
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
            <h2 class="card-title">Payable &amp; Settlement Purpose</h2>
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
    <?php foreach ([$productCostPayable, $supplierInvoice, $additionalPayable, $returnDeduction, $paymentMade, $advanceReceived] as $section): ?>
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><?= e($section['title']) ?></h2>
        </div>
        <div class="card-body">
            <p><?= e($section['summary']) ?></p>
            <ul class="feature-list">
                <?php foreach ($section['points'] as $point): ?>
                    <li><?= e($point) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title"><?= e($netPayable['title']) ?></h2>
    </div>
    <div class="card-body">
        <p><strong><?= e($netPayable['summary']) ?></strong></p>
        <ul class="feature-list">
            <?php foreach ($netPayable['points'] as $point): ?>
                <li><?= e($point) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Supplier Tools Separation Rule</h2>
    </div>
    <div class="card-body">
        <p>Supplier Tools do not affect payables or settlement unless owner/admin explicitly reviews/converts later.</p>
        <p class="page-description">Supplier Quick Invoice Generator and Simple Calculator are independent engagement tools only. They do not create Product Cost Payable, Supplier Invoice, settlement, payment, deduction, product cost, stock, courier charge, order, dispatch, return, sync/import, or accounting entries. See <a href="<?= e(url('/supplier-tools')) ?>">Supplier Tools planning foundation</a>.</p>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Approval &amp; Audit Rule</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($approvalRule as $rule): ?>
                    <li><?= e($rule) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Future Supplier Self-Request Rule</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($selfRequestRule as $rule): ?>
                    <li><?= e($rule) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Future Report / Export Plan</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($reportPlan as $item): ?>
                    <li><?= e($item) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

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
            <p class="page-description">Owner and admin can view the Supplier Payable planning foundation now. Staff may view/manage later based on permission. Supplier role should later see only its own payable/settlement area, not all suppliers.</p>
        </div>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Planned Payable Ledger Fields</h2>
        </div>
        <div class="card-body">
            <p class="page-description">Documented only. No payable tables are created automatically and no records are written.</p>
            <ul class="feature-list">
                <?php foreach ($plannedLedgerFields as $field): ?>
                    <li><code><?= e($field) ?></code></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Planned Supplier Invoice Fields</h2>
        </div>
        <div class="card-body">
            <p class="page-description">Documented only. No payable tables are created automatically and no records are written.</p>
            <ul class="feature-list">
                <?php foreach ($plannedInvoiceFields as $field): ?>
                    <li><code><?= e($field) ?></code></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Planned Supplier Payment Fields</h2>
        </div>
        <div class="card-body">
            <p class="page-description">Documented only. No payable tables are created automatically and no records are written.</p>
            <ul class="feature-list">
                <?php foreach ($plannedPaymentFields as $field): ?>
                    <li><code><?= e($field) ?></code></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Planned Deduction Fields</h2>
        </div>
        <div class="card-body">
            <p class="page-description">Documented only. No payable tables are created automatically and no records are written.</p>
            <ul class="feature-list">
                <?php foreach ($plannedDeductionFields as $field): ?>
                    <li><code><?= e($field) ?></code></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>
