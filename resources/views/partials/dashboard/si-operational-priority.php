<?php
$si = $si ?? [];
$findKpi = static function (string $label) use ($si): ?array {
    foreach ($si['kpis'] ?? [] as $kpi) {
        if ((string) ($kpi['label'] ?? '') === $label) {
            return $kpi;
        }
    }

    return null;
};

$pendingDispatch = 0;
foreach ($si['dispatch_pipeline']['workflow'] ?? [] as $stage) {
    if (in_array((string) ($stage['status'] ?? ''), ['new_order', 'order_received', 'packaging'], true)) {
        $pendingDispatch += (int) ($stage['count'] ?? 0);
    }
}

$returns = $si['returns'] ?? [];
$returnsPending = (int) ($returns['hub_return'] ?? 0) + (int) ($returns['customer_return'] ?? 0);

$payableKpi = $findKpi('Current Payable');
$balance = (float) ($payableKpi['value'] ?? 0);
foreach ($si['payable_center']['ledger_lines'] ?? [] as $line) {
    if (($line['key'] ?? '') === 'balance') {
        $balance = (float) ($line['amount'] ?? $balance);
        break;
    }
}

$catalog = $si['catalog_health'] ?? [];
$ordersKpi = $findKpi('Orders This Month');
$healthScore = (int) ($si['performance']['score'] ?? ($catalog['readiness_score'] ?? 0));

$cards = [
    [
        'label' => 'Orders',
        'display' => number_format((int) ($ordersKpi['value'] ?? 0)),
        'href' => '/order-workflow',
        'tone' => 'primary',
    ],
    [
        'label' => 'Dispatch',
        'display' => number_format($pendingDispatch),
        'href' => '/dispatch-reports',
        'tone' => 'warn',
    ],
    [
        'label' => 'Returns',
        'display' => number_format($returnsPending),
        'href' => '/return-receive',
        'tone' => 'muted',
    ],
    [
        'label' => 'Payable',
        'display' => number_format((float) ($payableKpi['value'] ?? 0), 0) . ' BDT',
        'href' => '/reports?report=supplier_ledger',
        'tone' => 'warn',
    ],
    [
        'label' => 'Balance',
        'display' => number_format($balance, 0) . ' BDT',
        'href' => '/supplier-opening-balances',
        'tone' => 'primary',
    ],
    [
        'label' => 'Products Missing Cost',
        'display' => number_format((int) ($catalog['missing_cost'] ?? 0)),
        'href' => '/product-control?chip=missing_cost',
        'tone' => 'warn',
    ],
    [
        'label' => 'Products Missing Model',
        'display' => number_format((int) ($catalog['missing_model'] ?? 0)),
        'href' => '/product-control?chip=missing_model',
        'tone' => 'warn',
    ],
    [
        'label' => 'Health',
        'display' => number_format($healthScore) . '/100',
        'href' => '/health',
        'tone' => 'success',
    ],
];

$rows = array_chunk($cards, 4);
?>
<section class="si-operational-priority" aria-label="Operational priority">
    <?php foreach ($rows as $row): ?>
    <div class="si-operational-row si-kpi-grid">
        <?php foreach ($row as $card): ?>
        <?php
            $href = (string) ($card['href'] ?? '');
            $tone = (string) ($card['tone'] ?? 'primary');
        ?>
        <a href="<?= e(url($href)) ?>" class="si-kpi-card card si-kpi-tone-<?= e($tone) ?> si-operational-card">
            <div class="si-kpi-top">
                <span class="si-kpi-label"><?= e((string) ($card['label'] ?? '')) ?></span>
            </div>
            <div class="si-kpi-value"><?= e((string) ($card['display'] ?? '0')) ?></div>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endforeach; ?>
</section>
