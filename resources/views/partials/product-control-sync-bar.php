<?php
$lastSync = trim((string) ($lastCatalogSyncAt ?? ''));
$lastSyncDisplay = $lastSync !== '' ? $lastSync : 'Never';
$sourceLabel = (string) ($sourceSyncLabel ?? 'OpenCart read-only');
$previewCount = is_array($productPreview ?? null) ? count($productPreview['rows'] ?? []) : 0;
$catalogTotal = (int) ($catalogTotal ?? 0);
$hasPreviewSession = !empty($productPreview);
$previewOpen = $hasPreviewSession && $catalogTotal === 0 && $previewCount > 0;
?>
<div class="pcc-sync-strip card mb-15">
    <div class="card-body pcc-sync-strip-body">
        <div class="pcc-sync-strip-meta">
            <span class="pcc-sync-meta-item"><strong>Last sync:</strong> <?= e($lastSyncDisplay) ?></span>
            <span class="pcc-sync-meta-item"><strong>Source:</strong> <?= e($sourceLabel) ?></span>
            <?php if (!empty($productSyncStatus['connection_ok'])): ?>
            <span class="badge badge-ok">Connection OK</span>
            <?php elseif (!empty($canViewSync)): ?>
            <span class="badge badge-warn">Connection not ready</span>
            <?php endif; ?>
        </div>

        <?php if (!empty($canManageSync) && !empty($productWriteGateReady)): ?>
        <div class="pcc-sync-strip-actions">
            <form method="post" action="<?= e(url('/sync-preview/preview-products')) ?>" class="pcc-sync-action-form">
                <?= $csrfField ?? '' ?>
                <input type="hidden" name="business_source_id" value="<?= e((string) ($defaultBusinessSourceId ?? 1)) ?>">
                <input type="hidden" name="page" value="<?= e((string) ($productPage ?? 1)) ?>">
                <button type="submit" class="btn btn-primary btn-sm">Load Supplier Product Preview</button>
            </form>
            <?php if ($previewCount > 0): ?>
            <button type="button" class="btn btn-success btn-sm" disabled title="Confirm import inside preview panel below">Import Previewed Products</button>
            <?php else: ?>
            <button type="button" class="btn btn-success btn-sm" disabled title="Load preview first">Import Previewed Products</button>
            <?php endif; ?>
            <form method="post" action="<?= e(url('/sync-preview/reset-product-sync')) ?>" class="pcc-sync-action-form" onsubmit="return confirm('Reset synced product catalog data from ERP? Orders and payables are not affected.');">
                <?= $csrfField ?? '' ?>
                <input type="hidden" name="redirect_to" value="<?= e($syncRedirectTo ?? '/product-control') ?>">
                <input type="hidden" name="reset_confirmation" value="1">
                <button type="submit" class="btn btn-danger btn-sm">Reset Product Sync Data</button>
            </form>
        </div>

        <?php if ($hasPreviewSession): ?>
        <details class="pcc-preview-details"<?= $previewOpen ? ' open' : '' ?>>
            <summary class="pcc-preview-summary">Preview page <?= e((string) ($productPage ?? 1)) ?> · <?= e((string) $previewCount) ?> row(s)<?= $catalogTotal > 0 ? ' · catalog has ' . e((string) $catalogTotal) . ' product(s)' : '' ?></summary>
            <div class="pcc-preview-body">
                <?php
                view('partials.sync-pagination', [
                    'page' => $productPage ?? 1,
                    'pageParam' => 'product_page',
                    'baseUrl' => url($syncPaginationBase ?? '/product-control'),
                    'pagination' => [
                        'per_page' => (int) ($productPreview['per_page'] ?? 20),
                        'has_previous' => (bool) ($productPreview['has_previous'] ?? (($productPage ?? 1) > 1)),
                        'has_next' => (bool) ($productPreview['has_next'] ?? false),
                    ],
                    'otherPageQuery' => array_filter([
                        'page' => $catalogPage ?? null,
                        'q' => $catalogFilters['q'] ?? null,
                        'chip' => ($catalogFilters['chip'] ?? '') !== 'all' ? ($catalogFilters['chip'] ?? null) : null,
                    ], static fn ($v) => $v !== null && $v !== ''),
                ]);
                ?>
                <?php if (!empty($productPreview['rows'])): ?>
                <div class="table-scroll">
                    <table class="data-table data-table-compact">
                        <thead>
                            <tr>
                                <th>OC ID</th>
                                <th>Name</th>
                                <th>Model</th>
                                <th>Stock</th>
                                <th>Options</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($productPreview['rows'] as $row): ?>
                            <tr>
                                <td><code><?= e($row['source_product_id'] ?? '') ?></code></td>
                                <td><?= e($row['product_name'] ?? '') ?></td>
                                <td><code><?= e($row['source_model'] ?? '') ?></code></td>
                                <td><?= e((string) ($row['source_stock'] ?? '—')) ?></td>
                                <td><?= e((string) ($row['option_count'] ?? 0)) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <form method="post" action="<?= e(url('/sync-preview/import-products')) ?>" class="pcc-preview-import-confirm">
                    <?= $csrfField ?? '' ?>
                    <input type="hidden" name="business_source_id" value="<?= e((string) ($defaultBusinessSourceId ?? 1)) ?>">
                    <input type="hidden" name="page" value="<?= e((string) ($productPage ?? 1)) ?>">
                    <label class="sync-import-confirm">
                        <input type="checkbox" name="import_confirmation" value="1" required>
                        <span>Owner confirms import of previewed warehouse products</span>
                    </label>
                    <button type="submit" class="btn btn-success btn-sm">Import Previewed Products</button>
                </form>
                <?php else: ?>
                <p class="page-description">No warehouse products on this preview page.</p>
                <?php endif; ?>
            </div>
        </details>
        <?php endif; ?>

        <?php elseif (!empty($canManageSync)): ?>
        <?php view('partials.write-gate-warning', [
            'writeGateReady' => $productWriteGateReady ?? false,
            'writeGate' => $productWriteGate ?? [],
            'writeGateMessage' => 'Product sync requires ibs_products, ibs_product_variants, and ibs_business_sources tables.',
        ]); ?>
        <?php endif; ?>
    </div>
</div>
