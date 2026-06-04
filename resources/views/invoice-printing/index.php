<div class="page-header">
    <h1 class="page-title">Invoice Printing</h1>
    <p class="page-description">ERP Invoice and Packing Print Planning Foundation. No real invoices are generated, no invoice tables are created automatically, and no invoice/print records are written in this release.</p>
</div>

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
