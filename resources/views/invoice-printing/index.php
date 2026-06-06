<div class="page-header">
    <h1 class="page-title">Invoice Printing</h1>
    <p class="page-description">ERP invoice persistence + print logs (v0.5.8). Customer invoice hides supplier cost. Packing slip may show internal cost snapshot. Apply migration 0007 manually first.</p>
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
    <div class="card-header"><h2 class="card-title">Generate Invoice / Packing Slip</h2></div>
    <div class="card-body">
        <form method="post" action="/invoice-printing/generate" class="form-grid">
            <?= $csrfField ?? '' ?>
            <label>Order ID<input type="number" name="order_id" min="1" required></label>
            <label>Document Type
                <select name="invoice_type" required>
                    <option value="packing_slip">Packing Slip (internal cost allowed)</option>
                    <option value="customer_invoice">Customer Invoice (no supplier cost)</option>
                </select>
            </label>
            <label>Template
                <select name="template_key">
                    <option value="lokkisona_default">Lokkisona layout</option>
                    <option value="sonamoni_default">Sonamoni layout</option>
                    <option value="manual_default">Manual/offline layout</option>
                </select>
            </label>
            <button type="submit" class="btn btn-primary">Generate from Order Snapshot</button>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($generatedInvoices)): ?>
<div class="card mb-15 print-document-card">
    <div class="card-header"><h2 class="card-title">Generated Documents</h2></div>
    <div class="card-body card-body-flush">
        <div class="table-scroll">
            <table class="data-table">
                <thead><tr><th>Reference</th><th>Order</th><th>Type</th><th>Customer</th><th>Total</th><th>Print</th></tr></thead>
                <tbody>
                    <?php foreach ($generatedInvoices as $invoice): ?>
                    <tr>
                        <td><?= e((string) ($invoice['invoice_reference'] ?? '')) ?></td>
                        <td><?= e((string) ($invoice['order_id'] ?? '')) ?></td>
                        <td><?= e((string) ($invoice['invoice_type'] ?? '')) ?></td>
                        <td><?= e((string) ($invoice['customer_name'] ?? '')) ?></td>
                        <td><?= e(number_format((float) ($invoice['invoice_total'] ?? 0), 2)) ?></td>
                        <td>
                            <?php if (!empty($canManage) && !empty($writeGateReady)): ?>
                            <form method="post" action="/invoice-printing/log-print" style="display:inline;">
                                <?= $csrfField ?? '' ?>
                                <input type="hidden" name="printable_type" value="invoice">
                                <input type="hidden" name="printable_id" value="<?= e((string) ($invoice['invoice_id'] ?? '')) ?>">
                                <input type="hidden" name="print_reference" value="<?= e((string) ($invoice['invoice_reference'] ?? '')) ?>">
                                <input type="hidden" name="invoice_type" value="<?= e((string) ($invoice['invoice_type'] ?? '')) ?>">
                                <input type="hidden" name="action" value="print">
                                <button type="submit" class="btn btn-sm btn-print-action" onclick="window.print();">Print / Log</button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($packingPreview['orders'])): ?>
<div class="card mb-15">
    <div class="card-header"><h2 class="card-title">Packing Slip Preview</h2></div>
    <div class="card-body card-body-flush">
        <p class="report-summary"><?= e($packingPreview['message'] ?? '') ?></p>
        <div class="table-scroll">
            <table class="data-table">
                <thead><tr><th>Order No</th><th>Customer</th><th>Status</th><th>Cost Snapshot</th><th>Template</th></tr></thead>
                <tbody>
                    <?php foreach ($packingPreview['orders'] as $row): ?>
                    <tr>
                        <td><?= e($row['order_reference'] ?? '') ?></td>
                        <td><?= e($row['customer_name'] ?? '') ?></td>
                        <td><?= e($row['ibs_status'] ?? '') ?></td>
                        <td><?= e($row['cost_snapshot_total'] ?? '') ?></td>
                        <td><?= e($row['template'] ?? '') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<h2 class="section-heading" style="margin: 0 0 0.75rem;">Read-Only Invoice Inventory (v0.2.8)</h2>
<p class="page-description" style="margin-bottom: 1rem;">SELECT only. No database writes. No invoice creation. No print log creation. No migration apply from this page.</p>

<?php view('partials.read-inventory-card', ['readInventory' => $readInventory, 'cardTitle' => 'Invoices']); ?>

<h2 class="section-heading" style="margin: 1.5rem 0 1rem;">Planning Foundation</h2>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Current Print Context</h2>
        </div>
        <div class="card-body">
            <dl class="info-list">
                <div class="info-row">
                    <dt>Primary Source</dt>
                    <dd><?= e($currentContext['primarySource']) ?></dd>
                </div>
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
            <h2 class="card-title">ERP Invoice Print Purpose</h2>
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

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Invoice Template Plan by Source</h2>
    </div>
    <div class="card-body card-body-flush">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Source</th>
                    <th>ERP Template</th>
                    <th>Planning Note</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($templateRules as $rule): ?>
                <tr>
                    <td class="cell-name"><?= e($rule['source']) ?></td>
                    <td><?= e($rule['template']) ?></td>
                    <td class="cell-detail"><?= e($rule['note']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <p class="page-description" style="padding: 1rem;">Manual/external orders use ERP source-aware invoice templates: Sonamoni reference orders use Sonamoni-style ERP invoice later, and offline orders use ERP manual invoice template later. See <a href="<?= e(url('/manual-orders')) ?>">Manual Orders planning foundation</a>.</p>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Lokkisona Invoice Layout Reference</h2>
    </div>
    <div class="card-body">
        <p class="page-description">The real Lokkisona invoice sample is used as visual/layout reference only. The ERP will implement its own source-aware invoice from ERP order snapshot data.</p>
    </div>
    <div class="card-body card-body-flush">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Section</th>
                    <th>Planned Content</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($invoiceLayoutSections as $section): ?>
                <tr>
                    <td class="cell-name"><?= e($section['section']) ?></td>
                    <td class="cell-detail"><?= e($section['fields']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">PIT Courier / Tracking Reference Fields</h2>
        </div>
        <div class="card-body">
            <p class="page-description">PIT Order Manager is read-only reference for courier tracking / consignment fields only.</p>
            <ul class="feature-list">
                <?php foreach ($courierReferenceFields as $field): ?>
                    <li><?= e($field) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Print Rules</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($printRules as $rule): ?>
                    <li><?= e($rule) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Planned Print Document Types</h2>
    </div>
    <div class="card-body">
        <ul class="feature-list">
            <?php foreach ($documentTypes as $type): ?>
                <li><?= e($type) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Supplier Tools Separation Rule</h2>
    </div>
    <div class="card-body">
        <p>Supplier Quick Invoice Generator is independent and does not affect official ERP invoices.</p>
        <p class="page-description">Supplier quick invoices are supplier engagement outputs only. They do not create ERP orders, do not modify official ERP invoice records, and do not affect payable, settlement, product cost, stock, courier, dispatch, returns, sync/import, or accounting. See <a href="<?= e(url('/supplier-tools')) ?>">Supplier Tools planning foundation</a>.</p>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Planned invoices Fields</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($plannedInvoiceFields as $field): ?>
                    <li><?= e($field) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Planned invoice_items Fields</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($plannedInvoiceItemFields as $field): ?>
                    <li><?= e($field) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Planned packing_prints Fields</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($plannedPackingPrintFields as $field): ?>
                    <li><?= e($field) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Planned print_logs Fields</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($plannedPrintLogFields as $field): ?>
                    <li><?= e($field) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Planned invoice_templates Fields</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($plannedInvoiceTemplateFields as $field): ?>
                    <li><?= e($field) ?></li>
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
            <p class="page-description">Owner and admin can view Invoice Printing planning now. Staff may view/manage later based on permission. Supplier role does not manage global invoice/print settings.</p>
        </div>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Manual Migration Required</h2>
        </div>
        <div class="card-body">
            <p>No invoice, invoice item, packing print, print log, or invoice template tables are created automatically and no invoice/print records are written in this release.</p>
            <p class="page-description">Real invoice/packing print data requires an owner/admin-reviewed manual migration before activation. No table creation, alteration, or schema repair runs on page load.</p>
        </div>
    </div>
</div>
