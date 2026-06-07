<?php
$trend_chart = $trend_chart ?? [];
$trendMax = max(1.0, (float) ($trendMax ?? 1));
$labels = $trend_chart['labels'] ?? [];
$series = [
    'orders' => ['label' => 'Orders', 'class' => 'si-trend-line-orders'],
    'dispatch' => ['label' => 'Dispatch', 'class' => 'si-trend-line-dispatch'],
    'payable_bdt' => ['label' => 'Payable BDT', 'class' => 'si-trend-line-payable'],
    'forecast' => ['label' => 'Forecast', 'class' => 'si-trend-line-forecast'],
];

$linePoints = static function (array $values, float $max, int $w, int $h): string {
    $values = array_map('floatval', $values);
    if ($values === []) {
        return '';
    }
    $count = count($values);
    $pts = [];
    foreach ($values as $i => $v) {
        $x = $count > 1 ? ($i / ($count - 1)) * $w : $w / 2;
        $y = $h - (($max > 0 ? ($v / $max) : 0) * ($h - 16)) - 8;
        $pts[] = round($x, 1) . ',' . round($y, 1);
    }

    return implode(' ', $pts);
};

$w = 400;
$h = 140;
?>
<section class="card si-trend">
    <div class="card-header">
        <h2 class="card-title">Trend Overview</h2>
    </div>
    <div class="card-body">
        <div class="si-trend-legend">
            <?php foreach ($series as $key => $meta): ?>
            <span class="si-trend-legend-item <?= e($meta['class']) ?>"><?= e($meta['label']) ?></span>
            <?php endforeach; ?>
        </div>
        <div class="si-trend-chart-wrap">
            <svg class="si-trend-chart" viewBox="0 0 <?= e((string) $w) ?> <?= e((string) $h) ?>" preserveAspectRatio="none" role="img" aria-label="Trend chart">
                <?php foreach ($series as $key => $meta): ?>
                <?php $pts = $linePoints($trend_chart[$key] ?? [], $trendMax, $w, $h); ?>
                <?php if ($pts !== ''): ?>
                <polyline class="<?= e($meta['class']) ?>" points="<?= e($pts) ?>" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <?php endif; ?>
                <?php endforeach; ?>
            </svg>
        </div>
        <?php if ($labels !== []): ?>
        <div class="si-trend-labels">
            <?php foreach ($labels as $label): ?>
            <span><?= e((string) $label) ?></span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>
