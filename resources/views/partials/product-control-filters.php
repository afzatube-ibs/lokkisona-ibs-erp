<?php
$filters = $catalogFilters ?? [];
$chip = (string) ($filters['chip'] ?? 'all');
$chips = [
    'all' => 'All',
    'variable' => 'Variable',
    'simple' => 'Simple',
    'needs_work' => 'Needs Work',
    'low_stock' => 'Low Stock',
    'missing_cost' => 'Missing Cost',
    'missing_model' => 'Missing Model',
    'sync_required' => 'Sync Required',
];
$queryWithoutChip = array_filter([
    'q' => $filters['q'] ?? '',
    'product_id' => $filters['product_id'] ?? '',
    'product_name' => $filters['product_name'] ?? '',
    'model' => $filters['model'] ?? '',
    'supplier_model' => $filters['supplier_model'] ?? '',
], static fn ($value) => $value !== '' && $value !== null);
?>
<div class="card mb-15">
    <div class="card-header"><h2 class="card-title">Search &amp; Filter</h2></div>
    <div class="card-body">
        <form method="get" action="<?= e(url('/product-control')) ?>" class="product-control-filter-form">
            <div class="product-control-filter-grid">
                <label class="pcc-field">Search
                    <input type="search" name="q" class="form-input" value="<?= e($filters['q'] ?? '') ?>" placeholder="Name, model, OC ID, ERP ID">
                </label>
                <label class="pcc-field">Product ID
                    <input type="text" name="product_id" class="form-input" value="<?= e($filters['product_id'] ?? '') ?>" placeholder="ERP #">
                </label>
                <label class="pcc-field">Product Name
                    <input type="text" name="product_name" class="form-input" value="<?= e($filters['product_name'] ?? '') ?>">
                </label>
                <label class="pcc-field">OpenCart Model
                    <input type="text" name="model" class="form-input" value="<?= e($filters['model'] ?? '') ?>">
                </label>
                <label class="pcc-field">Supplier Model
                    <input type="text" name="supplier_model" class="form-input" value="<?= e($filters['supplier_model'] ?? '') ?>">
                </label>
            </div>
            <input type="hidden" name="chip" value="<?= e($chip) ?>">
            <div class="product-control-filter-actions">
                <button type="submit" class="btn btn-primary btn-sm">Apply filters</button>
                <a href="<?= e(url('/product-control')) ?>" class="btn btn-secondary btn-sm">Reset</a>
            </div>
        </form>
        <div class="workflow-chip-row product-control-chip-row">
            <?php foreach ($chips as $chipKey => $chipLabel): ?>
            <?php
            $chipQuery = array_merge($queryWithoutChip, ['chip' => $chipKey]);
            $chipUrl = url('/product-control') . '?' . http_build_query($chipQuery);
            ?>
            <a href="<?= e($chipUrl) ?>" class="workflow-chip<?= $chip === $chipKey ? ' is-active' : '' ?>"><?= e($chipLabel) ?></a>
            <?php endforeach; ?>
        </div>
    </div>
</div>
