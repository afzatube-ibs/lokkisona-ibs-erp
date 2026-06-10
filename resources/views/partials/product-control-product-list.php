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
    'supplier_model' => $catalogFilters['supplier_model'] ?? '',
    'category' => ($catalogFilters['category'] ?? '') !== '' ? ($catalogFilters['category'] ?? '') : '',
    'type' => ($catalogFilters['type'] ?? '') !== 'all' ? ($catalogFilters['type'] ?? '') : '',
    'sort' => ($catalogFilters['sort'] ?? '') !== 'product_id_asc' ? ($catalogFilters['sort'] ?? '') : '',
    'chip' => ($catalogFilters['chip'] ?? '') !== 'all' ? ($catalogFilters['chip'] ?? '') : '',
    'per_page' => $perPage !== 20 ? (string) $perPage : '',
], static fn ($value) => $value !== '' && $value !== null && $value !== 'all');
$paginationMeta = 'Page ' . $currentPage . ' of ' . $totalPages . ' · ' . $perPage . ' per page';
$productCountLabel = $totalFiltered . ' Product' . ($totalFiltered === 1 ? '' : 's');
$actionLabel = $canManage ? 'Manage' : 'Open';
?>
<div class="card pc-card pcc-list-card">
    <div class="card-header product-control-table-header pc-table-header">
        <div>
            <h2 class="card-title">Inventory Products</h2>
            <p class="pc-table-subtitle pc-table-count"><?= e($productCountLabel) ?></p>
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
            <table class="data-table product-catalog-table product-catalog-table-v874 product-catalog-table-v874-fixed pc-product-table pc-product-table-v2171">
                <thead>
                    <tr>
                        <th class="pcc-col-id">Product ID</th>
                        <th class="pcc-col-model-no">Model No</th>
                        <th class="pcc-col-ibs-category">IBS Category</th>
                        <th class="pcc-col-ibs-model">IBS Model</th>
                        <th class="pcc-col-type">Type</th>
                        <th class="pcc-col-live-stock">Live Stock</th>
                        <th class="pcc-col-ibs-stock">IBS Stock</th>
                        <th class="pcc-col-rate">Rate</th>
                        <th class="pcc-col-health">Health</th>
                        <th class="pcc-col-action">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($catalogRows as $row): ?>
                    <?php
                    $productId = (int) ($row['product_id'] ?? 0);
                    $isVariable = ($row['type'] ?? '') === 'variable';
                    $imageUrl = (string) ($row['image_url'] ?? '');
                    $ibsModel = trim((string) ($row['supplier_model'] ?? ''));
                    $modelNo = trim((string) ($row['source_model'] ?? ''));
                    $productName = trim((string) ($row['product_name'] ?? ''));
                    $originId = trim((string) ($row['source_product_id'] ?? ''));
                    $displayProductId = $originId !== '' ? $originId : (string) $productId;
                    $modelNoLabel = $modelNo !== '' ? $modelNo : '—';
                    $modelTooltip = $productName !== '' ? $productName : ($modelNo !== '' ? $modelNo : 'Product #' . $productId);
                    $showModelSubId = $modelNo === '' && $displayProductId !== '';
                    $healthLabel = (string) ($row['health_status_display'] ?? '—');
                    $healthClass = (string) ($row['health_status_class'] ?? 'muted');
                    $vendorStockLabel = (string) ($row['vendor_stock_label'] ?? '');
                    $vendorStockValue = (int) ($row['vendor_stock'] ?? 0);
                    $ibsStockDisplay = $vendorStockLabel === 'Not Set'
                        ? '<span class="pcc-muted-dash">—</span>'
                        : e((string) $vendorStockValue);
                    $liveStock = $row['owner_stock'] ?? $row['source_stock'] ?? null;
                    $liveStockDisplay = ($liveStock === null || $liveStock === '')
                        ? '<span class="pcc-muted-dash">—</span>'
                        : e((string) $liveStock);
                    $rateRaw = trim((string) ($row['average_cost'] ?? ''));
                    $rateDisplay = ($rateRaw === '' || $rateRaw === '—')
                        ? '<span class="pcc-muted-dash">—</span>'
                        : e($rateRaw);
                    $ibsCategory = trim((string) ($row['supplier_product_category'] ?? ''));
                    $ibsCategoryDisplay = $ibsCategory !== '' ? $ibsCategory : '—';
                    ?>
                    <tr class="product-catalog-row pc-table-row" data-product-id="<?= e((string) $productId) ?>" tabindex="0">
                        <td class="pcc-col-id"><span class="pcc-cell-ellipsis" title="<?= e($displayProductId) ?>"><?= e($displayProductId) ?></span></td>
                        <td class="pcc-col-model-no">
                            <div class="pcc-product-cell">
                                <div class="pcc-list-thumb pc-list-thumb">
                                    <?php if ($imageUrl !== ''): ?>
                                    <img src="<?= e($imageUrl) ?>" alt="" loading="lazy">
                                    <?php else: ?>
                                    <span class="pcc-list-thumb-empty">—</span>
                                    <?php endif; ?>
                                </div>
                                <div class="pcc-product-cell-text">
                                    <span class="pcc-product-cell-model" title="<?= e($modelTooltip) ?>"><?= e($modelNoLabel) ?></span>
                                    <?php if ($showModelSubId): ?>
                                    <span class="pcc-product-cell-subid"><?= e($displayProductId) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td class="pcc-col-ibs-category"><span class="pcc-cell-ellipsis" title="<?= e($ibsCategoryDisplay) ?>"><?= e($ibsCategoryDisplay) ?></span></td>
                        <td class="pcc-col-ibs-model"><?php if ($ibsModel !== ''): ?><span class="pcc-cell-ellipsis" title="<?= e($ibsModel) ?>"><?= e($ibsModel) ?></span><?php else: ?><span class="pcc-muted-dash">—</span><?php endif; ?></td>
                        <td class="pcc-col-type"><span class="badge pc-cell-badge <?= $isVariable ? 'badge-info' : 'badge-ok' ?>"><?= $isVariable ? 'Variable' : 'Simple' ?></span></td>
                        <td class="pcc-col-live-stock pcc-ro-stock-cell"><?= $liveStockDisplay ?></td>
                        <td class="pcc-col-ibs-stock pcc-vendor-stock-cell"><?= $ibsStockDisplay ?></td>
                        <td class="pcc-col-rate pcc-num"><?= $rateDisplay ?></td>
                        <td class="pcc-col-health"><span class="badge pc-cell-badge badge-<?= e($healthClass) ?>"><?= e($healthLabel) ?></span></td>
                        <td class="pcc-col-action">
                            <button type="button" class="btn btn-primary btn-sm pcc-open-btn" data-product-id="<?= e((string) $productId) ?>" data-pcc-open="details"><?= e($actionLabel) ?></button>
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
