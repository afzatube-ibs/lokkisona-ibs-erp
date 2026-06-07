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
    'type' => ($catalogFilters['type'] ?? '') !== 'all' ? ($catalogFilters['type'] ?? '') : '',
    'sort' => ($catalogFilters['sort'] ?? '') !== 'product_id_asc' ? ($catalogFilters['sort'] ?? '') : '',
    'chip' => ($catalogFilters['chip'] ?? '') !== 'all' ? ($catalogFilters['chip'] ?? '') : '',
], static fn ($value) => $value !== '' && $value !== null && $value !== 'all');
$paginationMeta = 'Page ' . $currentPage . ' of ' . $totalPages . ' · ' . $totalFiltered . ' records · ' . $perPage . ' per page';
$actionLabel = $canManage ? 'Manage' : 'Open';
?>
<div class="card mb-15 pcc-list-card">
    <div class="card-header product-control-table-header">
        <div>
            <h2 class="card-title">Inventory Products</h2>
            <p class="page-description mb-0">Read-only inventory monitor. Click any row or use Manage to open Product Control Center.</p>
        </div>
        <span class="page-description mb-0"><?= e($paginationMeta) ?></span>
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
            <table class="data-table product-catalog-table product-catalog-table-compact product-catalog-table-v874 product-catalog-table-v874-fixed pcc-table-tight">
                <thead>
                    <tr>
                        <th class="pcc-col-product">Product</th>
                        <th class="pcc-col-variable">Variable</th>
                        <th class="pcc-col-vendor-model">Vendor Model</th>
                        <th class="pcc-col-numeric pcc-hide-sm">Average Cost</th>
                        <th class="pcc-col-numeric pcc-hide-sm">Owner Stock</th>
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
                    <tr class="product-catalog-row" data-product-id="<?= e((string) $productId) ?>" tabindex="0">
                        <td class="pcc-col-product">
                            <div class="pcc-product-cell">
                                <div class="pcc-list-thumb">
                                    <?php if ($imageUrl !== ''): ?>
                                    <img src="<?= e($imageUrl) ?>" alt="" loading="lazy">
                                    <?php else: ?>
                                    <span class="pcc-list-thumb-empty">—</span>
                                    <?php endif; ?>
                                </div>
                                <div class="pcc-product-cell-text">
                                    <span class="pcc-product-cell-model" title="<?= e($sourceModel !== '' ? $sourceModel : 'Product #' . $productId) ?>"><?= e($sourceModel !== '' ? $sourceModel : 'Product #' . $productId) ?></span>
                                    <span class="pcc-product-cell-oc">OC ID: <?= e($ocId !== '' ? $ocId : '—') ?></span>
                                </div>
                            </div>
                        </td>
                        <td class="pcc-col-variable"><span class="badge <?= $isVariable ? 'badge-info' : 'badge-ok' ?>"><?= $isVariable ? 'Variable' : 'Simple' ?></span></td>
                        <td class="pcc-col-vendor-model"><span class="pcc-cell-ellipsis" title="<?= e($vendorModel) ?>"><?= $vendorModel !== '' ? e($vendorModel) : '—' ?></span></td>
                        <td class="pcc-col-numeric pcc-hide-sm"><?= e((string) ($row['average_cost'] ?? '—')) ?></td>
                        <td class="pcc-col-numeric pcc-hide-sm"><?= e((string) ($row['owner_stock'] ?? '—')) ?></td>
                        <td><span class="badge badge-<?= e($row['vendor_stock_class'] ?? 'muted') ?>"><?= e($row['vendor_stock_label'] ?? 'Not Set') ?></span></td>
                        <td><span class="badge badge-<?= e($healthClass) ?>"><?= e($healthLabel) ?></span></td>
                        <td class="pcc-col-actions">
                            <div class="pcc-row-actions">
                                <button type="button" class="btn btn-primary btn-sm pcc-open-btn" data-product-id="<?= e((string) $productId) ?>" data-pcc-open="details"><?= e($actionLabel) ?></button>
                                <button type="button" class="btn btn-secondary btn-sm pcc-history-btn" data-product-id="<?= e((string) $productId) ?>" data-pcc-open="history">History</button>
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
