<?php
$costTerm = !empty($isSupplierView) ? 'Sale' : 'Cost';
$missingRateLabel = !empty($isSupplierView) ? 'Missing Rate' : 'Missing Cost';
$summary = $summaryKpis ?? ($productCatalog['summary_kpis'] ?? ($productCatalog['kpis'] ?? []));
$catalogRows = $productCatalog['rows'] ?? [];
$pagination = $catalogPagination ?? ($productCatalog['pagination'] ?? []);
$tableReady = !empty($tableReady);
$catalogTotal = (int) ($summary['total_products'] ?? 0);
$filters = $catalogFilters ?? [];
$lastSync = trim((string) ($lastCatalogSyncAt ?? ''));
$snapshotStale = !empty($snapshotIsStale);
$chipUrl = static function (string $chipKey) use ($filters): string {
    $query = array_filter([
        'q' => $filters['q'] ?? '',
        'supplier_model' => $filters['supplier_model'] ?? '',
        'category' => ($filters['category'] ?? '') !== '' ? ($filters['category'] ?? null) : null,
        'type' => ($filters['type'] ?? 'all') !== 'all' ? ($filters['type'] ?? null) : null,
        'sort' => ($filters['sort'] ?? 'product_id_asc') !== 'product_id_asc' ? ($filters['sort'] ?? null) : null,
        'per_page' => (int) ($filters['per_page'] ?? 20) !== 20 ? ($filters['per_page'] ?? null) : null,
        'chip' => $chipKey !== 'all' ? $chipKey : null,
    ], static fn ($v) => $v !== '' && $v !== null && $v !== 'all');

    return url('/product-control') . ($query !== [] ? '?' . http_build_query($query) : '');
};
?>
<div class="product-control-release">
<div class="page-header page-header-compact product-control-header pc-page-header">
    <div class="product-control-header-main">
        <div>
            <h1 class="page-title">Product Control</h1>
            <p class="ops-page-subtitle pc-page-subtitle">Local ERP product snapshot. Click <strong>Refresh Products</strong> only when you need latest Lokkisona catalog.</p>
            <p class="pc-snapshot-inline<?= $snapshotStale ? ' pc-snapshot-inline-stale' : '' ?>">
                Snapshot last refreshed: <?= $lastSync !== '' ? e($lastSync) : '—' ?>
                <?php if ($snapshotStale): ?>
                <span class="pc-snapshot-stale-note">· older than 24h</span>
                <?php endif; ?>
            </p>
        </div>
        <div class="product-control-header-actions pc-header-actions">
            <?php if (!empty($canViewHealth)): ?>
            <a href="<?= e(url('/health')) ?>" class="btn btn-secondary btn-sm">Catalog Health</a>
            <?php endif; ?>
            <?php if (!empty($canRefreshProducts)): ?>
            <form method="post" action="<?= e(url('/product-control/refresh-products')) ?>" class="pcc-header-sync-form">
                <?= $csrfField ?? '' ?>
                <input type="hidden" name="business_source_id" value="<?= e((string) ($defaultBusinessSourceId ?? 1)) ?>">
                <button type="submit" class="btn btn-primary btn-sm">Refresh Products</button>
            </form>
            <?php elseif (empty($isSupplierView)): ?>
            <button type="button" class="btn btn-primary btn-sm" disabled title="<?= !empty($productWriteGateReady) ? 'Product API route not configured' : 'Product refresh not ready' ?>">Refresh Products</button>
            <?php endif; ?>
            <?php if (!empty($canViewLogs)): ?>
            <a href="<?= e(url('/activity-log')) ?>" class="btn btn-secondary btn-sm">Sync Log</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php view('partials.flash-messages', ['flashSuccess' => $flashSuccess ?? null, 'flashError' => $flashError ?? null]); ?>

<div class="kpi-grid kpi-grid-inline product-control-kpis product-control-kpis-filter pc-kpi-grid">
    <a href="<?= e($chipUrl('ready')) ?>" class="kpi-card kpi-accent-success pc-kpi-card">
        <span class="kpi-label">Ready</span>
        <span class="kpi-value"><?= e((string) ($summary['ready'] ?? 0)) ?></span>
    </a>
    <a href="<?= e($chipUrl('needs_work')) ?>" class="kpi-card kpi-accent-warn pc-kpi-card">
        <span class="kpi-label">Needs Work</span>
        <span class="kpi-value"><?= e((string) ($summary['needs_work'] ?? 0)) ?></span>
    </a>
    <a href="<?= e($chipUrl('missing_cost')) ?>" class="kpi-card kpi-accent-warn pc-kpi-card">
        <span class="kpi-label"><?= e($missingRateLabel) ?></span>
        <span class="kpi-value"><?= e((string) ($summary['missing_cost'] ?? 0)) ?></span>
    </a>
    <a href="<?= e($chipUrl('missing_model')) ?>" class="kpi-card kpi-accent-warn pc-kpi-card">
        <span class="kpi-label">Missing Model</span>
        <span class="kpi-value"><?= e((string) ($summary['missing_model'] ?? 0)) ?></span>
    </a>
    <a href="<?= e($chipUrl('low_stock')) ?>" class="kpi-card kpi-accent-info pc-kpi-card">
        <span class="kpi-label">Low Stock</span>
        <span class="kpi-value"><?= e((string) ($summary['low_stock'] ?? 0)) ?></span>
    </a>
</div>

<?php view('partials.product-control-filters', [
    'catalogFilters' => $filters,
    'missingRateLabel' => $missingRateLabel,
    'categoryOptions' => $categoryOptions ?? [],
]); ?>

<?php view('partials.product-control-product-list', [
    'catalogRows' => $catalogRows,
    'catalogPagination' => $pagination,
    'catalogFilters' => $filters,
    'tableReady' => $tableReady,
    'productReadInventory' => $productReadInventory ?? [],
    'catalogTotal' => $catalogTotal,
    'isSupplierView' => !empty($isSupplierView),
    'canManage' => !empty($canManage),
]); ?>

<?php view('partials.product-control-center-modal', [
    'csrfField' => $csrfField ?? '',
    'isSupplierView' => !empty($isSupplierView),
    'boundSupplierId' => $boundSupplierId ?? 0,
    'defaultBusinessSourceId' => $defaultBusinessSourceId ?? 1,
    'canManage' => !empty($canManage),
    'writeGateProductEditReady' => !empty($writeGateProductEditReady),
    'supplierSelectOptions' => $supplierSelectOptions ?? [],
    'writeGateSupplierNoteReady' => !empty($writeGateSupplierNoteReady),
    'writeGateSupplierNote' => $writeGateSupplierNote ?? [],
    'categoryOptions' => $categoryOptions ?? [],
]); ?>

<script type="application/json" id="productCatalogBootstrap"><?= json_encode([
    'isSupplierView' => !empty($isSupplierView),
    'workspaceUrl' => url('/product-control/workspace'),
    'historyUrl' => url('/product-control/history'),
    'savedProductId' => (int) ($_GET['saved_product_id'] ?? 0),
    'categoryOptions' => $categoryOptions ?? [],
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?></script>
<script src="<?= e(asset('js/product-control.js')) ?>"></script>
</div>
