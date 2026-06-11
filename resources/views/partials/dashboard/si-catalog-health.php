<?php
$catalog_health = $catalog_health ?? [];
$bars = [
    ['label' => 'Healthy stock', 'value' => (int) ($catalog_health['healthy_stock'] ?? 0), 'max' => max(1, (int) ($catalog_health['total_products'] ?? 1))],
    ['label' => 'Low stock', 'value' => (int) ($catalog_health['low_stock'] ?? 0), 'max' => max(1, (int) ($catalog_health['total_products'] ?? 1))],
    ['label' => 'Out of stock', 'value' => (int) ($catalog_health['out_of_stock'] ?? 0), 'max' => max(1, (int) ($catalog_health['total_products'] ?? 1))],
    ['label' => 'Reorder soon', 'value' => (int) ($catalog_health['reorder_soon'] ?? 0), 'max' => max(1, (int) ($catalog_health['total_products'] ?? 1))],
    ['label' => 'Missing cost', 'value' => (int) ($catalog_health['missing_cost'] ?? 0), 'max' => max(1, (int) ($catalog_health['total_products'] ?? 1))],
    ['label' => 'Missing model', 'value' => (int) ($catalog_health['missing_model'] ?? 0), 'max' => max(1, (int) ($catalog_health['total_products'] ?? 1))],
];
?>
<section class="card si-catalog-health">
    <div class="card-header card-header-flex">
        <h2 class="card-title">Catalog Health</h2>
        <a href="<?= e(url('/product-control')) ?>" class="btn btn-sm btn-ghost">Products</a>
    </div>
    <div class="card-body">
        <p class="si-catalog-summary">
            <?= e((string) ($catalog_health['total_products'] ?? 0)) ?> products ·
            Vendor stock value <strong><?= e(number_format((float) ($catalog_health['vendor_stock_value'] ?? 0), 0)) ?> BDT</strong>
        </p>
        <div class="si-performance-bars">
            <?php foreach ($bars as $bar): ?>
            <?php $pct = round(($bar['value'] / $bar['max']) * 100, 1); ?>
            <div class="si-performance-bar-row">
                <div class="si-performance-bar-head">
                    <span><?= e($bar['label']) ?></span>
                    <span><?= e((string) $bar['value']) ?></span>
                </div>
                <div class="si-progress-track">
                    <div class="si-progress-fill" style="width: <?= e((string) min(100, $pct)) ?>%;"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
