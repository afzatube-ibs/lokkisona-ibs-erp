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
        'product_name' => $filters['product_name'] ?? '',
        'supplier_model' => $filters['supplier_model'] ?? '',
        'type' => ($filters['type'] ?? 'all') !== 'all' ? ($filters['type'] ?? null) : null,
        'sort' => ($filters['sort'] ?? 'product_id_asc') !== 'product_id_asc' ? ($filters['sort'] ?? null) : null,
        'chip' => $chipKey,
    ], static fn ($v) => $v !== '' && $v !== null && $v !== 'all');

    return url('/product-control') . '?' . http_build_query($query);
};
?>
<div class="page-header page-header-compact product-control-header">
    <div class="product-control-header-main">
        <div>
            <h1 class="page-title">Product Control</h1>
            <p class="ops-page-subtitle">Local ERP product snapshot — loaded from the database only. Click <strong>Refresh Products</strong> when you need the latest Lokkisona warehouse catalog.</p>
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

<div class="card mb-15 pcc-snapshot-bar <?= $snapshotStale ? 'pcc-snapshot-bar-stale' : '' ?>">
    <div class="card-body pcc-snapshot-bar-body">
        <div>
            <strong>Snapshot last refreshed:</strong>
            <?= $lastSync !== '' ? e($lastSync) : '—' ?>
            <?php if (!empty($sourceSyncLabel)): ?>
            <span class="pcc-snapshot-source">· <?= e((string) $sourceSyncLabel) ?></span>
            <?php endif; ?>
        </div>
        <?php if ($snapshotStale): ?>
        <p class="page-description mb-0 pcc-snapshot-warn">Catalog snapshot is older than 24 hours. Refresh when needed.</p>
        <?php endif; ?>
    </div>
</div>

<div class="card mb-15 pcc-hero-card">
    <div class="card-body pcc-hero-card-body">
        <div class="pcc-hero-main">
            <h2 class="pcc-hero-title">Product Control</h2>
            <p class="page-description mb-0">Saved ERP catalog snapshot — the list is read-only; edits live inside Product Control Center when you click Manage. No automatic API refresh on page load.</p>
        </div>
        <div class="pcc-hero-pills">
            <span class="workflow-chip">Supplier fields preserved on re-sync</span>
            <span class="workflow-chip is-active">Local snapshot view</span>
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

<script type="application/json" id="productCatalogBootstrap"><?= json_encode([
    'isSupplierView' => !empty($isSupplierView),
    'workspaceUrl' => url('/product-control/workspace'),
    'historyUrl' => url('/product-control/history'),
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?></script>
<script src="<?= e(asset('js/product-control.js')) ?>"></script>
<?php if (!empty($timingDiagnostics)): ?>
<p class="page-description pcc-timing-diagnostics mb-15">Timing (local): <?php
    $parts = [];
    foreach ($timingDiagnostics as $label => $ms) {
        $parts[] = e((string) $label) . ' ' . e((string) $ms) . 'ms';
    }
    echo implode(' · ', $parts);
?></p>
<?php endif; ?>
