<?php
$costTerm = !empty($isSupplierView) ? 'Sale' : 'Cost';
$catalog = $productCatalog ?? ['kpis' => [], 'rows' => [], 'workspaces' => []];
$kpis = $catalog['kpis'] ?? [];
$catalogRows = $catalog['rows'] ?? [];
$pagination = $catalogPagination ?? ($catalog['pagination'] ?? []);
$tableReady = !empty($productReadInventory['table_exists']);
$totalFiltered = (int) ($pagination['total'] ?? count($catalogRows));
$currentPage = max(1, (int) ($pagination['page'] ?? 1));
?>
<div class="page-header page-header-compact product-control-header">
    <div class="product-control-header-main">
        <div>
            <h1 class="page-title">IBS-LK Product Control</h1>
            <p class="ops-page-subtitle"><?= !empty($isSupplierView)
                ? 'Synced supplier catalog from the live site — click any row to set vendor model, ' . strtolower($costTerm) . ', stock, and warnings in the Product Control Center.'
                : 'Read-only vendor inventory from live site sync — click any row to map model, cost, stock, and warnings. Catalog rows are not created manually here. v' . e($appVersion) . '.' ?></p>
        </div>
        <div class="product-control-header-actions">
            <?php if (empty($isSupplierView) && !empty($canManage)): ?>
            <a href="<?= e(url('/sync-preview')) ?>" class="btn btn-primary btn-sm">Sync Products</a>
            <?php elseif (empty($isSupplierView)): ?>
            <a href="<?= e(url('/sync-preview')) ?>" class="btn btn-secondary btn-sm">Sync Preview</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php view('partials.flash-messages', ['flashSuccess' => $flashSuccess ?? null, 'flashError' => $flashError ?? null]); ?>

<?php if (!empty($tableReady) && empty($writeGateSupplierNoteReady)): ?>
<?php view('partials.column-gate-note', [
    'columnGateReady' => false,
    'columnGateTitle' => 'Supplier note — migration 0012 not applied',
    'columnGateMessage' => $writeGateSupplierNote['message'] ?? 'Supplier note requires migration 0012_supplier_product_note.sql (manual apply only).',
    'columnGateMigrationFile' => '0012_supplier_product_note.sql',
    'columnGateDetails' => [
        'Supplier note fields are hidden in the Product Control Center modal.',
        'Variant supplier note column is hidden until 0012 is applied.',
        '"No option synced" badge requires sync_options_state column from the same migration.',
        'All other supplier fields (model, cost, stock, category, status) save normally.',
    ],
]); ?>
<?php endif; ?>

<?php if (empty($writeGateProductCreateReady)): ?>
<?php view('partials.write-gate-warning', ['writeGateReady' => false, 'writeGate' => $writeGateProductCreate ?? []]); ?>
<?php endif; ?>

<div class="card mb-15">
    <div class="card-header product-control-status-header">
        <div>
            <h2 class="card-title">Inventory Control Status</h2>
            <p class="page-description mb-0">Synced catalog from live site. New rows come from Sync Preview import — not manual add.</p>
        </div>
        <div class="product-control-status-pills">
            <span class="workflow-chip">Sync-only catalog</span>
            <span class="workflow-chip is-active">Supplier/ERP edits only</span>
        </div>
    </div>
    <div class="card-body">
        <div class="kpi-grid kpi-grid-inline product-control-kpis">
            <div class="kpi-card kpi-accent-primary">
                <span class="kpi-label">Total Products</span>
                <span class="kpi-value"><?= e((string) ($kpis['total_products'] ?? 0)) ?></span>
                <span class="kpi-sub">filtered catalog</span>
            </div>
            <div class="kpi-card kpi-accent-success">
                <span class="kpi-label">Ready</span>
                <span class="kpi-value"><?= e((string) ($kpis['ready'] ?? 0)) ?></span>
                <span class="kpi-sub">model + <?= strtolower($costTerm) ?> complete</span>
            </div>
            <div class="kpi-card kpi-accent-info">
                <span class="kpi-label">Variants</span>
                <span class="kpi-value"><?= e((string) ($kpis['variants'] ?? 0)) ?></span>
                <span class="kpi-sub">option lines</span>
            </div>
            <div class="kpi-card kpi-accent-warn">
                <span class="kpi-label">Needs Work</span>
                <span class="kpi-value"><?= e((string) ($kpis['needs_work'] ?? 0)) ?></span>
                <span class="kpi-sub">missing model/<?= strtolower($costTerm) ?></span>
            </div>
        </div>
    </div>
</div>

<?php view('partials.product-control-filters', ['catalogFilters' => $catalogFilters ?? []]); ?>

<div class="card mb-15">
    <div class="card-header product-control-table-header">
        <h2 class="card-title">Inventory Products</h2>
        <p class="page-description mb-0">Page <?= e((string) $currentPage) ?> · <?= e((string) count($catalogRows)) ?> of <?= e((string) $totalFiltered) ?> filtered · 20 rows per page</p>
    </div>
    <div class="card-body">
        <?php if (!$tableReady): ?>
        <p class="page-description"><?= e($productReadInventory['status_message'] ?? 'Product table unavailable.') ?></p>
        <?php elseif ($totalFiltered === 0): ?>
        <p class="page-description">No products match the current filters.<?php if (empty($productReadInventory['rows'])): ?><?php if (empty($isSupplierView)): ?> Owner: open <a href="<?= e(url('/sync-preview')) ?>">Sync Preview</a> and import warehouse products.<?php else: ?> Ask the owner to run product import from Sync Preview.<?php endif; ?><?php endif; ?></p>
        <?php else: ?>
        <?php
        $pageQuery = array_filter([
            'q' => $catalogFilters['q'] ?? '',
            'product_id' => $catalogFilters['product_id'] ?? '',
            'product_name' => $catalogFilters['product_name'] ?? '',
            'model' => $catalogFilters['model'] ?? '',
            'supplier_model' => $catalogFilters['supplier_model'] ?? '',
            'chip' => $catalogFilters['chip'] ?? 'all',
        ], static fn ($value) => $value !== '' && $value !== null && $value !== 'all');
        view('partials.sync-pagination', [
            'page' => $currentPage,
            'pageParam' => 'page',
            'baseUrl' => url('/product-control'),
            'otherPageQuery' => $pageQuery,
            'pagination' => $pagination,
        ]);
        ?>
        <div class="table-scroll">
            <table class="data-table product-catalog-table product-catalog-table-compact">
                <thead>
                    <tr>
                        <th>Thumb</th>
                        <th>ID</th>
                        <th>Product Name</th>
                        <th>Type</th>
                        <th>OC ID</th>
                        <th>Vendor Model</th>
                        <th>Vendor Stock</th>
                        <th>Last Synced</th>
                        <th>Health</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="productCatalogTableBody">
                    <?php foreach ($catalogRows as $row): ?>
                    <tr class="product-catalog-row"
                        data-product-row
                        data-product-id="<?= e((string) $row['product_id']) ?>"
                        tabindex="0"
                        role="button"
                        aria-label="Open product #<?= e((string) $row['product_id']) ?>">
                        <td>
                            <div class="pcc-list-thumb">
                                <?php if (!empty($row['image_path'])): ?>
                                <img src="<?= e($row['image_path']) ?>" alt="">
                                <?php else: ?>
                                <span class="pcc-list-thumb-empty">—</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td><code>#<?= e((string) $row['product_id']) ?></code></td>
                        <td><strong><?= e($row['product_name'] !== '' ? $row['product_name'] : '—') ?></strong></td>
                        <td>
                            <span class="badge <?= ($row['type'] ?? '') === 'variable' ? 'badge-info' : 'badge-ok' ?>">
                                <?= ($row['type'] ?? '') === 'variable' ? 'Variable' : 'Simple' ?>
                            </span>
                        </td>
                        <td><code><?= e($row['source_product_id'] !== '' ? $row['source_product_id'] : '—') ?></code></td>
                        <td><?= e($row['supplier_model'] !== '' ? $row['supplier_model'] : '—') ?></td>
                        <td><strong><?= e((string) ($row['vendor_stock'] ?? 0)) ?></strong></td>
                        <td><?= e((string) ($row['last_synced_at'] ?? '—')) ?></td>
                        <td>
                            <div class="pcc-badge-row">
                                <?php foreach (($row['badges'] ?? []) as $badge): ?>
                                <span class="badge badge-<?= e($badge['class'] ?? 'muted') ?>"><?= e($badge['label'] ?? '') ?></span>
                                <?php endforeach; ?>
                            </div>
                        </td>
                        <td>
                            <button type="button" class="btn btn-secondary btn-sm" data-open-product-workspace="<?= e((string) $row['product_id']) ?>">Open</button>
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
            'otherPageQuery' => $pageQuery,
            'pagination' => $pagination,
        ]);
        ?>
        <p class="page-description mt-1">Click any row to edit supplier fields. OpenCart name, model, stock, and image stay read-only.</p>
        <?php endif; ?>
    </div>
</div>

<script type="application/json" id="productCatalogPayload"><?= e(json_encode($catalog['workspaces'] ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)) ?></script>
<script type="application/json" id="productHistoryPayload"><?= e(json_encode($productHistoryByProduct ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)) ?></script>
<script type="application/json" id="productControlConfig"><?= e(json_encode(['supplierNoteReady' => !empty($writeGateSupplierNoteReady)], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)) ?></script>

<?php view('partials.product-control-center-modal', [
    'csrfField' => $csrfField ?? '',
    'isSupplierView' => !empty($isSupplierView),
    'boundSupplierId' => $boundSupplierId ?? 0,
    'defaultBusinessSourceId' => $defaultBusinessSourceId ?? 1,
    'canManage' => !empty($canManage),
    'writeGateProductEditReady' => !empty($writeGateProductEditReady),
    'supplierSelectOptions' => $supplierSelectOptions ?? [],
    'writeGateSupplierNote' => $writeGateSupplierNote ?? [],
    'writeGateSupplierNoteReady' => !empty($writeGateSupplierNoteReady),
]); ?>

<script src="<?= e(asset('js/product-control.js')) ?>"></script>

<?php if (empty($isSupplierView)): ?>
<details class="planning-collapsible">
    <summary class="planning-collapsible-summary">Read-Only Product Inventory (developer reference)</summary>
    <div class="planning-collapsible-body">
        <p class="page-description mb-1">Live Read Inventory (SELECT only). No sync, no migration apply from this page.</p>
        <?php view('partials.read-inventory-card', ['readInventory' => $productReadInventory, 'cardTitle' => 'Products']); ?>
        <?php view('partials.read-inventory-card', ['readInventory' => $productVariantReadInventory, 'cardTitle' => 'Product Variants (raw read inventory)']); ?>
        <?php view('partials.read-inventory-card', ['readInventory' => $productCostHistoryReadInventory, 'cardTitle' => 'Product Cost History (raw read inventory)']); ?>
        <?php view('partials.read-inventory-card', ['readInventory' => $productStockHistoryReadInventory, 'cardTitle' => 'Product Stock History (raw read inventory)']); ?>
    </div>
</details>

<details class="planning-collapsible">
    <summary class="planning-collapsible-summary">Planning Foundation (reference)</summary>
    <div class="planning-collapsible-body">

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Current Supplier Context</h2>
        </div>
        <div class="card-body">
            <dl class="info-list">
                <div class="info-row">
                    <dt>Supplier</dt>
                    <dd><?= e($currentSupplier['name']) ?></dd>
                </div>
                <div class="info-row">
                    <dt>Context</dt>
                    <dd><?= e($currentSupplier['role']) ?></dd>
                </div>
            </dl>
            <p class="page-description"><?= e($currentSupplier['summary']) ?></p>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Product Control Purpose</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($purpose as $item): ?>
                    <li><?= e($item) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<div class="card-grid">
    <?php foreach ($futureSyncedStructure as $section): ?>
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><?= e($section['title']) ?></h2>
        </div>
        <div class="card-body">
            <p><?= e($section['description']) ?></p>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Supplier Editable Fields</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($editableFields as $field): ?>
                    <li><?= e($field) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Read-Only Platform Fields</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($readOnlyFields as $field): ?>
                    <li><?= e($field) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Business Rules</h2>
        </div>
        <div class="card-body">
            <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Field</th>
                        <th>Rule</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($businessRules as $rule): ?>
                    <tr>
                        <td><?= e($rule['field']) ?></td>
                        <td><?= e($rule['rule']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Cost / Stock History Rules</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($historyRules as $rule): ?>
                    <li><?= e($rule) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Low Stock Warning Rules</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($lowStockRules as $rule): ?>
                    <li><?= e($rule) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Option / Image Reference Rules</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($optionImageRules as $rule): ?>
                    <li><?= e($rule) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><?= e($costSnapshotRule['title']) ?></h2>
        </div>
        <div class="card-body">
            <p><?= e($costSnapshotRule['summary']) ?></p>
            <ul class="feature-list">
                <?php foreach ($costSnapshotRule['points'] as $point): ?>
                    <li><?= e($point) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><?= e($sharedStockRule['title']) ?></h2>
        </div>
        <div class="card-body">
            <p><?= e($sharedStockRule['summary']) ?></p>
            <ul class="feature-list">
                <?php foreach ($sharedStockRule['points'] as $point): ?>
                    <li><?= e($point) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Planned Product Fields</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($plannedProductFields as $field): ?>
                    <li><?= e($field) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Planned Variant / Option Fields</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($plannedVariantFields as $field): ?>
                    <li><?= e($field) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

    </div>
</details>
<?php endif; ?>
