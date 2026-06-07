<?php
$costTerm = !empty($isSupplierView) ? 'Sale' : 'Cost';
$missingRateLabel = !empty($isSupplierView) ? 'Missing Rate' : 'Missing Cost';
$summary = $summaryKpis ?? ($productCatalog['summary_kpis'] ?? ($productCatalog['kpis'] ?? []));
$catalogRows = $productCatalog['rows'] ?? [];
$pagination = $catalogPagination ?? ($productCatalog['pagination'] ?? []);
$tableReady = !empty($tableReady);
$catalogTotal = (int) ($summary['total_products'] ?? 0);
$filters = $catalogFilters ?? [];
$chipUrl = static function (string $chipKey) use ($filters): string {
    $query = array_filter([
        'q' => $filters['q'] ?? '',
        'product_name' => $filters['product_name'] ?? '',
        'supplier_model' => $filters['supplier_model'] ?? '',
        'type' => ($filters['type'] ?? 'all') !== 'all' ? ($filters['type'] ?? null) : null,
        'sort' => ($filters['sort'] ?? 'product_id_asc') !== 'product_id_asc' ? ($filters['sort'] ?? null) : null,
        'chip' => $chipKey,
    ], static fn ($v) => $v !== '' && $v !== null && $v !== 'all');

    return url('/product-control') . '?' . http_build_query($query);
};
$catalogPayload = json_encode([
    'workspaces' => $catalogWorkspaces ?? ($productCatalog['workspaces'] ?? []),
    'historyByProduct' => $productHistoryByProduct ?? [],
    'isSupplierView' => !empty($isSupplierView),
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
?>
<div class="page-header page-header-compact product-control-header">
    <div class="product-control-header-main">
        <div>
            <h1 class="page-title">Product Control</h1>
            <p class="ops-page-subtitle">Read-only vendor inventory monitor. Products refresh automatically from Dispatch Location (from_warehouse = 1). Click any row to manage model, <?= strtolower($costTerm) ?>, stock, warnings and history in Product Control Center.</p>
        </div>
        <div class="product-control-header-actions">
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

<div class="card mb-15 pcc-hero-card">
    <div class="card-body pcc-hero-card-body">
        <div class="pcc-hero-main">
            <h2 class="pcc-hero-title">Product Control</h2>
            <p class="page-description mb-0">Clean vendor inventory monitor — the list stays read-only; all edits, stock changes, price changes and history live inside the product popup. Use <strong>Refresh Products</strong> to pull the latest Dispatch Location catalog.</p>
        </div>
        <div class="pcc-hero-pills">
            <span class="workflow-chip">Safe mapping edits only</span>
            <span class="workflow-chip is-active">Live inventory view</span>
        </div>
    </div>
</div>

<div class="kpi-grid kpi-grid-inline product-control-kpis product-control-kpis-filter mb-15">
    <a href="<?= e($chipUrl('ready')) ?>" class="kpi-card kpi-accent-success">
        <span class="kpi-label">Ready</span>
        <span class="kpi-value"><?= e((string) ($summary['ready'] ?? 0)) ?></span>
        <span class="kpi-sub">model + rate complete</span>
    </a>
    <a href="<?= e($chipUrl('needs_work')) ?>" class="kpi-card kpi-accent-warn">
        <span class="kpi-label">Needs Work</span>
        <span class="kpi-value"><?= e((string) ($summary['needs_work'] ?? 0)) ?></span>
        <span class="kpi-sub">missing model or rate</span>
    </a>
    <a href="<?= e($chipUrl('missing_cost')) ?>" class="kpi-card kpi-accent-warn">
        <span class="kpi-label"><?= e($missingRateLabel) ?></span>
        <span class="kpi-value"><?= e((string) ($summary['missing_cost'] ?? 0)) ?></span>
        <span class="kpi-sub">click to filter</span>
    </a>
    <a href="<?= e($chipUrl('missing_model')) ?>" class="kpi-card kpi-accent-warn">
        <span class="kpi-label">Missing Model</span>
        <span class="kpi-value"><?= e((string) ($summary['missing_model'] ?? 0)) ?></span>
        <span class="kpi-sub">click to filter</span>
    </a>
    <a href="<?= e($chipUrl('low_stock')) ?>" class="kpi-card kpi-accent-info">
        <span class="kpi-label">Low Stock</span>
        <span class="kpi-value"><?= e((string) ($summary['low_stock'] ?? 0)) ?></span>
        <span class="kpi-sub">at or below warning</span>
    </a>
</div>

<?php view('partials.product-control-filters', [
    'catalogFilters' => $filters,
    'missingRateLabel' => $missingRateLabel,
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
]); ?>

<script type="application/json" id="productCatalogPayload"><?= $catalogPayload ?></script>
<script src="<?= e(asset('js/product-control.js')) ?>"></script>
