<?php
$filters = $catalogFilters ?? [];
$chip = (string) ($filters['chip'] ?? 'all');
$type = (string) ($filters['type'] ?? 'all');
$sort = (string) ($filters['sort'] ?? 'product_id_asc');
$category = (string) ($filters['category'] ?? '');
$perPage = (int) ($filters['per_page'] ?? 20);
if (!in_array($perPage, [20, 50], true)) {
    $perPage = 20;
}
$missingRateLabel = $missingRateLabel ?? 'Missing Cost';
$categoryOptions = $categoryOptions ?? [];
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
    'category' => $category !== '' ? $category : null,
    'type' => $type !== 'all' ? $type : null,
    'sort' => $sort !== 'product_id_asc' ? $sort : null,
    'per_page' => $perPage !== 20 ? (string) $perPage : null,
], static fn ($v) => $v !== '' && $v !== null && $v !== 'all');
?>
<div class="card pc-card pcc-filter-card">
    <div class="card-header pcc-filter-card-header">
        <h2 class="card-title">Search &amp; Filter</h2>
    </div>
    <div class="card-body">
        <form method="get" action="<?= e(url('/product-control')) ?>" class="product-control-filters">
            <div class="pcc-filter-grid pc-filter-grid">
                <label class="pcc-field pc-field">Search product or origin model
                    <input type="search" name="q" class="form-input" value="<?= e($filters['q'] ?? '') ?>" placeholder="Name, model, category, or ID">
                </label>
                <label class="pcc-field pc-field">Supplier product name
                    <input type="search" name="product_name" class="form-input" value="<?= e($filters['product_name'] ?? '') ?>" placeholder="Supplier name">
                </label>
                <label class="pcc-field pc-field">Supplier model
                    <input type="search" name="supplier_model" class="form-input" value="<?= e($filters['supplier_model'] ?? '') ?>" placeholder="Vendor model">
                </label>
                <label class="pcc-field pc-field">Category
                    <select name="category" class="form-input">
                        <option value=""<?= $category === '' ? ' selected' : '' ?>>All categories</option>
                        <?php foreach ($categoryOptions as $option): ?>
                        <?php $opt = (string) $option; ?>
                        <option value="<?= e($opt) ?>"<?= $category === $opt ? ' selected' : '' ?>><?= e($opt) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="pcc-field pc-field">Type
                    <select name="type" class="form-input">
                        <option value="all"<?= $type === 'all' ? ' selected' : '' ?>>All types</option>
                        <option value="simple"<?= $type === 'simple' ? ' selected' : '' ?>>Simple</option>
                        <option value="variable"<?= $type === 'variable' ? ' selected' : '' ?>>Variable</option>
                    </select>
                </label>
                <label class="pcc-field pc-field">Sort
                    <select name="sort" class="form-input">
                        <option value="product_id_asc"<?= $sort === 'product_id_asc' ? ' selected' : '' ?>>Product ID</option>
                        <option value="product_id_desc"<?= $sort === 'product_id_desc' ? ' selected' : '' ?>>Product ID (desc)</option>
                        <option value="name_asc"<?= $sort === 'name_asc' ? ' selected' : '' ?>>Name A–Z</option>
                        <option value="model_asc"<?= $sort === 'model_asc' ? ' selected' : '' ?>>Model</option>
                        <option value="synced_desc"<?= $sort === 'synced_desc' ? ' selected' : '' ?>>Last synced</option>
                        <option value="health"<?= $sort === 'health' ? ' selected' : '' ?>>Health status</option>
                    </select>
                </label>
                <label class="pcc-field pc-field">Page size
                    <select name="per_page" class="form-input">
                        <option value="20"<?= $perPage === 20 ? ' selected' : '' ?>>20</option>
                        <option value="50"<?= $perPage === 50 ? ' selected' : '' ?>>50</option>
                    </select>
                </label>
            </div>
            <input type="hidden" name="chip" value="<?= e($chip) ?>">
            <div class="product-control-filter-actions pc-filter-actions">
                <button type="submit" class="btn btn-primary btn-sm">Apply Filters</button>
                <a href="<?= e(url('/product-control')) ?>" class="btn btn-secondary btn-sm">Clear</a>
            </div>
        </form>
        <div class="workflow-chip-row product-control-chip-row pc-chip-row">
            <?php foreach ($chips as $chipKey => $chipLabel): ?>
            <?php
            $chipQuery = array_merge($preserveQuery, ['chip' => $chipKey !== 'all' ? $chipKey : null]);
            $chipQuery = array_filter($chipQuery, static fn ($v) => $v !== '' && $v !== null && $v !== 'all');
            $chipUrl = url('/product-control') . ($chipQuery !== [] ? '?' . http_build_query($chipQuery) : '');
            ?>
            <a href="<?= e($chipUrl) ?>" class="workflow-chip<?= $chip === $chipKey ? ' is-active' : '' ?>"><?= e($chipLabel) ?></a>
            <?php endforeach; ?>
        </div>
    </div>
</div>
