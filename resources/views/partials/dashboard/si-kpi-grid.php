<?php
$kpis = $kpis ?? [];
$siSparkPolyline = $siSparkPolyline ?? null;
$iconMap = [
    'orders' => 'O',
    'dispatch' => 'D',
    'delivered' => '✓',
    'payable' => '৳',
    'stock' => 'S',
    'low' => '!',
    'return' => '↩',
    'score' => '★',
];
?>
<section class="si-kpi-grid">
    <?php foreach ($kpis as $kpi): ?>
    <?php
        $icon = $iconMap[(string) ($kpi['icon'] ?? '')] ?? '•';
        $value = $kpi['value'] ?? 0;
        $display = !empty($kpi['is_currency'])
            ? number_format((float) $value, 0) . ' BDT'
            : number_format((float) $value, (strpos((string) ($kpi['suffix'] ?? ''), '%') !== false ? 1 : 0)) . e((string) ($kpi['suffix'] ?? ''));
        $spark = $kpi['spark'] ?? [];
        $sparkPts = is_callable($siSparkPolyline) ? $siSparkPolyline($spark) : '';
        $trendUp = $kpi['trend_up'] ?? null;
    ?>
    <article class="si-kpi-card card si-kpi-tone-<?= e((string) ($kpi['tone'] ?? 'primary')) ?>">
        <div class="si-kpi-top">
            <span class="si-kpi-icon"><?= e($icon) ?></span>
            <span class="si-kpi-label"><?= e((string) ($kpi['label'] ?? '')) ?></span>
        </div>
        <div class="si-kpi-value"><?= e($display) ?></div>
        <?php if ($kpi['trend'] !== null): ?>
        <div class="si-kpi-trend si-trend-<?= $trendUp ? 'up' : 'down' ?>">
            <?= $trendUp ? '↑' : '↓' ?> <?= e((string) ($kpi['trend_label'] ?? '')) ?>
        </div>
        <?php else: ?>
        <div class="si-kpi-trend si-trend-muted"><?= e((string) ($kpi['trend_label'] ?? '—')) ?></div>
        <?php endif; ?>
        <?php if ($sparkPts !== ''): ?>
        <svg class="si-sparkline" viewBox="0 0 120 32" preserveAspectRatio="none" aria-hidden="true">
            <polyline points="<?= e($sparkPts) ?>" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <?php endif; ?>
    </article>
    <?php endforeach; ?>
</section>
