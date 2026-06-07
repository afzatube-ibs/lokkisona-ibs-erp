<?php
$filters = $catalogFilters ?? [];
$chip = (string) ($filters['chip'] ?? 'all');
$type = (string) ($filters['type'] ?? 'all');
$sort = (string) ($filters['sort'] ?? 'product_id_asc');
$missingRateLabel = $missingRateLabel ?? 'Missing Cost';
$chips = [
    'all' => 'All',
    'ready' => 'Order Received',
    'missing_model' => 'Missing Model',
    'missing_cost' => $missingRateLabel,
    'low_stock' => 'Low Stock',
];
$preserveQuery = array_filter([
    'q' => $filters['q'] ?? '',
    'product_name' => $filters['product_name'] ?? '',
    'supplier_model' => $filters['supplier_model'] ?? '',
    'type' => $type !== 'all' ? $type : null,
    'sort' => $sort !== 'product_id_asc' ? $sort : null,
], static fn ($v) => $v !== '' && $v !== null && $v !== 'all');
?>
<div class="card mb-15 pcc-filter-card">
    <div class="card-header pcc-filter-card-header">
        <div>
            <h2 class="card-title">Search &amp; Filter</h2>
            <p class="page-description mb-0">Find products by name, model, type, readiness, stock or sort order</p>
        </div>
        <a href="<?= e(url('/product-control')) ?>" class="btn btn-secondary btn-sm">Clear</a>
    </div>
    <div class="card-body">
        <form method="get" action="<?= e(url('/product-control')) ?>" class="product-control-filters">
            <div class="pcc-filter-grid">
                <label class="pcc-field">Search product or origin model
                    <input type="search" name="q" class="form-input" value="<?= e($filters['q'] ?? '') ?>" placeholder="Product name, model, or ID">
                </label>
                <label class="pcc-field">Supplier product name
                    <input type="search" name="product_name" class="form-input" value="<?= e($filters['product_name'] ?? '') ?>">
                </label>
                <label class="pcc-field">Supplier model
                    <input type="search" name="supplier_model" class="form-input" value="<?= e($filters['supplier_model'] ?? '') ?>">
                </label>
                <label class="pcc-field">Type
                    <select name="type" class="form-input">
                        <option value="all"<?= $type === 'all' ? ' selected' : '' ?>>All types</option>
                        <option value="simple"<?= $type === 'simple' ? ' selected' : '' ?>>Simple</option>
                        <option value="variable"<?= $type === 'variable' ? ' selected' : '' ?>>Variable</option>
                    </select>
                </label>
                <label class="pcc-field">Sort
                    <select name="sort" class="form-input">
                        <option value="product_id_asc"<?= $sort === 'product_id_asc' ? ' selected' : '' ?>>Product ID</option>
                        <option value="product_id_desc"<?= $sort === 'product_id_desc' ? ' selected' : '' ?>>Product ID (desc)</option>
                        <option value="name_asc"<?= $sort === 'name_asc' ? ' selected' : '' ?>>Name A–Z</option>
                        <option value="model_asc"<?= $sort === 'model_asc' ? ' selected' : '' ?>>Model</option>
                        <option value="synced_desc"<?= $sort === 'synced_desc' ? ' selected' : '' ?>>Last synced</option>
                        <option value="health"<?= $sort === 'health' ? ' selected' : '' ?>>Health status</option>
                    </select>
                </label>
                <label class="pcc-field">Page size
                    <input type="text" class="form-input" value="20" readonly aria-readonly="true">
                </label>
            </div>
            <input type="hidden" name="chip" value="<?= e($chip) ?>">
            <div class="product-control-filter-actions">
                <button type="submit" class="btn btn-primary btn-sm">Apply filters</button>
            </div>
        </form>
        <div class="workflow-chip-row product-control-chip-row">
            <?php foreach ($chips as $chipKey => $chipLabel): ?>
            <?php
            $chipQuery = array_merge($preserveQuery, ['chip' => $chipKey]);
            $chipUrl = url('/product-control') . '?' . http_build_query($chipQuery);
            ?>
            <a href="<?= e($chipUrl) ?>" class="workflow-chip<?= $chip === $chipKey ? ' is-active' : '' ?>"><?= e($chipLabel) ?></a>
            <?php endforeach; ?>
        </div>
    </div>
</div>
