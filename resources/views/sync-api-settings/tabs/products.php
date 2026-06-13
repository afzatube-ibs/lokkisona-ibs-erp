<?php
$productSync = $productSync ?? [];
$productState = (string) ($productSync['state'] ?? 'initial');
$productRows = is_array($productSync['rows'] ?? null) ? $productSync['rows'] : [];
$productImportable = !empty($productSync['importable']);
$sourceId = (int) ($entryMapping['business_source_id'] ?? config('opencart.business_source_id', 1));
?>
<div class="card sync-hub-card-wide">
    <div class="card-header">
        <h2 class="card-title">Product Sync</h2>
        <p class="page-description mb-0">Warehouse products only (<code>from_warehouse=1</code>), max 20 per refresh. Supplier fields preserved on re-import.</p>
    </div>
    <div class="card-body">
        <?php if (!empty($canSyncHub) && !empty($productWriteGateReady)): ?>
        <form method="post" action="<?= e(url('/sync-api-settings/preview-products')) ?>" class="mb-15">
            <?= $csrfField ?? '' ?>
            <input type="hidden" name="redirect_to" value="/sync-api-settings?tab=products">
            <input type="hidden" name="tab" value="products">
            <input type="hidden" name="business_source_id" value="<?= e((string) $sourceId) ?>">
            <button type="submit" class="btn btn-secondary">Refresh Products</button>
        </form>

        <?php if ($productState === 'has_rows'): ?>
        <div class="sync-hub-table-wrap mb-15">
            <table class="data-table">
                <thead><tr><th>Model</th><th>Name</th><th>Warehouse</th><th>Options</th></tr></thead>
                <tbody>
                    <?php foreach ($productRows as $row): if (!is_array($row)) continue; ?>
                    <tr>
                        <td><code><?= e((string) ($row['model'] ?? '')) ?></code></td>
                        <td><?= e((string) ($row['name'] ?? '')) ?></td>
                        <td><?= !empty($row['from_warehouse']) ? 'Yes' : 'No' ?></td>
                        <td><?= e((string) ($row['option_count'] ?? 0)) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <form method="post" action="<?= e(url('/sync-api-settings/import-products')) ?>" data-sync-hub-product-import>
            <?= $csrfField ?? '' ?>
            <input type="hidden" name="redirect_to" value="/sync-api-settings?tab=products">
            <input type="hidden" name="tab" value="products">
            <div class="sync-hub-import-row">
                <label class="sync-hub-import-confirm">
                    <input type="checkbox" name="import_confirmation" value="1" data-sync-hub-product-confirm <?= $productImportable ? '' : 'disabled' ?>>
                    <span>I confirm product import from preview</span>
                </label>
                <button type="submit" class="btn btn-primary" data-sync-hub-product-submit disabled>Import Products</button>
            </div>
        </form>
        <?php elseif ($productState === 'empty_result'): ?>
        <div class="sync-hub-empty">No warehouse products found</div>
        <?php else: ?>
        <div class="sync-hub-empty">Click <strong>Refresh Products</strong> to load warehouse items.</div>
        <?php endif; ?>

        <p class="form-help mt-15">Product Control editing stays on the Products page.</p>
        <?php else: ?>
        <p class="page-description"><?= empty($productWriteGateReady) ? 'Product sync tables not ready.' : 'View-only or missing Sync Hub permission.' ?></p>
        <?php endif; ?>
    </div>
</div>
