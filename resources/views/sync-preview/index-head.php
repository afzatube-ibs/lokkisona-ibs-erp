<div class="page-header page-header-compact">
    <h1 class="page-title">Sync Preview</h1>
    <p class="ops-page-subtitle">Live connection test — max 20 rows per page, preview before import, supplier-handled orders only, Dispatch Location bridge required.</p>
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
    <div class="card-header"><h2 class="card-title">Connection &amp; Product Sync Status</h2></div>
    <div class="card-body">
        <p><strong>Mode:</strong> <?= e($productSyncStatus['mode'] ?? '') ?> — <?= e($productSyncStatus['message'] ?? '') ?></p>
        <p><strong>Connection test:</strong> <?= !empty($productSyncStatus['connection_ok']) ? 'OK' : 'Not ready' ?>
            · <strong>Dispatch Location bridge:</strong> <?= ($productSyncStatus['bridge_available'] ?? null) === false ? 'Missing' : (($productSyncStatus['bridge_available'] ?? null) === true ? 'OK' : '—') ?></p>
        <p><strong>Product API route:</strong> <code><?= e($productSyncStatus['product_route'] ?? '') ?></code></p>
        <p><strong>Preview available:</strong> <?= !empty($productSyncStatus['product_pull_available']) ? 'Yes' : 'No' ?> · <strong>Max per page:</strong> <?= e((string) ($productSyncStatus['max_products_per_page'] ?? 20)) ?></p>
        <p><strong>Sync/API settings:</strong> <a href="<?= e($productSyncStatus['settings_url'] ?? url('/sync-api-settings')) ?>">System → Sync/API Settings</a>
            · <strong>Read-only lock:</strong> <?= !empty($productSyncStatus['read_only_lock']) ? 'On' : 'Off' ?></p>
        <ul class="feature-list">
            <?php foreach (($productSyncStatus['rules'] ?? []) as $rule): ?>
            <li><?= e($rule) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
<?php view('partials.product-sync-diagnostics', ['productSyncDiagnostics' => $productSyncDiagnostics ?? null]); ?>
<?php endif; ?>

<?php if (!empty($canManage) && !empty($productWriteGateReady)): ?>
<div class="card mb-15">
    <div class="card-header"><h2 class="card-title">Product Sync Preview</h2></div>
    <div class="card-body">
        <p class="page-description">Step 1: load read-only preview (Dispatch Location <code>from_warehouse = 1</code> only). Step 2: owner confirms import into Product Control. Supplier fields are never changed from OpenCart on re-import.</p>
        <div class="sync-action-bar">
            <div class="sync-action">
                <form method="post" action="<?= e(url('/sync-preview/preview-products')) ?>">
                    <?= $csrfField ?? '' ?>
                    <input type="hidden" name="business_source_id" value="<?= e((string) ($defaultBusinessSourceId ?? 1)) ?>">
                    <input type="hidden" name="page" value="<?= e((string) ($productPage ?? 1)) ?>">
                    <button type="submit" class="btn btn-primary btn-block">Load product preview (page <?= e((string) ($productPage ?? 1)) ?>)</button>
                </form>
            </div>
        </div>
        <?php
        view('partials.sync-pagination', [
            'page' => $productPage ?? 1,
            'pageParam' => 'product_page',
            'pagination' => [
                'per_page' => (int) ($productPreview['per_page'] ?? 20),
                'has_previous' => (bool) ($productPreview['has_previous'] ?? (($productPage ?? 1) > 1)),
                'has_next' => (bool) ($productPreview['has_next'] ?? false),
            ],
            'otherPageQuery' => ['order_page' => $orderPage ?? 1],
        ]);
        ?>
        <?php if (!empty($productPreview['bridge_warning'])): ?>
        <p class="page-description" style="margin-top:1rem;color:var(--color-warn, #b45309);"><strong><?= e($productPreview['bridge_warning']) ?></strong></p>
        <?php elseif (!empty($productPreview['rows'])): ?>
        <div class="table-scroll" style="margin-top:1rem;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>OC ID</th>
                        <th>Name</th>
                        <th>Model</th>
                        <th>Price (RO)</th>
                        <th>Status</th>
                        <th>Stock</th>
                        <th>Sync status</th>
                        <th>Options</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($productPreview['rows'] as $row): ?>
                    <tr>
                        <td><code><?= e($row['source_product_id'] ?? '') ?></code></td>
                        <td><?= e($row['product_name'] ?? '') ?></td>
                        <td><code><?= e($row['source_model'] ?? '') ?></code></td>
                        <td><?= e($row['source_price'] !== null ? (string) $row['source_price'] : '—') ?></td>
                        <td><?= e($row['source_status'] !== '' ? $row['source_status'] : '—') ?></td>
                        <td><?= e((string) ($row['source_stock'] ?? '—')) ?></td>
                        <td><span class="badge <?= ($row['sync_status'] ?? '') === 'existing' ? 'badge-info' : 'badge-ok' ?>"><?= e($row['sync_status'] ?? '') ?></span></td>
                        <td><?= e((string) ($row['option_count'] ?? 0)) ?></td>
                    </tr>
                    <?php if (!empty($row['options'])): ?>
                    <tr class="sync-option-detail-row">
                        <td colspan="8">
                            <table class="data-table data-table-compact">
                                <thead><tr><th>Option</th><th>Value</th><th>Model</th><th>Price</th><th>Stock</th><th>Req</th><th>Sub</th><th>Image</th></tr></thead>
                                <tbody>
                                    <?php foreach ($row['options'] as $opt): ?>
                                    <tr>
                                        <td><?= e($opt['option_name'] ?? '') ?></td>
                                        <td><?= e($opt['option_value'] ?? '') ?></td>
                                        <td><code><?= e($opt['source_model'] ?? '—') ?></code></td>
                                        <td><?= e($opt['price_display'] ?? '—') ?></td>
                                        <td><?= e((string) ($opt['source_stock'] ?? '—')) ?></td>
                                        <td><?= e($opt['required'] !== null ? (string) $opt['required'] : '—') ?></td>
                                        <td><?= e($opt['subtract'] !== null ? (string) $opt['subtract'] : '—') ?></td>
                                        <td><?= e($opt['option_image_path'] !== '' ? 'Yes' : '—') ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if (!empty($productPreview['extensions']['related_options_detected'])): ?>
        <p class="page-description" style="margin-top:0.75rem;">Related Options extension detected — exact combinations are documented only, not imported in v1.7.1.</p>
        <?php endif; ?>
        <form method="post" action="<?= e(url('/sync-preview/import-products')) ?>" style="margin-top:1rem;">
            <?= $csrfField ?? '' ?>
            <input type="hidden" name="business_source_id" value="<?= e((string) ($defaultBusinessSourceId ?? 1)) ?>">
            <input type="hidden" name="page" value="<?= e((string) ($productPage ?? 1)) ?>">
            <label class="sync-import-confirm">
                <input type="checkbox" name="import_confirmation" value="1" required>
                <span>Owner confirms import of previewed warehouse products (page <?= e((string) ($productPage ?? 1)) ?>)</span>
            </label>
            <button type="submit" class="btn btn-success">Import Products</button>
        </form>
        <?php elseif (!empty($productPreview)): ?>
        <p class="page-description" style="margin-top:1rem;">No warehouse products on this page.</p>
        <?php else: ?>
        <p class="page-description" style="margin-top:1rem;">Load product preview to fetch live/demo catalog rows.</p>
        <?php endif; ?>
    </div>
</div>
<?php elseif (!empty($canManage)): ?>
<?php
view('partials.write-gate-warning', [
    'writeGateReady' => $productWriteGateReady ?? false,
    'writeGate' => $productWriteGate ?? [],
    'writeGateMessage' => 'Product sync import requires ibs_products, ibs_product_variants, and ibs_business_sources. Apply migrations from Dev DB Activation before loading preview or import.',
]);
?>
<?php endif; ?>

<?php if (!empty($canManage) && !empty($writeGateReady)): ?>
<div class="card mb-15">
    <div class="card-header"><h2 class="card-title">Order Sync Preview</h2></div>
    <div class="card-body">
        <p class="page-description">Supplier-handled orders only (status mapping). Product mapping alone does not import orders. Skip Missing / status 0.</p>
        <div class="sync-action-bar">
            <div class="sync-action">
                <form method="post" action="<?= e(url('/sync-preview/run-test-sync')) ?>">
                    <?= $csrfField ?? '' ?>
                    <input type="hidden" name="business_source_id" value="<?= e((string) ($defaultBusinessSourceId ?? 1)) ?>">
                    <input type="hidden" name="page" value="<?= e((string) ($orderPage ?? 1)) ?>">
                    <button type="submit" class="btn btn-primary btn-block">Load order preview (page <?= e((string) ($orderPage ?? 1)) ?>)</button>
                </form>
            </div>
        </div>
        <?php
        view('partials.sync-pagination', [
            'page' => $orderPage ?? 1,
            'pageParam' => 'order_page',
            'pagination' => $testSyncPreview['pagination'] ?? ['has_previous' => ($orderPage ?? 1) > 1, 'has_next' => false, 'per_page' => 20],
            'otherPageQuery' => ['product_page' => $productPage ?? 1],
        ]);
        ?>
        <?php if (!empty($testSyncPreview['preview_counts'])): ?>
        <div class="kpi-grid kpi-grid-inline" style="margin-top:1rem;">
            <?php foreach (($testSyncPreview['preview_counts'] ?? []) as $label => $count): ?>
            <div class="kpi-card kpi-accent-muted">
                <span class="kpi-label"><?= e(str_replace('_', ' ', $label)) ?></span>
                <span class="kpi-value"><?= e((string) $count) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php if (!empty($testSyncPreview['display_rows'])): ?>
        <div class="table-scroll" style="margin-top:1rem;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>OC Order</th>
                        <th>Customer</th>
                        <th>Phone</th>
                        <th>Product</th>
                        <th>Qty</th>
                        <th>Source status</th>
                        <th>Courier</th>
                        <th>Consignment</th>
                        <th>Supplier?</th>
                        <th>IBS status</th>
                        <th>Imported?</th>
                        <th>Preview</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($testSyncPreview['display_rows'] as $row): ?>
                    <tr>
                        <td><code><?= e($row['source_order_reference'] ?? '') ?></code></td>
                        <td><?= e($row['customer_name'] ?? '') ?></td>
                        <td><?= e($row['customer_phone'] ?? '') ?></td>
                        <td><?= e($row['product_card'] ?? '') ?></td>
                        <td><?= e((string) ($row['total_quantity'] ?? 0)) ?></td>
                        <td><?= e($row['source_status'] ?? '') ?></td>
                        <td><?= e($row['courier_status'] ?? '—') ?></td>
                        <td><?= e($row['consignment_id'] ?? '—') ?></td>
                        <td><?= e($row['supplier_handled'] ?? '') ?><br><small><?= e($row['supplier_handled_reason'] ?? '') ?></small></td>
                        <td><?= e($row['mapped_status'] ?? '—') ?></td>
                        <td><?= e($row['already_imported'] ?? 'No') ?></td>
                        <td><?= e($row['preview_status'] ?? '') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <form method="post" action="<?= e(url('/sync-preview/import')) ?>" style="margin-top:1rem;">
            <?= $csrfField ?? '' ?>
            <input type="hidden" name="business_source_id" value="<?= e((string) ($defaultBusinessSourceId ?? 1)) ?>">
            <input type="hidden" name="page" value="<?= e((string) ($orderPage ?? 1)) ?>">
            <input type="hidden" name="sync_preview_id" value="<?= e((string) ($testSyncPreview['latest_preview']['sync_preview_id'] ?? '')) ?>">
            <label class="sync-import-confirm">
                <input type="checkbox" name="import_confirmation" value="1" required>
                <span>Owner confirms import of eligible orders on preview page <?= e((string) ($orderPage ?? 1)) ?></span>
            </label>
            <button type="submit" class="btn btn-success">Import Eligible Orders</button>
        </form>
        <?php else: ?>
        <p class="page-description" style="margin-top:1rem;">Load order preview to classify supplier-handled rows.</p>
        <?php endif; ?>
        <p class="page-description" style="margin-top:1rem;">One request per button — no background sync. IBS workflow is not changed on re-import.</p>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($testSyncPreview)): ?>
<div class="card mb-15">
    <div class="card-header"><h2 class="card-title">Sync Rules Summary</h2></div>
    <div class="card-body">
        <p><strong>Source:</strong> <?= e($testSyncPreview['source'] ?? '') ?></p>
        <p><strong>Status:</strong> <?= e($testSyncPreview['status'] ?? '') ?> — <?= e($testSyncPreview['message'] ?? '') ?></p>
        <ul class="feature-list">
            <?php foreach (($testSyncPreview['rules'] ?? []) as $rule): ?>
                <li><?= e($rule) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
<?php endif; ?>
