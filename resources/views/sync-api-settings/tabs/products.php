<?php
$productSync = $productSync ?? [];
$productState = (string) ($productSync['state'] ?? 'initial');
$productRows = is_array($productSync['rows'] ?? null) ? $productSync['rows'] : [];
$productImportable = !empty($productSync['importable']);
$productCount = count($productRows);
$sourceId = (int) ($entryMapping['business_source_id'] ?? config('opencart.business_source_id', 1));
?>
<div class="card sync-hub-card-wide sync-hub-product-card">
    <div class="card-header">
        <h2 class="card-title">Product Import Preview</h2>
        <p class="sync-hub-card-lead mb-0">Warehouse products from OpenCart · max 20 per refresh.</p>
    </div>
    <div class="card-body">
        <?php if (!empty($canSyncHub) && !empty($productWriteGateReady)): ?>
        <form method="post" action="<?= e(url('/sync-api-settings/preview-products')) ?>" class="sync-hub-product-toolbar">
            <?= $csrfField ?? '' ?>
            <input type="hidden" name="redirect_to" value="/sync-api-settings?tab=products">
            <input type="hidden" name="tab" value="products">
            <input type="hidden" name="business_source_id" value="<?= e((string) $sourceId) ?>">
            <button type="submit" class="btn btn-secondary">Refresh from OpenCart</button>
            <?php if ($productState === 'has_rows'): ?>
            <span class="sync-hub-product-count"><?= e((string) $productCount) ?> product<?= $productCount === 1 ? '' : 's' ?> in preview</span>
            <?php endif; ?>
        </form>

        <?php if ($productState === 'has_rows'): ?>
        <div class="sync-hub-table-wrap sync-hub-product-table-wrap">
            <table class="data-table sync-hub-product-table">
                <thead>
                    <tr>
                        <th>Model</th>
                        <th>Product Name</th>
                        <th class="sync-hub-col-center">Warehouse</th>
                        <th class="sync-hub-col-center">Variants</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($productRows as $row): if (!is_array($row)) continue;
                        $model = trim((string) ($row['model'] ?? ''));
                        $name = trim((string) ($row['name'] ?? ''));
                        $fromWarehouse = !empty($row['from_warehouse']);
                        $optionCount = (int) ($row['option_count'] ?? 0);
                    ?>
                    <tr>
                        <td class="sync-hub-product-model"><code><?= e($model !== '' ? $model : '—') ?></code></td>
                        <td class="sync-hub-product-name"><?= e($name !== '' ? $name : '—') ?></td>
                        <td class="sync-hub-col-center">
                            <span class="sync-hub-warehouse-badge <?= $fromWarehouse ? 'is-yes' : 'is-no' ?>"><?= $fromWarehouse ? 'Yes' : 'No' ?></span>
                        </td>
                        <td class="sync-hub-col-center"><?= e((string) $optionCount) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <form method="post" action="<?= e(url('/sync-api-settings/import-products')) ?>" class="sync-hub-product-import" data-sync-hub-product-import>
            <?= $csrfField ?? '' ?>
            <input type="hidden" name="redirect_to" value="/sync-api-settings?tab=products">
            <input type="hidden" name="tab" value="products">
            <div class="sync-hub-import-panel">
                <p class="sync-hub-import-lead">These products will be added or updated in Product Control.</p>
                <div class="sync-hub-card-footer sync-hub-import-footer">
                    <label class="sync-hub-import-confirm">
                        <input type="checkbox" name="import_confirmation" value="1" data-sync-hub-product-confirm <?= $productImportable ? '' : 'disabled' ?>>
                        <span>I confirm importing <?= e((string) $productCount) ?> product<?= $productCount === 1 ? '' : 's' ?> to Product Control</span>
                    </label>
                    <button type="submit" class="btn btn-primary" data-sync-hub-product-submit disabled>Import to Product Control</button>
                </div>
            </div>
        </form>

        <?php elseif ($productState === 'empty_result'): ?>
        <div class="sync-hub-empty sync-hub-empty-state">
            <strong>No warehouse products</strong>
            <p class="mb-0">OpenCart returned no supplier warehouse items. Try again after updating the catalog.</p>
        </div>
        <?php else: ?>
        <div class="sync-hub-empty sync-hub-empty-state">
            <strong>No preview loaded</strong>
            <p class="mb-0">Refresh from OpenCart to preview warehouse products before import.</p>
        </div>
        <?php endif; ?>

        <?php else: ?>
        <div class="sync-hub-empty sync-hub-empty-state">
            <strong><?= empty($productWriteGateReady) ? 'Product sync not ready' : 'View only' ?></strong>
            <p class="mb-0"><?= empty($productWriteGateReady) ? 'Product sync tables are not ready on this environment.' : 'Sync Hub permission required to refresh and import products.' ?></p>
        </div>
        <?php endif; ?>
    </div>
</div>
