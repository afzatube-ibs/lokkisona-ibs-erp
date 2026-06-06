<?php
$costTerm = !empty($isSupplierView) ? 'Sale' : 'Cost';
$avgCostLabel = !empty($isSupplierView) ? 'Average Sale' : 'Average Cost';
$catalog = $productCatalog ?? ['kpis' => [], 'rows' => [], 'workspaces' => []];
$kpis = $catalog['kpis'] ?? [];
$catalogRows = $catalog['rows'] ?? [];
$tableReady = !empty($productReadInventory['table_exists']);
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

<?php if (empty($writeGateProductCreateReady)): ?>
<?php view('partials.write-gate-warning', ['writeGateReady' => false, 'writeGate' => $writeGateProductCreate ?? []]); ?>
<?php endif; ?>

<div class="card mb-15">
    <div class="card-header product-control-status-header">
        <div>
            <h2 class="card-title">Inventory Control Status</h2>
            <p class="page-description mb-0">Synced catalog from live site. New rows come from <strong>Pull warehouse products</strong> on Sync Preview — not manual add.</p>
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
                <span class="kpi-sub">warehouse catalog</span>
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

<div class="card mb-15">
    <div class="card-header"><h2 class="card-title">Search &amp; Filter</h2></div>
    <div class="card-body">
        <div class="product-control-filters">
            <input type="search" id="productCatalogSearch" class="form-input" placeholder="Search product, model, OC ID, or category">
            <div class="workflow-chip-row">
                <button type="button" class="workflow-chip is-active" data-catalog-filter="all">All</button>
                <button type="button" class="workflow-chip" data-catalog-filter="variable">Variable</button>
                <button type="button" class="workflow-chip" data-catalog-filter="simple">Simple</button>
                <button type="button" class="workflow-chip" data-catalog-filter="needs_work">Needs Work</button>
                <button type="button" class="workflow-chip" data-catalog-filter="low_stock">Low Stock</button>
            </div>
        </div>
    </div>
</div>

<div class="card mb-15">
    <div class="card-header product-control-table-header">
        <h2 class="card-title">Inventory Products</h2>
        <p class="page-description mb-0">Showing <strong id="productCatalogVisibleCount"><?= e((string) count($catalogRows)) ?></strong> of <?= e((string) count($catalogRows)) ?> records</p>
    </div>
    <div class="card-body">
        <?php if (!$tableReady): ?>
        <p class="page-description"><?= e($productReadInventory['status_message'] ?? 'Product table unavailable.') ?></p>
        <?php elseif ($catalogRows === []): ?>
        <p class="page-description">No synced products yet.<?php if (empty($isSupplierView)): ?> Owner: open <a href="<?= e(url('/sync-preview')) ?>">Sync Preview</a> and run <strong>Pull warehouse products</strong> (live site, From Warehouse = Yes only).<?php else: ?> Ask the owner to run warehouse product pull from the live site.<?php endif; ?></p>
        <?php if (empty($isSupplierView) && empty($warehouseProductPullAvailable)): ?>
        <p class="page-description">Set <code>product_api_route</code> in <code>config/opencart.php</code> to your live OpenCart product API route before pulling.</p>
        <?php endif; ?>
        <?php else: ?>
        <div class="table-scroll">
            <table class="data-table product-catalog-table">
                <thead>
                    <tr>
                        <th>Product ID</th>
                        <th>Image</th>
                        <th>Variable</th>
                        <th>Model</th>
                        <th>Vendor Model</th>
                        <th><?= e($avgCostLabel) ?></th>
                        <th>Owner Stock</th>
                        <th>Vendor Stock</th>
                        <th>Low Warning</th>
                        <th>Health Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="productCatalogTableBody">
                    <?php foreach ($catalogRows as $row): ?>
                    <tr class="product-catalog-row"
                        data-product-row
                        data-product-id="<?= e((string) $row['product_id']) ?>"
                        data-search="<?= e($row['search_blob'] ?? '') ?>"
                        data-health="<?= e($row['health_class'] ?? 'warn') ?>"
                        data-type="<?= e($row['type'] ?? 'simple') ?>"
                        data-low="<?= !empty($row['low_warning']) ? '1' : '0' ?>"
                        tabindex="0"
                        role="button"
                        aria-label="Open product #<?= e((string) $row['product_id']) ?>">
                        <td><code>#<?= e((string) $row['product_id']) ?></code></td>
                        <td>
                            <div class="pcc-list-thumb">
                                <?php if (!empty($row['image_path'])): ?>
                                <img src="<?= e($row['image_path']) ?>" alt="">
                                <?php else: ?>
                                <span class="pcc-list-thumb-empty">—</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <span class="badge <?= ($row['type'] ?? '') === 'variable' ? 'badge-info' : 'badge-ok' ?>">
                                <?= ($row['type'] ?? '') === 'variable' ? 'Variable' : 'Simple' ?>
                            </span>
                        </td>
                        <td><code><?= e($row['source_model'] !== '' ? $row['source_model'] : '—') ?></code></td>
                        <td><?= e($row['supplier_model'] !== '' ? $row['supplier_model'] : '—') ?></td>
                        <td><?= e((string) ($row['average_cost'] ?? '—')) ?></td>
                        <td><?= e((string) ($row['owner_stock'] ?? 0)) ?></td>
                        <td><strong><?= e((string) ($row['vendor_stock'] ?? 0)) ?></strong></td>
                        <td><?= !empty($row['low_warning']) ? '<span class="badge badge-warn">Low</span>' : '—' ?></td>
                        <td><span class="badge <?= ($row['health_class'] ?? '') === 'ok' ? 'badge-ok' : 'badge-warn' ?>"><?= e($row['health_label'] ?? '—') ?></span></td>
                        <td>
                            <button type="button" class="btn btn-secondary btn-sm" data-open-product-workspace="<?= e((string) $row['product_id']) ?>">Open</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="page-description mt-1">Click any row to add supplier vendor model, <?= strtolower($costTerm) ?>, and stock. OpenCart name, model, and platform stock stay read-only.</p>
        <?php endif; ?>
    </div>
</div>

<script type="application/json" id="productCatalogPayload"><?= e(json_encode($catalog['workspaces'] ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)) ?></script>
<script type="application/json" id="productHistoryPayload"><?= e(json_encode($productHistoryByProduct ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)) ?></script>

<?php view('partials.product-control-center-modal', [
    'csrfField' => $csrfField ?? '',
    'isSupplierView' => !empty($isSupplierView),
    'boundSupplierId' => $boundSupplierId ?? 0,
    'defaultBusinessSourceId' => $defaultBusinessSourceId ?? 1,
    'canManage' => !empty($canManage),
    'writeGateProductEditReady' => !empty($writeGateProductEditReady),
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
