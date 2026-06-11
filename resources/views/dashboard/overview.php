<?php
use App\Domain\OrderWorkflowStatus;
use App\Domain\SupplierTerminology;

$analytics = $dashboardAnalytics ?? [];
$hero = $analytics['hero'] ?? [];
$ordersRatio = $analytics['orders_ratio'] ?? ['fulfilled' => 0, 'active' => 0, 'pct' => 0];
$topProducts = $analytics['top_products'] ?? [];
$topCategories = $analytics['top_categories'] ?? [];
$salesTrend = $analytics['sales_trend'] ?? ['labels' => [], 'sale' => [], 'retail' => []];
$avgSale = (float) ($analytics['avg_sale_per_order'] ?? 0);
$returnRate = $analytics['return_rate'] ?? ['pct' => 0, 'returns' => 0, 'base' => 0];
$pipeline = $analytics['order_pipeline'] ?? [];
$payments = $analytics['payments'] ?? [];
$products = $analytics['products'] ?? [];
$actionQueue = $analytics['action_queue'] ?? [];
$showRetail = !empty($showRetailAmounts);

$salesMtd = (float) ($hero['sales_mtd'] ?? 0);
$pctChange = (float) ($hero['sales_mtd_pct_change'] ?? 0);
$pctUp = !empty($hero['sales_mtd_up']);
$gaugePct = min(100, max(8, abs($pctChange) > 0 ? min(100, 40 + abs($pctChange) / 5) : 35));

$trendSale = $salesTrend['sale'] ?? [];
$trendRetail = $salesTrend['retail'] ?? [];
$trendMax = max(1.0, ...array_map('floatval', $trendSale), ...array_map('floatval', $showRetail ? $trendRetail : [0]));
$trendLabels = $salesTrend['labels'] ?? [];

$sparkPoints = static function (array $values, float $maxVal, int $w = 300, int $h = 72): string {
    $count = count($values);
    if ($count === 0) {
        return '';
    }
    $pts = [];
    foreach ($values as $i => $v) {
        $x = $count > 1 ? ($i / ($count - 1)) * $w : $w / 2;
        $y = $h - (($maxVal > 0 ? ((float) $v / $maxVal) : 0) * ($h - 8)) - 4;
        $pts[] = round($x, 1) . ',' . round($y, 1);
    }

    return implode(' ', $pts);
};

$payTrend = $payments['trend'] ?? ['labels' => [], 'debits' => [], 'credits' => []];
$payMax = 1.0;
foreach ($payTrend['debits'] ?? [] as $v) {
    $payMax = max($payMax, (float) $v);
}
foreach ($payTrend['credits'] ?? [] as $v) {
    $payMax = max($payMax, (float) $v);
}

$productMax = 1;
foreach ($products['rows'] ?? [] as $row) {
    $productMax = max($productMax, (int) ($row['stock'] ?? 0));
}
?>

<div class="dash-supplier-hero">
    <div class="dash-supplier-hero-text">
        <h1 class="page-title">Dashboard</h1>
        <p class="dash-welcome"><?= !empty($isSupplierView) ? 'Iqbal &amp; Brothers with Lokkisona' : 'Lokkisona ERP' ?> — <?= e($welcomeDate ?? date('l, d F Y')) ?></p>
        <p class="dash-supplier-tagline">Unified KPI wallboard — same view for owner and supplier. Open <a href="<?= e(url('/order-workflow')) ?>">Orders</a> for the live work queue.</p>
    </div>
    <div class="dash-supplier-hero-actions">
        <a href="<?= e(url('/order-workflow')) ?>" class="btn btn-primary btn-sm">Orders</a>
        <a href="<?= e(url('/supplier-tools')) ?>" class="btn btn-secondary btn-sm">Offline Invoice</a>
        <a href="<?= e(url('/reports')) ?>" class="btn btn-secondary btn-sm">Reports</a>
        <a href="<?= e(url('/supplier-payables')) ?>" class="btn btn-secondary btn-sm">Payables</a>
    </div>
</div>

<div class="dash-wallboard-grid">
    <div class="card dash-metric-card dash-metric-gauge">
        <div class="card-header"><h2 class="card-title"><?= e(SupplierTerminology::salesMtd()) ?></h2></div>
        <div class="card-body dash-metric-body">
            <div class="dash-metric-ring" style="--gauge-pct: <?= e((string) $gaugePct) ?>%;">
                <div class="dash-metric-ring-inner">
                    <span class="dash-metric-big-value"><?= e(number_format($salesMtd, 2)) ?></span>
                    <span class="dash-metric-unit">BDT</span>
                    <span class="dash-metric-trend dash-metric-trend-<?= $pctUp ? 'up' : 'down' ?>">
                        <?= $pctUp ? '↑' : '↓' ?> <?= e(number_format(abs($pctChange), 1)) ?>%
                    </span>
                </div>
            </div>
            <?php if ($showRetail): ?>
            <p class="dash-metric-sub">Retail MTD: <strong><?= e(number_format((float) ($hero['retail_mtd'] ?? 0), 2)) ?> BDT</strong></p>
            <?php endif; ?>
            <p class="dash-chart-footnote">vs prior month · ledger sales + offline invoices</p>
        </div>
    </div>

    <div class="card dash-metric-card">
        <div class="card-header"><h2 class="card-title">Orders Processed</h2></div>
        <div class="card-body dash-metric-body">
            <div class="dash-metric-ratio">
                <span class="dash-metric-big-value"><?= e((string) ($ordersRatio['fulfilled'] ?? 0)) ?></span>
                <span class="dash-metric-ratio-sep">/</span>
                <span class="dash-metric-ratio-denom"><?= e((string) ($ordersRatio['active'] ?? 0)) ?></span>
            </div>
            <p class="dash-metric-sub">Shipped + dispatch / active pipeline</p>
            <span class="dash-metric-trend dash-metric-trend-up"><?= e(number_format((float) ($ordersRatio['pct'] ?? 0), 1)) ?>% throughput</span>
        </div>
    </div>

    <div class="card dash-metric-card dash-metric-table-card">
        <div class="card-header card-header-flex">
            <h2 class="card-title">Top 10 Products</h2>
            <a href="<?= e(url('/reports?report=product_sales')) ?>" class="btn btn-sm btn-ghost">Report</a>
        </div>
        <div class="card-body card-body-flush">
            <?php if (!empty($topProducts)): ?>
            <div class="table-scroll">
                <table class="data-table data-table-compact dash-rank-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Qty</th>
                            <th>Sale BDT</th>
                            <?php if ($showRetail): ?><th>Retail BDT</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topProducts as $row): ?>
                        <tr>
                            <td><?= e((string) ($row['product'] ?? '')) ?></td>
                            <td><?= e((string) ($row['category'] ?? '—')) ?></td>
                            <td><?= e((string) ($row['qty'] ?? 0)) ?></td>
                            <td><?= e(number_format((float) ($row['sale_bdt'] ?? 0), 2)) ?></td>
                            <?php if ($showRetail): ?><td><?= e(number_format((float) ($row['retail_bdt'] ?? 0), 2)) ?></td><?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state"><p>No product sales data yet.</p></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="dash-wallboard-grid dash-wallboard-grid-3">
    <div class="card dash-chart-card dash-chart-wide">
        <div class="card-header card-header-flex">
            <h2 class="card-title">Sales Trend (6 weeks)</h2>
            <span class="dash-chart-legend">
                <span class="dash-legend-item"><i class="dash-legend-swatch dash-legend-sales"></i> Sale BDT</span>
                <?php if ($showRetail): ?>
                <span class="dash-legend-item"><i class="dash-legend-swatch dash-legend-retail"></i> Retail BDT</span>
                <?php endif; ?>
            </span>
        </div>
        <div class="card-body">
            <?php if (!empty($trendLabels)): ?>
            <div class="dash-trend-sparkline-wrap">
                <svg class="dash-trend-sparkline" viewBox="0 0 300 72" preserveAspectRatio="none" aria-hidden="true">
                    <polyline class="dash-sparkline-sale" points="<?= e($sparkPoints($trendSale, $trendMax)) ?>" fill="none" stroke-width="2.5"/>
                    <?php if ($showRetail && !empty($trendRetail)): ?>
                    <polyline class="dash-sparkline-retail" points="<?= e($sparkPoints($trendRetail, $trendMax)) ?>" fill="none" stroke-width="2" stroke-dasharray="5,4"/>
                    <?php endif; ?>
                </svg>
                <div class="dash-trend-labels">
                    <?php foreach ($trendLabels as $label): ?>
                    <span><?= e((string) $label) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <p class="dash-chart-footnote">Last week: <strong><?= e(number_format((float) (end($trendSale) ?: 0), 2)) ?> BDT</strong> sale</p>
            <?php else: ?>
            <div class="empty-state"><p>Sales trend appears when orders exist.</p></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card dash-metric-card">
        <div class="card-header"><h2 class="card-title">Avg Sale / Order</h2></div>
        <div class="card-body dash-metric-body">
            <span class="dash-metric-big-value"><?= e(number_format($avgSale, 2)) ?></span>
            <span class="dash-metric-unit">BDT</span>
            <p class="dash-metric-sub">Cost snapshot average</p>
        </div>
    </div>

    <div class="card dash-metric-card">
        <div class="card-header"><h2 class="card-title">Return Rate</h2></div>
        <div class="card-body dash-metric-body">
            <span class="dash-metric-big-value dash-metric-big-value-warn"><?= e(number_format((float) ($returnRate['pct'] ?? 0), 1)) ?>%</span>
            <p class="dash-metric-sub"><?= e((string) ($returnRate['returns'] ?? 0)) ?> returns / <?= e((string) ($returnRate['base'] ?? 0)) ?> fulfilled base</p>
        </div>
    </div>
</div>

<div class="dash-analytics-grid">
    <div class="card dash-chart-card">
        <div class="card-header card-header-flex">
            <h2 class="card-title">Order Pipeline</h2>
            <a href="<?= e(url('/order-workflow')) ?>" class="btn btn-sm btn-ghost">Work queue</a>
        </div>
        <div class="card-body">
            <?php if (!empty($pipeline)): ?>
            <div class="workflow-stage-grid workflow-stage-grid-dashboard">
                <?php foreach ($pipeline as $stage): ?>
                <?php
                $code = (string) ($stage['status'] ?? '');
                $count = (int) ($stage['count'] ?? 0);
                ?>
                <a href="<?= e(url('/order-workflow?status=' . rawurlencode($code))) ?>" class="workflow-stage-card workflow-stage-link <?= e(OrderWorkflowStatus::stageAccentClass($code)) ?>">
                    <span class="workflow-stage-label"><?= e((string) ($stage['label'] ?? '')) ?></span>
                    <span class="workflow-stage-value<?= $count === 0 ? ' workflow-stage-value-zero' : '' ?>"><?= e((string) $count) ?></span>
                </a>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="empty-state"><p>No orders in pipeline yet.</p></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card dash-chart-card">
        <div class="card-header card-header-flex">
            <h2 class="card-title">Payments &amp; Ledger</h2>
            <a href="<?= e(url('/supplier-payables')) ?>" class="btn btn-sm btn-ghost">Payables</a>
        </div>
        <div class="card-body">
            <div class="dash-payment-summary">
                <?php foreach ($payments['breakdown'] ?? [] as $item): ?>
                <div class="dash-payment-stat dash-payment-<?= e((string) ($item['tone'] ?? 'muted')) ?>">
                    <span class="dash-payment-label"><?= e((string) ($item['label'] ?? '')) ?></span>
                    <strong class="dash-payment-value"><?= e(is_float($item['amount'] ?? 0) && ($item['label'] ?? '') !== 'Pending drafts' ? number_format((float) $item['amount'], 2) : (string) ($item['amount'] ?? 0)) ?></strong>
                </div>
                <?php endforeach; ?>
            </div>
            <?php if (!empty($payTrend['labels'])): ?>
            <div class="dash-column-chart dash-column-chart-compact">
                <?php foreach ($payTrend['labels'] as $i => $label): ?>
                <?php
                    $debit = (float) ($payTrend['debits'][$i] ?? 0);
                    $credit = (float) ($payTrend['credits'][$i] ?? 0);
                    $debitH = $payMax > 0 ? round(($debit / $payMax) * 100) : 0;
                    $creditH = $payMax > 0 ? round(($credit / $payMax) * 100) : 0;
                ?>
                <div class="dash-column-group" title="<?= e(SupplierTerminology::salesTrend()) ?>: <?= e(number_format($debit, 2)) ?>, <?= e(SupplierTerminology::paymentsTrend()) ?>: <?= e(number_format($credit, 2)) ?>">
                    <div class="dash-column-bars">
                        <div class="dash-column dash-column-debit" style="height: <?= e((string) max(2, $debitH)) ?>%;"></div>
                        <div class="dash-column dash-column-credit" style="height: <?= e((string) max(2, $creditH)) ?>%;"></div>
                    </div>
                    <span class="dash-column-label"><?= e((string) $label) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <p class="dash-chart-footnote"><?= e(SupplierTerminology::ledgerTrendFootnote()) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <div class="card dash-chart-card">
        <div class="card-header card-header-flex">
            <h2 class="card-title">Top Categories</h2>
            <a href="<?= e(url('/reports?report=category_sales')) ?>" class="btn btn-sm btn-ghost">Report</a>
        </div>
        <div class="card-body card-body-flush">
            <?php if (!empty($topCategories)): ?>
            <div class="table-scroll">
                <table class="data-table data-table-compact dash-rank-table">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Orders</th>
                            <th>Sale BDT</th>
                            <?php if ($showRetail): ?><th>Retail BDT</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topCategories as $row): ?>
                        <tr>
                            <td><?= e((string) ($row['category'] ?? '')) ?></td>
                            <td><?= e((string) ($row['orders'] ?? 0)) ?></td>
                            <td><?= e(number_format((float) ($row['sale_bdt'] ?? 0), 2)) ?></td>
                            <?php if ($showRetail): ?><td><?= e(number_format((float) ($row['retail_bdt'] ?? 0), 2)) ?></td><?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state"><p>Assign supplier categories on Product Control to see breakdown.</p></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card dash-chart-card">
        <div class="card-header card-header-flex">
            <h2 class="card-title">Product Catalog</h2>
            <a href="<?= e(url('/product-control')) ?>" class="btn btn-sm btn-ghost">Products</a>
        </div>
        <div class="card-body">
            <div class="dash-product-stats">
                <div><span class="dash-product-stat-label">Products</span><strong><?= e((string) ($products['total'] ?? 0)) ?></strong></div>
                <div><span class="dash-product-stat-label">Low stock</span><strong class="text-warn"><?= e((string) ($products['low_stock'] ?? 0)) ?></strong></div>
            </div>
            <?php if (!empty($products['rows'])): ?>
            <div class="dash-bar-chart dash-bar-chart-compact">
                <?php foreach ($products['rows'] as $product): ?>
                <?php $stock = (int) ($product['stock'] ?? 0); $pct = $productMax > 0 ? round(($stock / $productMax) * 100) : 0; ?>
                <div class="dash-bar-row" title="<?= e((string) ($product['category'] ?? '')) ?>">
                    <span class="dash-bar-label"><?= e((string) ($product['label'] ?? '')) ?></span>
                    <div class="dash-bar-track">
                        <div class="dash-bar-fill dash-bar-fill-success" style="width: <?= e((string) max(4, $pct)) ?>%;"></div>
                    </div>
                    <span class="dash-bar-value"><?= e((string) $stock) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="empty-state"><p>No products yet.</p></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (!empty($actionQueue)): ?>
<div class="card mb-15">
    <div class="card-header card-header-flex">
        <h2 class="card-title">Priority Actions</h2>
    </div>
    <div class="card-body">
        <ul class="attention-list attention-list-inline">
            <?php foreach ($actionQueue as $item): ?>
            <li class="attention-item attention-<?= e((string) ($item['tone'] ?? 'primary')) ?>">
                <a href="<?= e(url((string) ($item['url'] ?? '#'))) ?>" class="attention-link">
                    <span class="attention-count"><?= e((string) ($item['count'] ?? 0)) ?></span>
                    <span class="attention-label"><?= e((string) ($item['label'] ?? '')) ?></span>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($recentNotes)): ?>
<div class="card">
    <div class="card-header"><h2 class="card-title">Recent Activity</h2></div>
    <div class="card-body">
        <ul class="feature-list">
            <?php foreach ($recentNotes as $note): ?>
                <li><strong><?= e($note['time']) ?></strong> — <?= e($note['text']) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
<?php endif; ?>
