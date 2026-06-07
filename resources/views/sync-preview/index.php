<div class="page-header page-header-compact">
    <h1 class="page-title">Sync Preview</h1>
    <p class="ops-page-subtitle">Test Sync and controlled import — max 50 orders, supplier-handled only, owner-approved import.</p>
</div>

<?php view('partials.flash-messages', ['flashSuccess' => $flashSuccess ?? null, 'flashError' => $flashError ?? null]); ?>

<?php
view('partials.write-gate-warning', [
    'writeGateReady' => $writeGateReady ?? false,
    'writeGate' => $writeGate ?? [],
    'writeGateMessage' => null,
]);
?>

<?php if (!empty($productSyncStatus)): ?>
<div class="card mb-15">
    <div class="card-header"><h2 class="card-title">Product Sync Status</h2></div>
    <div class="card-body">
        <p><strong>Mode:</strong> <?= e($productSyncStatus['mode'] ?? '') ?> — <?= e($productSyncStatus['message'] ?? '') ?></p>
        <p><strong>Product API route:</strong> <code><?= e($productSyncStatus['product_route'] ?? '') ?></code></p>
        <p><strong>Pull available:</strong> <?= !empty($productSyncStatus['product_pull_available']) ? 'Yes' : 'No' ?> · <strong>Demo/live warehouse products:</strong> <?= e((string) ($productSyncStatus['warehouse_product_count'] ?? 0)) ?> · <strong>Max per pull:</strong> <?= e((string) ($productSyncStatus['max_products_per_pull'] ?? 50)) ?></p>
        <ul class="feature-list">
            <?php foreach (($productSyncStatus['rules'] ?? []) as $rule): ?>
            <li><?= e($rule) ?></li>
            <?php endforeach; ?>
        </ul>
        <p class="page-description" style="margin-top:0.75rem;">Read-only from Lokkisona. Use <strong>Pull warehouse products</strong> below to sync catalog rows into Product Control. No OpenCart writes.</p>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($canManage) && !empty($writeGateReady)): ?>
<div class="card mb-15">
    <div class="card-header"><h2 class="card-title">Test Sync Actions</h2></div>
    <div class="card-body">
        <div class="sync-action-bar">
            <?php if (!empty($warehouseProductPullAvailable)): ?>
            <div class="sync-action">
                <span class="sync-action-step">Step 1 · Optional</span>
                <form method="post" action="<?= e(url('/sync-preview/pull-warehouse-products')) ?>">
                    <?= $csrfField ?? '' ?>
                    <input type="hidden" name="business_source_id" value="<?= e((string) ($defaultBusinessSourceId ?? 1)) ?>">
                    <button type="submit" class="btn btn-secondary btn-block">Pull warehouse products</button>
                </form>
            </div>
            <?php endif; ?>

            <div class="sync-action">
                <span class="sync-action-step">Step 2 · Preview</span>
                <form method="post" action="<?= e(url('/sync-preview/run-test-sync')) ?>">
                    <?= $csrfField ?? '' ?>
                    <input type="hidden" name="business_source_id" value="<?= e((string) ($defaultBusinessSourceId ?? 1)) ?>">
                    <button type="submit" class="btn btn-primary btn-block">Run Test Sync</button>
                </form>
            </div>

            <div class="sync-action sync-action-wide">
                <span class="sync-action-step">Step 3 · Owner import</span>
                <form method="post" action="<?= e(url('/sync-preview/import')) ?>">
                    <?= $csrfField ?? '' ?>
                    <input type="hidden" name="business_source_id" value="<?= e((string) ($defaultBusinessSourceId ?? 1)) ?>">
                    <input type="hidden" name="sync_preview_id" value="<?= e((string) ($testSyncPreview['latest_preview']['sync_preview_id'] ?? '')) ?>">
                    <label class="sync-import-confirm">
                        <input type="checkbox" name="import_confirmation" value="1" required>
                        <span>Owner confirms eligible preview import</span>
                    </label>
                    <button type="submit" class="btn btn-success btn-block">Import Eligible Rows</button>
                </form>
            </div>
        </div>
        <p class="page-description" style="margin-top:1rem;">Full Sync stays hidden. One request only — no background loops. Warehouse pull upserts only <strong>From Warehouse = Yes</strong> OpenCart products into Product Control (does not overwrite supplier cost/stock).</p>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($testSyncPreview)): ?>
<div class="card mb-15">
    <div class="card-header"><h2 class="card-title">Test Sync Preview</h2></div>
    <div class="card-body">
        <p><strong>Source:</strong> <?= e($testSyncPreview['source'] ?? '') ?></p>
        <p><strong>Status:</strong> <?= e($testSyncPreview['status'] ?? '') ?> — <?= e($testSyncPreview['message'] ?? '') ?></p>
        <ul class="feature-list">
            <?php foreach (($testSyncPreview['rules'] ?? []) as $rule): ?>
                <li><?= e($rule) ?></li>
            <?php endforeach; ?>
        </ul>
        <div class="kpi-grid kpi-grid-inline" style="margin-top:1rem;">
            <?php foreach (($testSyncPreview['preview_counts'] ?? []) as $label => $count): ?>
            <div class="kpi-card kpi-accent-muted">
                <span class="kpi-label"><?= e(str_replace('_', ' ', $label)) ?></span>
                <span class="kpi-value"><?= e((string) $count) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php if (!empty($testSyncPreview['sample_rows'])): ?>
        <div class="table-scroll" style="margin-top:1rem;">
            <table class="data-table">
                <thead><tr><th>Source Ref</th><th>Source Status</th><th>Mapped</th><th>Preview Status</th><th>Customer</th><th>Total</th></tr></thead>
                <tbody>
                    <?php foreach ($testSyncPreview['sample_rows'] as $row): ?>
                    <tr>
                        <td><?= e($row['source_order_reference'] ?? '') ?></td>
                        <td><?= e($row['source_status'] ?? '') ?></td>
                        <td><?= e($row['mapped_status'] ?? '') ?></td>
                        <td><?= e($row['preview_status'] ?? '') ?></td>
                        <td><?= e($row['customer_name'] ?? '') ?></td>
                        <td><?= e($row['order_total'] ?? '') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<details class="planning-collapsible">
    <summary class="planning-collapsible-summary">Sync Planning Foundation (developer reference)</summary>
    <div class="planning-collapsible-body">
<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Current Sync Context</h2>
        </div>
        <div class="card-body">
            <dl class="info-list">
                <div class="info-row">
                    <dt>Primary Supplier</dt>
                    <dd><?= e($currentContext['supplier']) ?></dd>
                </div>
                <div class="info-row">
                    <dt>Planned Sources</dt>
                    <dd><?= e($currentContext['sources']) ?></dd>
                </div>
            </dl>
            <p class="page-description"><?= e($currentContext['summary']) ?></p>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Sync Preview Purpose</h2>
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
    <?php foreach ([$multiSourcePlan, $lokkisonaSourcePlan, $sonamoniSourcePlan, $manualExternalRule] as $section): ?>
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
    <?php foreach ([$sharedStockRule, $erpInvoiceRule] as $section): ?>
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
        <h2 class="card-title">Lokkisona ERP Invoice Layout (Visual Reference)</h2>
    </div>
    <div class="card-body">
        <p class="page-description">Planned Lokkisona-style customer invoice sections — layout follows the real Lokkisona invoice sample as visual reference only. No old extension code is used.</p>
        <p class="page-description">Full ERP invoice and packing print planning now lives on <a href="<?= e(url('/invoice-printing')) ?>">Invoice Printing planning foundation</a>. Sync/import should prepare source invoice reference and ERP invoice template type only.</p>
    </div>
    <div class="card-body card-body-flush">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Section</th>
                    <th>Planned Fields</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($lokkisonaInvoiceLayout as $block): ?>
                <tr>
                    <td class="cell-name"><?= e($block['section']) ?></td>
                    <td class="cell-detail"><?= e(implode(' · ', $block['fields'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">ERP Invoice Print Rules</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($erpInvoicePrintRules as $rule): ?>
                    <li><?= e($rule) ?></li>
                <?php endforeach; ?>
            </ul>
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
                        <th>Template</th>
                        <th>Business Source</th>
                        <th>Note</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($invoiceTemplatePlan as $row): ?>
                    <tr>
                        <td class="cell-name"><?= e($row['template']) ?></td>
                        <td><?= e($row['source']) ?></td>
                        <td class="cell-detail"><?= e($row['note']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card-grid">
    <?php foreach ([$mappingFirstRule, $previewBeforeImportRule, $skipMissingRule, $unmappedBlockingRule] as $section): ?>
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
    <?php foreach ([$duplicateExistingRule, $independentWorkflowRule, $returnCandidateRule, $importConfirmationRule] as $section): ?>
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
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Performance / Sync Safety Rules</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($performanceSyncRules as $rule): ?>
                    <li><?= e($rule) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Import Safety Behavior</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($importSafetyBehavior as $item): ?>
                    <li><?= e($item) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Preview Totals (Before Import)</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($previewTotals as $total): ?>
                    <li><?= e($total) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Planned Preview Table Columns</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($previewTableColumns as $column): ?>
                    <li><?= e($column) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Future Preview Table Plan</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($futurePreviewTablePlan as $item): ?>
                    <li><?= e($item) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Future Import Approval Plan</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($futureImportApprovalPlan as $item): ?>
                    <li><?= e($item) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Planned Sync Preview Fields</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($plannedPreviewFields as $field): ?>
                    <li><?= e($field) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Planned Sync Preview Item Fields</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($plannedPreviewItemFields as $field): ?>
                    <li><?= e($field) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Planned Import Approval Fields</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($plannedImportApprovalFields as $field): ?>
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
            <p class="page-description">Owner and admin can view Sync Preview planning now. Staff may view/manage later based on permission. Supplier role does not manage global sync/import.</p>
        </div>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Manual Migration Required</h2>
        </div>
        <div class="card-body">
            <p>No sync preview, sync import, sync log, or order tables are created automatically and no sync/import records are written in this release.</p>
            <p class="page-description">Real sync preview and import data requires an owner/admin-reviewed manual migration before activation. No table creation, alteration, or schema repair runs on page load. OpenCart and WooCommerce are not connected in this release.</p>
        </div>
    </div>
</div>

    </div>
</details>
