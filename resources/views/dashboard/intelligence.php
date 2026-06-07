<?php
$si = $supplierIntelligence ?? [];
$siSparkPolyline = static function (array $values, int $w = 120, int $h = 32): string {
    $values = array_map('floatval', $values);
    if ($values === []) {
        return '';
    }
    $max = max(1.0, ...$values);
    $count = count($values);
    $pts = [];
    foreach ($values as $i => $v) {
        $x = $count > 1 ? ($i / ($count - 1)) * $w : $w / 2;
        $y = $h - (($v / $max) * ($h - 6)) - 3;
        $pts[] = round($x, 1) . ',' . round($y, 1);
    }

    return implode(' ', $pts);
};

$trendChart = $si['trend_chart'] ?? [];
$trendMax = 1.0;
foreach (['orders', 'dispatch', 'payable_bdt', 'forecast'] as $seriesKey) {
    foreach ($trendChart[$seriesKey] ?? [] as $v) {
        $trendMax = max($trendMax, (float) $v);
    }
}
?>

<div class="si-dashboard">
    <?php view('partials.dashboard.si-header', [
        'header' => $si['header'] ?? [],
        'supplierDisplayName' => $supplierDisplayName ?? 'Iqbal & Brothers (IBS)',
        'businessSourceLabel' => $businessSourceLabel ?? 'Lokkisona.com',
        'recentNotes' => $recentNotes ?? [],
    ]); ?>

    <?php view('partials.dashboard.si-insights', ['insights' => $si['insights'] ?? []]); ?>

    <?php view('partials.dashboard.si-kpi-grid', [
        'kpis' => $si['kpis'] ?? [],
        'siSparkPolyline' => $siSparkPolyline,
    ]); ?>

    <div class="si-dashboard-row si-dashboard-row-2">
        <?php view('partials.dashboard.si-performance', ['performance' => $si['performance'] ?? []]); ?>
        <?php view('partials.dashboard.si-dispatch', [
            'dispatch_sla' => $si['dispatch_sla'] ?? [],
            'dispatch_pipeline' => $si['dispatch_pipeline'] ?? [],
        ]); ?>
    </div>

    <div class="si-dashboard-row si-dashboard-row-2">
        <?php view('partials.dashboard.si-payable', ['payable_center' => $si['payable_center'] ?? []]); ?>
        <?php view('partials.dashboard.si-trend', [
            'trend_chart' => $trendChart,
            'trendMax' => $trendMax,
        ]); ?>
    </div>

    <div class="si-dashboard-row si-dashboard-row-2">
        <?php view('partials.dashboard.si-top-products', ['top_products' => $si['top_products'] ?? []]); ?>
        <?php view('partials.dashboard.si-top-categories', ['top_categories' => $si['top_categories'] ?? []]); ?>
    </div>

    <div class="si-dashboard-row si-dashboard-row-2">
        <?php view('partials.dashboard.si-catalog-health', ['catalog_health' => $si['catalog_health'] ?? []]); ?>
        <?php view('partials.dashboard.si-returns', ['returns' => $si['returns'] ?? []]); ?>
    </div>

    <?php view('partials.dashboard.si-priority-actions', ['priority_actions' => $si['priority_actions'] ?? []]); ?>
</div>
