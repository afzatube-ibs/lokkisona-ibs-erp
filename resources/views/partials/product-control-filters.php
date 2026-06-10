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
$statusOptions = [
    'all' => 'All',
    'ready' => 'Order Received',
    'missing_model' => 'Missing Model',
    'missing_cost' => $missingRateLabel,
    'low_stock' => 'Low Stock',
];
?>
<div class="card pc-card pcc-filter-card pc-filter-compact-card">
    <div class="card-body pc-filter-compact-body">
        <form method="get" action="<?= e(url('/product-control')) ?>" class="product-control-filters pc-filter-compact-form">
            <div class="pc-filter-compact-row">
                <label class="pcc-field pc-field pc-field-search">Search
                    <input type="search" name="q" class="form-input form-input-compact" value="<?= e($filters['q'] ?? '') ?>" placeholder="Name, model, IBS category, or ID">
                </label>
                <label class="pcc-field pc-field">IBS Model
                    <input type="search" name="supplier_model" class="form-input form-input-compact" value="<?= e($filters['supplier_model'] ?? '') ?>" placeholder="Supplier vendor model">
                </label>
                <label class="pcc-field pc-field">IBS Category
                    <select name="category" class="form-input form-input-compact">
                        <option value=""<?= $category === '' ? ' selected' : '' ?>>All</option>
                        <?php foreach ($categoryOptions as $option): ?>
                        <?php $opt = (string) $option; ?>
                        <option value="<?= e($opt) ?>"<?= $category === $opt ? ' selected' : '' ?>><?= e($opt) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="pcc-field pc-field">Type
                    <select name="type" class="form-input form-input-compact">
                        <option value="all"<?= $type === 'all' ? ' selected' : '' ?>>All</option>
                        <option value="simple"<?= $type === 'simple' ? ' selected' : '' ?>>Simple</option>
                        <option value="variable"<?= $type === 'variable' ? ' selected' : '' ?>>Variable</option>
                    </select>
                </label>
                <label class="pcc-field pc-field">Sort
                    <select name="sort" class="form-input form-input-compact">
                        <option value="product_id_asc"<?= $sort === 'product_id_asc' ? ' selected' : '' ?>>Product ID</option>
                        <option value="product_id_desc"<?= $sort === 'product_id_desc' ? ' selected' : '' ?>>Product ID ↓</option>
                        <option value="name_asc"<?= $sort === 'name_asc' ? ' selected' : '' ?>>Name A–Z</option>
                        <option value="model_asc"<?= $sort === 'model_asc' ? ' selected' : '' ?>>Model</option>
                        <option value="category_asc"<?= $sort === 'category_asc' ? ' selected' : '' ?>>IBS Category</option>
                        <option value="synced_desc"<?= $sort === 'synced_desc' ? ' selected' : '' ?>>Last synced</option>
                        <option value="health"<?= $sort === 'health' ? ' selected' : '' ?>>Health</option>
                    </select>
                </label>
                <label class="pcc-field pc-field">Page Size
                    <select name="per_page" class="form-input form-input-compact">
                        <option value="20"<?= $perPage === 20 ? ' selected' : '' ?>>20</option>
                        <option value="50"<?= $perPage === 50 ? ' selected' : '' ?>>50</option>
                    </select>
                </label>
                <label class="pcc-field pc-field">Status Filter
                    <select name="chip" class="form-input form-input-compact">
                        <?php foreach ($statusOptions as $chipKey => $chipLabel): ?>
                        <option value="<?= e($chipKey) ?>"<?= $chip === $chipKey ? ' selected' : '' ?>><?= e($chipLabel) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <div class="pc-filter-compact-actions">
                    <button type="submit" class="btn btn-primary btn-sm">Apply</button>
                    <a href="<?= e(url('/product-control')) ?>" class="btn btn-secondary btn-sm">Clear</a>
                </div>
            </div>
        </form>
    </div>
</div>
