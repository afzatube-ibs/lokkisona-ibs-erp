<?php
$catalogRows = $catalogRows ?? [];
$pagination = $catalogPagination ?? [];
$totalFiltered = (int) ($pagination['total'] ?? count($catalogRows));
$catalogTotal = (int) ($catalogTotal ?? 0);
$currentPage = max(1, (int) ($pagination['page'] ?? 1));
$totalPages = max(1, (int) ($pagination['total_pages'] ?? 1));
$perPage = (int) ($pagination['per_page'] ?? 20);
$canManage = !empty($canManage);
$pageQuery = array_filter([
    'q' => $catalogFilters['q'] ?? '',
    'product_name' => $catalogFilters['product_name'] ?? '',
    'supplier_model' => $catalogFilters['supplier_model'] ?? '',
    'category' => ($catalogFilters['category'] ?? '') !== '' ? ($catalogFilters['category'] ?? '') : '',
    'type' => ($catalogFilters['type'] ?? '') !== 'all' ? ($catalogFilters['type'] ?? '') : '',
    'sort' => ($catalogFilters['sort'] ?? '') !== 'product_id_asc' ? ($catalogFilters['sort'] ?? '') : '',
    'chip' => ($catalogFilters['chip'] ?? '') !== 'all' ? ($catalogFilters['chip'] ?? '') : '',
    'per_page' => $perPage !== 20 ? (string) $perPage : '',
], static fn ($value) => $value !== '' && $value !== null && $value !== 'all');
$paginationMeta = 'Page ' . $currentPage . ' of ' . $totalPages . ' · ' . $totalFiltered . ' records · ' . $perPage . ' per page';
$actionLabel = $canManage ? 'Manage' : 'Open';
?>
<div class="card pc-card pcc-list-card">
    <div class="card-header product-control-table-header pc-table-header">
        <div>
            <h2 class="card-title">Inventory Products</h2>
            <p class="pc-table-subtitle">Local snapshot view. Click Manage to edit supplier fields.</p>
        </div>
        <span class="pc-pagination-meta"><?= e($paginationMeta) ?></span>
    </div>
    <div class="card-body">
        <?php if (!$tableReady): ?>
        <p class="page-description"><?= e($productReadInventory['status_message'] ?? 'Product table unavailable.') ?></p>
        <?php elseif ($totalFiltered === 0 && $catalogTotal === 0): ?>
        <p class="page-description">No supplier products yet.<?php if (empty($isSupplierView)): ?> Use <strong>Refresh Products</strong> above to pull Dispatch Location catalog (from_warehouse = 1).<?php else: ?> Ask the owner to refresh warehouse products.<?php endif; ?></p>
        <?php elseif ($totalFiltered === 0): ?>
        <p class="page-description">No products match the current filters. <a href="<?= e(url('/product-control')) ?>">Clear filters</a></p>
        <?php else: ?>
        <div class="table-scroll pcc-list-scroll">
            <table class="data-table product-catalog-table product-catalog-table-v874 product-catalog-table-v874-fixed pc-product-table">
                <thead>
                    <tr>
                        <th class="pcc-col-product">Product</th>
                        <th class="pcc-col-variable">Type / Variable</th>
                        <th class="pcc-col-vendor-model">Vendor Model</th>
                        <th class="pcc-col-numeric pcc-hide-sm">Supplier Cost</th>
                        <th class="pcc-col-numeric pcc-hide-sm">Stock</th>
                        <th class="pcc-col-numeric">Vendor Stock</th>
                        <th class="pcc-col-health">Health</th>
                        <th class="pcc-col-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($catalogRows as $row): ?>
                    <?php
                    $productId = (int) ($row['product_id'] ?? 0);
                    $isVariable = ($row['type'] ?? '') === 'variable';
                    $imageUrl = (string) ($row['image_url'] ?? '');
                    $vendorModel = trim((string) ($row['supplier_model'] ?? ''));
                    $sourceModel = trim((string) ($row['source_model'] ?? ''));
                    $ocId = trim((string) ($row['source_product_id'] ?? ''));
                    $healthLabel = (string) ($row['health_status_display'] ?? $row['health_label'] ?? '—');
                    $healthClass = (string) ($row['health_status_class'] ?? $row['health_class'] ?? 'muted');
                    ?>
                    <tr class="product-catalog-row pc-table-row" data-product-id="<?= e((string) $productId) ?>" tabindex="0">
                        <td class="pcc-col-product">
                            <div class="pcc-product-cell">
                                <div class="pcc-list-thumb pc-list-thumb">
                                    <?php if ($imageUrl !== ''): ?>
                                    <img src="<?= e($imageUrl) ?>" alt="" loading="lazy">
                                    <?php else: ?>
                                    <span class="pcc-list-thumb-empty">—</span>
                                    <?php endif; ?>
                                </div>
                                <div class="pcc-product-cell-text">
                                    <span class="pcc-product-cell-model" title="<?= e($sourceModel !== '' ? $sourceModel : 'Product #' . $productId) ?>"><?= e($sourceModel !== '' ? $sourceModel : 'Product #' . $productId) ?></span>
                                    <span class="pcc-product-cell-oc">Product ID: <?= e($ocId !== '' ? $ocId : '—') ?></span>
                                </div>
                            </div>
                        </td>
                        <td class="pcc-col-variable"><span class="badge pc-cell-badge <?= $isVariable ? 'badge-info' : 'badge-ok' ?>"><?= $isVariable ? 'Variable' : 'Simple' ?></span></td>
                        <td class="pcc-col-vendor-model"><span class="pcc-cell-ellipsis" title="<?= e($vendorModel) ?>"><?= $vendorModel !== '' ? e($vendorModel) : '—' ?></span></td>
                        <td class="pcc-col-numeric pcc-hide-sm"><?= e((string) ($row['average_cost'] ?? '—')) ?></td>
                        <td class="pcc-col-numeric pcc-hide-sm"><?= e((string) ($row['owner_stock'] ?? '—')) ?></td>
                        <td class="pcc-col-numeric"><span class="badge pc-cell-badge badge-<?= e($row['vendor_stock_class'] ?? 'muted') ?>"><?= e($row['vendor_stock_label'] ?? 'Not Set') ?></span></td>
                        <td class="pcc-col-health"><span class="badge pc-cell-badge badge-<?= e($healthClass) ?>"><?= e($healthLabel) ?></span></td>
                        <td class="pcc-col-actions">
                            <div class="pcc-row-actions pc-row-actions">
                                <button type="button" class="btn btn-primary btn-sm pcc-open-btn" data-product-id="<?= e((string) $productId) ?>" data-pcc-open="details"><?= e($actionLabel) ?></button>
                                <button type="button" class="btn btn-ghost btn-sm pcc-history-btn pc-btn-muted" data-product-id="<?= e((string) $productId) ?>" data-pcc-open="history">History</button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
        view('partials.sync-pagination', [
            'page' => $currentPage,
            'pageParam' => 'page',
            'baseUrl' => url('/product-control'),
            'pagination' => $pagination,
            'otherPageQuery' => $pageQuery,
        ]);
        ?>
        <?php endif; ?>
    </div>
</div>
