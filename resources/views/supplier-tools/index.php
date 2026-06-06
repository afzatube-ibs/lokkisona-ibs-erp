<div class="page-header">
    <h1 class="page-title">Supplier Tools</h1>
    <p class="page-description">Supplier engagement hub (v0.6.1). Use the <strong>calculator</strong> and <strong>quick invoice</strong> icons in the top header bar, or open them from the supplier dashboard. Independent tools — no ERP payable, stock, or order impact.</p>
</div>

<?php view('partials.flash-messages', ['flashSuccess' => $flashSuccess ?? null, 'flashError' => $flashError ?? null]); ?>

<div class="card mb-15">
    <div class="card-header"><h2 class="card-title">How to Access Tools</h2></div>
    <div class="card-body">
        <ul class="feature-list">
            <li><strong>Calculator</strong> — topbar calculator icon (full keypad modal, no saves).</li>
            <li><strong>Quick Invoice</strong> — topbar invoice icon (multi-product, discount, advance, print).</li>
            <li>Apply migrations <code>0007</code> + <code>0010</code> manually before invoice generation writes.</li>
        </ul>
        <?php if (empty($writeGateReady)): ?>
            <p class="page-description" style="margin-top:0.75rem;"><?= e($writeGate['message'] ?? 'Tables not ready.') ?></p>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($showOwnerLog) && !empty($quickInvoiceLog)): ?>
<div class="card mb-15">
    <div class="card-header"><h2 class="card-title">Owner / Admin Quick Invoice History</h2></div>
    <div class="card-body card-body-flush">
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Customer</th>
                        <th>Subtotal</th>
                        <th>Balance Due</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($quickInvoiceLog as $entry): ?>
                    <tr>
                        <td><?= e((string) ($entry['quick_invoice_reference'] ?? '')) ?></td>
                        <td><?= e((string) ($entry['customer_name'] ?? '')) ?></td>
                        <td><?= e(number_format((float) ($entry['subtotal'] ?? $entry['invoice_total'] ?? 0), 2)) ?></td>
                        <td><?= e(number_format((float) ($entry['balance_due'] ?? $entry['invoice_total'] ?? 0), 2)) ?></td>
                        <td><?= e((string) ($entry['output_status'] ?? '')) ?></td>
                        <td><?= e((string) ($entry['created_at'] ?? '')) ?></td>
                        <td>
                            <a class="btn btn-sm btn-secondary" href="<?= e(url('/supplier-tools/quick-invoice/print/' . (int) ($entry['supplier_quick_invoice_id'] ?? 0))) ?>">View</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php elseif (!empty($showOwnerLog)): ?>
<div class="card mb-15">
    <div class="card-body">
        <p class="page-description">No quick invoices yet. Apply migrations 0007 + 0010 to enable DB-backed invoice history.</p>
    </div>
</div>
<?php endif; ?>

<details class="dev-collapse mb-15">
<summary>Planning Foundation (collapsed)</summary>
<div class="dev-collapse-body">

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Current Supplier Tools Context</h2>
        </div>
        <div class="card-body">
            <dl class="info-list">
                <div class="info-row">
                    <dt>Primary Supplier</dt>
                    <dd><?= e($currentContext['primarySupplier']) ?></dd>
                </div>
            </dl>
            <p class="page-description"><?= e($currentContext['summary']) ?></p>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Supplier Tools Purpose</h2>
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
    <?php foreach ([$independentSafetyRule, $quickInvoicePlan, $oneTimeAccessRule, $adminAuditRule] as $section): ?>
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

<div class="card-grid">
    <?php foreach ([$calculatorPlan, $noAccountingImpactRule] as $section): ?>
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

</div>
</details>
