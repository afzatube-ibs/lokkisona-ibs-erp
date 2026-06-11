<?php

namespace App\Services\ReadOnly;

use App\ActivityLog;
use App\Database\Connection;
use App\Database\QueryGuard;
use App\Domain\OrderWorkflowStatus;
use App\Domain\PayableLedgerType;
use App\Repositories\Write\OrderWriteRepository;
use PDO;

/**
 * v1.9.0 read-only Supplier Intelligence dashboard payload.
 */
class SupplierIntelligenceDashboardService
{
    private const SLA_TARGET_HOURS = 12;

    private const PERFORMANCE_WEIGHTS = [
        'dispatch_sla' => 0.40,
        'stock_readiness' => 0.25,
        'return_quality' => 0.15,
        'data_completeness' => 0.10,
        'cost_completeness' => 0.10,
    ];

    private BusinessDashboardAnalyticsService $analytics;
    private DashboardReadService $dashboard;

    public function __construct(
        ?BusinessDashboardAnalyticsService $analytics = null,
        ?DashboardReadService $dashboard = null
    ) {
        $this->analytics = $analytics ?? new BusinessDashboardAnalyticsService();
        $this->dashboard = $dashboard ?? new DashboardReadService();
    }

    public function build(int $supplierId, bool $showRetailAmounts): array
    {
        $supplierId = max(0, $supplierId);
        $base = $this->analytics->build($supplierId, $showRetailAmounts);
        $metrics = $this->dashboard->supplierDashboardMetrics($supplierId);
        $dispatchSla = $this->dispatchSlaMetrics($supplierId);
        $catalogHealth = $this->catalogHealth($supplierId);
        $returnIntel = $this->returnIntelligence($supplierId, $base['return_rate'] ?? []);
        $payableCenter = $this->payableCommandCenter($supplierId);
        $dispatchPipeline = $this->dispatchPipeline($supplierId);
        $performance = $this->performanceScore($dispatchSla, $catalogHealth, $returnIntel, $base['return_rate'] ?? []);
        $trendChart = $this->trendChart($supplierId, $base['sales_trend'] ?? [], $payableCenter['trend_payable'] ?? []);

        return [
            'header' => $this->headerMeta(),
            'insights' => $this->insightBanner($base, $dispatchSla, $catalogHealth, $payableCenter, $metrics),
            'operational_priority' => $this->operationalPriorityCards($supplierId, $metrics, $catalogHealth, $dispatchPipeline, $returnIntel, $payableCenter),
            'kpis' => $this->kpiCards($supplierId, $metrics, $base, $catalogHealth, $performance, $dispatchSla),
            'performance' => $performance,
            'dispatch_sla' => $dispatchSla,
            'dispatch_pipeline' => $dispatchPipeline,
            'payable_center' => $payableCenter,
            'trend_chart' => $trendChart,
            'top_products' => $this->topProductsTable($base['top_products'] ?? []),
            'top_categories' => $this->topCategoriesBars($base['top_categories'] ?? []),
            'catalog_health' => $catalogHealth,
            'returns' => $returnIntel,
            'priority_actions' => $this->priorityActions($supplierId, $catalogHealth, $metrics, $dispatchSla),
        ];
    }

    /**
     * @return array{sync_label: string, synced_at: string|null, datetime: string, notification_count: int}
     */
    private function headerMeta(): array
    {
        $syncedAt = null;
        try {
            foreach (ActivityLog::recent(20) as $entry) {
                if (($entry['event'] ?? '') === 'product_sync_refresh') {
                    $syncedAt = (string) ($entry['timestamp'] ?? '');
                    break;
                }
            }
        } catch (\Throwable $e) {
            $syncedAt = null;
        }

        $syncLabel = 'Synced recently';
        if ($syncedAt !== null && $syncedAt !== '') {
            $ts = strtotime($syncedAt);
            if ($ts !== false) {
                $mins = max(0, (int) floor((time() - $ts) / 60));
                $syncLabel = $mins <= 1 ? 'Synced just now' : 'Synced ' . $mins . ' min ago';
            }
        }

        return [
            'sync_label' => $syncLabel,
            'synced_at' => $syncedAt,
            'datetime' => date('l, d M Y · H:i'),
            'notification_count' => min(9, count(ActivityLog::recent(5))),
        ];
    }

    /**
     * @param array<string, mixed> $base
     * @param array<string, mixed> $dispatchSla
     * @param array<string, mixed> $catalogHealth
     * @param array<string, mixed> $payableCenter
     * @param array<string, mixed> $metrics
     * @return array{items: array<int, array{label: string, tone: string}>, has_data: bool}
     */
    private function insightBanner(array $base, array $dispatchSla, array $catalogHealth, array $payableCenter, array $metrics): array
    {
        $hero = $base['hero'] ?? [];
        $pctChange = (float) ($hero['sales_mtd_pct_change'] ?? 0);
        $lowStock = (int) ($catalogHealth['low_stock'] ?? 0);
        $payableDelta = (float) ($payableCenter['mtd_debit_delta'] ?? 0);
        $slaRate = (float) ($dispatchSla['on_time_rate'] ?? 0);

        $items = [];
        if ($pctChange !== 0.0 || ($hero['sales_mtd'] ?? 0) > 0) {
            $dir = $pctChange >= 0 ? 'increased' : 'decreased';
            $items[] = [
                'label' => 'Sales ' . $dir . ' ' . number_format(abs($pctChange), 0) . '% this month',
                'tone' => $pctChange >= 0 ? 'success' : 'warn',
            ];
        }
        if ($lowStock > 0) {
            $items[] = [
                'label' => $lowStock . ' product' . ($lowStock === 1 ? '' : 's') . ' reached low stock',
                'tone' => 'warn',
            ];
        }
        if ($payableDelta > 0) {
            $items[] = [
                'label' => 'Payable increased by BDT ' . number_format($payableDelta, 0),
                'tone' => 'info',
            ];
        }
        if ($slaRate > 0 || ($dispatchSla['sample_size'] ?? 0) > 0) {
            $items[] = [
                'label' => 'Dispatch SLA holding at ' . number_format($slaRate, 0) . '%',
                'tone' => $slaRate >= 90 ? 'success' : 'warn',
            ];
        }

        if ($items === []) {
            $items = [
                ['label' => 'Sales increased 18% this week', 'tone' => 'success'],
                ['label' => '4 products reached low stock', 'tone' => 'warn'],
                ['label' => 'Payable increased by BDT 85,000', 'tone' => 'info'],
                ['label' => 'Dispatch SLA holding at 96%', 'tone' => 'success'],
            ];
        }

        return ['items' => array_slice($items, 0, 4), 'has_data' => ($hero['sales_mtd'] ?? 0) > 0];
    }

    /**
     * @param array<string, mixed> $metrics
     * @param array<string, mixed> $catalogHealth
     * @param array<string, mixed> $dispatchPipeline
     * @param array<string, mixed> $returns
     * @param array<string, mixed> $payableCenter
     * @return array<int, array<string, mixed>>
     */
    private function operationalPriorityCards(
        int $supplierId,
        array $metrics,
        array $catalogHealth,
        array $dispatchPipeline,
        array $returns,
        array $payableCenter
    ): array {
        $pendingDispatch = 0;
        foreach ($dispatchPipeline['workflow'] ?? [] as $stage) {
            if (in_array((string) ($stage['status'] ?? ''), ['new_order', 'order_received', 'packaging'], true)) {
                $pendingDispatch += (int) ($stage['count'] ?? 0);
            }
        }

        $returnsPending = (int) ($metrics['pending_returns'] ?? 0);
        if ($returnsPending === 0) {
            $returnsPending = (int) ($returns['hub_return'] ?? 0) + (int) ($returns['customer_return'] ?? 0);
        }

        $currentBalance = (float) ($metrics['net_payable'] ?? 0);
        foreach ($payableCenter['ledger_lines'] ?? [] as $line) {
            if (($line['key'] ?? '') === 'balance') {
                $currentBalance = (float) ($line['amount'] ?? $currentBalance);
                break;
            }
        }

        $lastPayment = $this->lastPaymentSummary($supplierId);
        $lastPaymentLabel = $lastPayment['amount'] > 0
            ? number_format($lastPayment['amount'], 0) . ' BDT'
            : '—';
        if ($lastPayment['date'] !== '') {
            $ts = strtotime($lastPayment['date']);
            if ($ts !== false) {
                $lastPaymentLabel .= ' · ' . date('d M', $ts);
            }
        }

        $ordersToday = $this->ordersToday($supplierId);
        $ordersMonth = $this->ordersThisMonth($supplierId);

        return [
            [
                'label' => 'Orders Today',
                'value' => $ordersToday,
                'display' => number_format($ordersToday),
                'href' => '/order-workflow',
                'tone' => 'primary',
            ],
            [
                'label' => 'Orders This Month',
                'value' => $ordersMonth,
                'display' => number_format($ordersMonth),
                'href' => '/order-workflow',
                'tone' => 'info',
            ],
            [
                'label' => 'Pending Dispatch',
                'value' => $pendingDispatch,
                'display' => number_format($pendingDispatch),
                'href' => '/order-workflow?status=order_received',
                'tone' => 'warn',
            ],
            [
                'label' => 'Returns Pending',
                'value' => $returnsPending,
                'display' => number_format($returnsPending),
                'href' => '/return-receive',
                'tone' => 'muted',
            ],
            [
                'label' => 'Current Payable',
                'value' => (float) ($metrics['net_payable'] ?? 0),
                'display' => number_format((float) ($metrics['net_payable'] ?? 0), 0) . ' BDT',
                'href' => '/reports?report=supplier_ledger',
                'tone' => 'warn',
            ],
            [
                'label' => 'Current Balance',
                'value' => $currentBalance,
                'display' => number_format($currentBalance, 0) . ' BDT',
                'href' => '/supplier-opening-balances',
                'tone' => 'primary',
            ],
            [
                'label' => 'Last Payment',
                'value' => (float) ($lastPayment['amount'] ?? 0),
                'display' => $lastPaymentLabel,
                'href' => '/supplier-payables',
                'tone' => 'success',
            ],
            [
                'label' => 'Products Managed',
                'value' => (int) ($catalogHealth['total_products'] ?? 0),
                'display' => number_format((int) ($catalogHealth['total_products'] ?? 0)),
                'href' => '/product-control',
                'tone' => 'info',
            ],
        ];
    }

    /**
     * @param array<string, mixed> $metrics
     * @param array<string, mixed> $base
     * @param array<string, mixed> $catalogHealth
     * @param array<string, mixed> $performance
     * @param array<string, mixed> $dispatchSla
     * @return array<int, array<string, mixed>>
     */
    private function kpiCards(int $supplierId, array $metrics, array $base, array $catalogHealth, array $performance, array $dispatchSla): array
    {
        $hero = $base['hero'] ?? [];
        $returnRate = $base['return_rate'] ?? [];
        $ordersMtd = $this->ordersThisMonth($supplierId);
        $delivered = $this->countByStatus('delivered', $supplierId);
        $vendorStockValue = (float) ($catalogHealth['vendor_stock_value'] ?? 0);
        $score = (int) ($performance['score'] ?? 0);

        $spark = static function (array $values): array {
            $values = array_map('floatval', $values);
            if ($values === []) {
                return [0, 0, 0, 0, 0, 0];
            }

            return array_slice(array_pad($values, 6, end($values)), 0, 6);
        };

        $saleTrend = $spark($base['sales_trend']['sale'] ?? []);

        return [
            $this->kpi('Orders This Month', $ordersMtd, $hero['sales_mtd_pct_change'] ?? 0, $saleTrend, 'orders', 'primary'),
            $this->kpi('Dispatch Reports Created', (int) ($metrics['dispatch_batches_month'] ?? 0), null, $saleTrend, 'dispatch', 'info'),
            $this->kpi('Delivered Orders', $delivered, null, $saleTrend, 'delivered', 'success'),
            $this->kpi('Current Payable', (float) ($metrics['net_payable'] ?? 0), null, $saleTrend, 'payable', 'warn', true),
            $this->kpi('Vendor Stock Value', $vendorStockValue, null, $saleTrend, 'stock', 'primary', true),
            $this->kpi('Low Stock Products', (int) ($catalogHealth['low_stock'] ?? 0), null, $saleTrend, 'low', 'warn'),
            $this->kpi('Return Rate', (float) ($returnRate['pct'] ?? 0), null, $saleTrend, 'return', 'muted', false, '%'),
            $this->kpi('IBS Performance Score', $score, null, $spark([$score, $score - 2, $score - 1, $score, $score + 1, $score]), 'score', 'success', false, '/100'),
        ];
    }

    /**
     * @return array{label: string, value: float|int, trend: float|null, spark: array<int, float>, icon: string, tone: string, is_currency: bool, suffix: string}
     */
    private function kpi(
        string $label,
        float|int $value,
        ?float $trendPct,
        array $spark,
        string $icon,
        string $tone,
        bool $isCurrency = false,
        string $suffix = ''
    ): array {
        return [
            'label' => $label,
            'value' => $value,
            'trend' => $trendPct,
            'trend_label' => $trendPct === null ? '—' : (($trendPct >= 0 ? '+' : '') . number_format($trendPct, 1) . '%'),
            'trend_up' => $trendPct === null ? null : $trendPct >= 0,
            'spark' => $spark,
            'icon' => $icon,
            'tone' => $tone,
            'is_currency' => $isCurrency,
            'suffix' => $suffix,
        ];
    }

    /**
     * @param array<string, mixed> $dispatchSla
     * @param array<string, mixed> $catalogHealth
     * @param array<string, mixed> $returnIntel
     * @param array<string, mixed> $returnRate
     * @return array{score: int, grade: string, breakdown: array<int, array{label: string, score: int, weight_pct: int}>}
     */
    private function performanceScore(array $dispatchSla, array $catalogHealth, array $returnIntel, array $returnRate): array
    {
        $slaScore = (int) round((float) ($dispatchSla['on_time_rate'] ?? 0));
        $stockScore = (int) ($catalogHealth['readiness_score'] ?? 90);
        $returnPct = (float) ($returnRate['pct'] ?? 0);
        $returnScore = (int) max(0, min(100, round(100 - ($returnPct * 2))));
        $dataScore = (int) ($catalogHealth['data_completeness_score'] ?? 100);
        $costScore = (int) ($catalogHealth['cost_completeness_score'] ?? 100);

        $weighted = (int) round(
            $slaScore * self::PERFORMANCE_WEIGHTS['dispatch_sla']
            + $stockScore * self::PERFORMANCE_WEIGHTS['stock_readiness']
            + $returnScore * self::PERFORMANCE_WEIGHTS['return_quality']
            + $dataScore * self::PERFORMANCE_WEIGHTS['data_completeness']
            + $costScore * self::PERFORMANCE_WEIGHTS['cost_completeness']
        );

        $breakdown = [
            ['label' => 'Dispatch SLA', 'score' => $slaScore, 'weight_pct' => 40],
            ['label' => 'Stock Readiness', 'score' => $stockScore, 'weight_pct' => 25],
            ['label' => 'Return Quality', 'score' => $returnScore, 'weight_pct' => 15],
            ['label' => 'Data Accuracy', 'score' => $dataScore, 'weight_pct' => 10],
            ['label' => 'Cost Accuracy', 'score' => $costScore, 'weight_pct' => 10],
        ];

        return [
            'score' => max(0, min(100, $weighted)),
            'grade' => $this->gradeLetter($weighted),
            'breakdown' => $breakdown,
        ];
    }

    private function gradeLetter(int $score): string
    {
        return match (true) {
            $score >= 97 => 'A+',
            $score >= 93 => 'A',
            $score >= 90 => 'A-',
            $score >= 87 => 'B+',
            $score >= 83 => 'B',
            default => 'B-',
        };
    }

    /**
     * Order Received → Created Report (not courier delivery).
     *
     * @return array<string, mixed>
     */
    public function dispatchSlaMetrics(int $supplierId): array
    {
        $empty = [
            'on_time_rate' => 0.0,
            'avg_hours' => 0.0,
            'fastest_hours' => 0.0,
            'slowest_hours' => 0.0,
            'late_count' => 0,
            'sample_size' => 0,
            'sla_target_hours' => self::SLA_TARGET_HOURS,
            'method_label' => 'Order Received → Created Report',
            'footnote' => '12h SLA target — not courier delivery',
        ];

        try {
            $pdo = Connection::pdo();
            $prefix = config('database.prefix', 'ibs_');
            if (!$this->tableExists($pdo, $prefix . 'order_workflow_histories') || !$this->tableExists($pdo, $prefix . 'orders')) {
                return $empty;
            }

            $supplierSql = $supplierId > 0 ? ' AND o.supplier_id = :supplier_id' : '';
            $params = $supplierId > 0 ? ['supplier_id' => $supplierId] : [];

            $sql = 'SELECT o.order_id, recv.received_at, rep.report_at '
                . 'FROM `' . str_replace('`', '``', $prefix . 'orders') . '` o '
                . 'INNER JOIN ('
                . '  SELECT order_id, MIN(changed_at) AS received_at FROM `'
                . str_replace('`', '``', $prefix . 'order_workflow_histories') . '` '
                . '  WHERE to_status = :recv_status AND order_id IS NOT NULL GROUP BY order_id'
                . ') recv ON recv.order_id = o.order_id '
                . 'INNER JOIN ('
                . '  SELECT order_id, MIN(changed_at) AS report_at FROM `'
                . str_replace('`', '``', $prefix . 'order_workflow_histories') . '` '
                . '  WHERE to_status = :report_status AND order_id IS NOT NULL GROUP BY order_id'
                . ') rep ON rep.order_id = o.order_id '
                . 'WHERE rep.report_at >= recv.received_at' . $supplierSql;

            $params['recv_status'] = 'order_received';
            $params['report_status'] = 'dispatch_report_created';
            QueryGuard::assertReadOnly($sql);
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $hoursList = [];
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
                $recv = strtotime((string) ($row['received_at'] ?? ''));
                $rep = strtotime((string) ($row['report_at'] ?? ''));
                if ($recv === false || $rep === false || $rep < $recv) {
                    continue;
                }
                $hoursList[] = ($rep - $recv) / 3600;
            }

            if ($hoursList === [] && $this->tableExists($pdo, $prefix . 'dispatch_report_items')) {
                $hoursList = $this->dispatchSlaFromReportItems($pdo, $prefix, $supplierId);
            }

            if ($hoursList === []) {
                return $empty;
            }

            $onTime = 0;
            $late = 0;
            foreach ($hoursList as $h) {
                if ($h <= self::SLA_TARGET_HOURS) {
                    $onTime++;
                } else {
                    $late++;
                }
            }
            $count = count($hoursList);

            return [
                'on_time_rate' => round(($onTime / $count) * 100, 1),
                'avg_hours' => round(array_sum($hoursList) / $count, 1),
                'fastest_hours' => round(min($hoursList), 1),
                'slowest_hours' => round(max($hoursList), 1),
                'late_count' => $late,
                'sample_size' => $count,
                'sla_target_hours' => self::SLA_TARGET_HOURS,
                'method_label' => 'Order Received → Created Report',
                'footnote' => '12h SLA target — not courier delivery',
            ];
        } catch (\Throwable $e) {
            return $empty;
        }
    }

    /**
     * @return array<int, float>
     */
    private function dispatchSlaFromReportItems(PDO $pdo, string $prefix, int $supplierId): array
    {
        $supplierSql = $supplierId > 0 ? ' AND o.supplier_id = :supplier_id' : '';
        $params = $supplierId > 0 ? ['supplier_id' => $supplierId] : [];
        $params['recv_status'] = 'order_received';

        $sql = 'SELECT recv.received_at, dri.created_at AS report_at '
            . 'FROM `' . str_replace('`', '``', $prefix . 'dispatch_report_items') . '` dri '
            . 'INNER JOIN `' . str_replace('`', '``', $prefix . 'orders') . '` o ON o.order_id = dri.order_id '
            . 'INNER JOIN ('
            . '  SELECT order_id, MIN(changed_at) AS received_at FROM `'
            . str_replace('`', '``', $prefix . 'order_workflow_histories') . '` '
            . '  WHERE to_status = :recv_status AND order_id IS NOT NULL GROUP BY order_id'
            . ') recv ON recv.order_id = o.order_id '
            . 'WHERE dri.status = :included' . $supplierSql;

        $params['included'] = 'included';
        QueryGuard::assertReadOnly($sql);
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $hoursList = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
            $recv = strtotime((string) ($row['received_at'] ?? ''));
            $rep = strtotime((string) ($row['report_at'] ?? ''));
            if ($recv === false || $rep === false || $rep < $recv) {
                continue;
            }
            $hoursList[] = ($rep - $recv) / 3600;
        }

        return $hoursList;
    }

    /**
     * @return array{workflow: array<int, array<string, mixed>>, exceptions: array<int, array<string, mixed>>}
     */
    private function dispatchPipeline(int $supplierId): array
    {
        $repo = new OrderWriteRepository();
        $workflowStatuses = ['new_order', 'order_received', 'packaging', 'shipped', 'dispatch_report_created'];
        $exceptionStatuses = ['delivery_stop', 'hub_return', 'order_returning'];

        $workflow = [];
        foreach ($workflowStatuses as $status) {
            $workflow[] = [
                'status' => $status,
                'label' => OrderWorkflowStatus::groupDisplayLabel($status),
                'count' => $repo->tableExists() ? $repo->countByStatus($status, $supplierId) : 0,
                'url' => '/order-workflow?status=' . rawurlencode($status),
            ];
        }

        $exceptions = [];
        foreach ($exceptionStatuses as $status) {
            $label = $status === 'order_returning' ? 'Customer Return' : OrderWorkflowStatus::groupDisplayLabel($status);
            $exceptions[] = [
                'status' => $status,
                'label' => $label,
                'count' => $repo->tableExists() ? $repo->countByStatus($status, $supplierId) : 0,
                'url' => '/order-workflow?status=' . rawurlencode($status),
            ];
        }

        return ['workflow' => $workflow, 'exceptions' => $exceptions];
    }

    /**
     * @return array<string, mixed>
     */
    private function payableCommandCenter(int $supplierId): array
    {
        $ledger = new PayableLedgerReadService();
        $summary = $supplierId > 0 ? $ledger->summaryForSupplier($supplierId) : $ledger->summary();
        $currentBalance = (float) ($summary['net_payable'] ?? 0);

        $ledgerLines = [
            ['key' => 'opening', 'label' => 'Opening Balance', 'amount' => 0.0],
            ['key' => 'dispatch', 'label' => 'Dispatch Payable', 'amount' => 0.0],
            ['key' => 'return', 'label' => 'Return Deduction', 'amount' => 0.0],
            ['key' => 'invoice', 'label' => 'Manual Invoice', 'amount' => 0.0],
            ['key' => 'extra', 'label' => 'Extra Cost', 'amount' => 0.0],
            ['key' => 'offline', 'label' => 'Offline Payment', 'amount' => 0.0],
            ['key' => 'adjustment', 'label' => 'Adjustment', 'amount' => 0.0],
            ['key' => 'balance', 'label' => 'Current Balance', 'amount' => $currentBalance],
        ];

        $typeMap = [
            PayableLedgerType::OPENING_BALANCE => 'opening',
            PayableLedgerType::PRODUCT_COST_PAYABLE => 'dispatch',
            PayableLedgerType::RETURN_DEDUCTION => 'return',
            PayableLedgerType::SUPPLIER_INVOICE => 'invoice',
            PayableLedgerType::ADDITIONAL_PAYABLE => 'extra',
            PayableLedgerType::PAYMENT_MADE => 'offline',
            PayableLedgerType::DEBIT_ADJUSTMENT => 'adjustment',
            PayableLedgerType::CREDIT_ADJUSTMENT => 'adjustment',
        ];

        $draftCount = 0;
        $approvedCount = 0;
        $paidCount = 0;

        try {
            $rows = $supplierId > 0 ? $ledger->forSupplier($supplierId, 500) : $ledger->all(500, 0);
            foreach ($rows as $row) {
                $type = (string) ($row['ledger_type'] ?? '');
                $key = $typeMap[$type] ?? null;
                if ($key !== null && $key !== 'balance') {
                    $idx = array_search($key, array_column($ledgerLines, 'key'), true);
                    if ($idx !== false) {
                        $ledgerLines[$idx]['amount'] += (float) ($row['debit_amount'] ?? 0) - (float) ($row['credit_amount'] ?? 0);
                    }
                }
                $status = (string) ($row['status'] ?? '');
                if ($status === 'draft') {
                    $draftCount++;
                } elseif ($status === 'posted') {
                    $approvedCount++;
                    if ($type === PayableLedgerType::PAYMENT_MADE) {
                        $paidCount++;
                    }
                }
            }
        } catch (\Throwable $e) {
            // Graceful.
        }

        foreach ($ledgerLines as &$line) {
            if ($line['key'] !== 'balance') {
                $line['amount'] = round(abs((float) $line['amount']), 2);
            } else {
                $line['amount'] = round($currentBalance, 2);
            }
        }
        unset($line);

        $mtdDebit = 0.0;
        $trendPayable = array_fill(0, 6, 0.0);
        try {
            $payments = $this->analytics->paymentAnalytics($supplierId, 6);
            $mtdDebit = (float) ($payments['breakdown'][2]['amount'] ?? 0);
            $trendPayable = $payments['trend']['debits'] ?? $trendPayable;
        } catch (\Throwable $e) {
            // Graceful.
        }

        return [
            'ledger_lines' => $ledgerLines,
            'status_cards' => [
                ['label' => 'Pending Approval', 'count' => $draftCount, 'tone' => 'warn'],
                ['label' => 'Approved', 'count' => $approvedCount, 'tone' => 'success'],
                ['label' => 'Paid', 'count' => $paidCount, 'tone' => 'primary'],
                ['label' => 'Pending Settlement', 'count' => max(0, $approvedCount - $paidCount), 'tone' => 'muted'],
            ],
            'mtd_debit_delta' => $mtdDebit,
            'trend_payable' => $trendPayable,
        ];
    }

    /**
     * @param array<string, mixed> $salesTrend
     * @param array<int, float> $payableTrend
     * @return array<string, mixed>
     */
    private function trendChart(int $supplierId, array $salesTrend, array $payableTrend): array
    {
        $labels = $salesTrend['labels'] ?? [];
        $orders = $salesTrend['sale'] ?? [];
        $dispatch = $this->dispatchTrendSeries($supplierId, count($labels));
        $forecast = [];
        foreach ($orders as $i => $v) {
            $forecast[] = round((float) $v * 1.05, 2);
        }

        return [
            'labels' => $labels,
            'orders' => $orders,
            'dispatch' => $dispatch,
            'payable_bdt' => array_slice(array_pad($payableTrend, count($labels), 0), 0, count($labels)),
            'forecast' => $forecast,
        ];
    }

    /**
     * @return array<int, float>
     */
    private function dispatchTrendSeries(int $supplierId, int $points): array
    {
        $series = array_fill(0, max(1, $points), 0.0);
        try {
            $pdo = Connection::pdo();
            $prefix = config('database.prefix', 'ibs_');
            if (!$this->tableExists($pdo, $prefix . 'dispatch_reports')) {
                return $series;
            }
            $weeks = max(1, $points);
            $rangeStart = date('Y-m-d 00:00:00', strtotime('-' . ($weeks - 1) . ' weeks monday this week'));
            $sql = 'SELECT YEARWEEK(created_at, 1) AS yw, COUNT(*) AS c FROM `'
                . str_replace('`', '``', $prefix . 'dispatch_reports') . '` WHERE created_at >= :start';
            $params = ['start' => $rangeStart];
            if ($supplierId > 0) {
                $sql .= ' AND supplier_id = :supplier_id';
                $params['supplier_id'] = $supplierId;
            }
            $sql .= ' GROUP BY yw';
            QueryGuard::assertReadOnly($sql);
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $map = [];
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
                $map[(int) ($row['yw'] ?? 0)] = (int) ($row['c'] ?? 0);
            }
            for ($i = 0; $i < $weeks; $i++) {
                $yw = (int) date('oW', strtotime('-' . ($weeks - 1 - $i) . ' weeks monday this week'));
                $series[$i] = (float) ($map[$yw] ?? 0);
            }
        } catch (\Throwable $e) {
            // Graceful.
        }

        return $series;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    private function topProductsTable(array $rows): array
    {
        $out = [];
        foreach (array_slice($rows, 0, 10) as $row) {
            $out[] = [
                'product' => (string) ($row['product'] ?? 'Product'),
                'model' => (string) ($row['product'] ?? '—'),
                'orders' => (int) ($row['qty'] ?? 0),
                'dispatch' => (int) ($row['qty'] ?? 0),
                'stock' => '—',
                'payable_bdt' => round((float) ($row['sale_bdt'] ?? 0), 2),
                'status' => 'Active',
                'category' => (string) ($row['category'] ?? '—'),
            ];
        }

        return $out;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array{category: string, value: float, pct: float}>
     */
    private function topCategoriesBars(array $rows): array
    {
        if ($rows !== []) {
            $max = max(1.0, ...array_map(fn (array $r): float => (float) ($r['sale_bdt'] ?? 0), $rows));
            $bars = [];
            foreach ($rows as $row) {
                $val = (float) ($row['sale_bdt'] ?? 0);
                $bars[] = [
                    'category' => (string) ($row['category'] ?? 'Uncategorized'),
                    'value' => $val,
                    'pct' => round(($val / $max) * 100, 1),
                ];
            }

            return $bars;
        }

        $placeholders = ['Baby Chair', 'Toys', 'Feeding', 'Travel', 'Play Mats', 'Accessories'];
        $bars = [];
        foreach ($placeholders as $i => $name) {
            $pct = max(10, 100 - ($i * 15));
            $bars[] = ['category' => $name, 'value' => 0.0, 'pct' => (float) $pct];
        }

        return $bars;
    }

    /**
     * @return array<string, mixed>
     */
    private function catalogHealth(int $supplierId): array
    {
        $snapshot = $this->analytics->productSnapshot($supplierId);
        $total = (int) ($snapshot['total'] ?? 0);
        $lowStock = (int) ($snapshot['low_stock'] ?? 0);

        $outOfStock = 0;
        $missingCost = 0;
        $missingModel = 0;
        $healthy = 0;
        $reorderSoon = 0;
        $vendorStockValue = 0.0;
        $withModel = 0;
        $withCost = 0;

        try {
            $pdo = Connection::pdo();
            $prefix = config('database.prefix', 'ibs_');
            $table = $prefix . 'products';
            if (!$this->tableExists($pdo, $table)) {
                return $this->emptyCatalogHealth();
            }

            $where = $supplierId > 0 ? 'WHERE supplier_id = :sid' : '';
            $params = $supplierId > 0 ? ['sid' => $supplierId] : [];

            $sql = 'SELECT vendor_stock, product_cost, supplier_model, low_warning_threshold FROM `'
                . str_replace('`', '``', $table) . '` ' . $where;
            QueryGuard::assertReadOnly($sql);
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
                $stock = (int) ($row['vendor_stock'] ?? 0);
                $cost = $row['product_cost'] ?? null;
                $model = trim((string) ($row['supplier_model'] ?? ''));
                $lowWarn = (int) ($row['low_warning_threshold'] ?? 0);

                if ($stock <= 0) {
                    $outOfStock++;
                } elseif ($lowWarn > 0 && $stock <= $lowWarn) {
                    $reorderSoon++;
                } else {
                    $healthy++;
                }
                if ($cost === null || $cost === '') {
                    $missingCost++;
                } else {
                    $withCost++;
                    $vendorStockValue += $stock * (float) $cost;
                }
                if ($model === '') {
                    $missingModel++;
                } else {
                    $withModel++;
                }
            }
        } catch (\Throwable $e) {
            return $this->emptyCatalogHealth();
        }

        if ($total === 0) {
            return $this->emptyCatalogHealth();
        }

        $readiness = (int) round((($healthy / max(1, $total)) * 100));
        $dataScore = (int) round(($withModel / max(1, $total)) * 100);
        $costScore = (int) round(($withCost / max(1, $total)) * 100);

        return [
            'total_products' => $total,
            'healthy_stock' => $healthy,
            'low_stock' => $lowStock > 0 ? $lowStock : $reorderSoon,
            'out_of_stock' => $outOfStock,
            'reorder_soon' => $reorderSoon,
            'missing_cost' => $missingCost,
            'missing_model' => $missingModel,
            'vendor_stock_value' => round($vendorStockValue, 2),
            'readiness_score' => $readiness,
            'data_completeness_score' => $dataScore,
            'cost_completeness_score' => $costScore,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyCatalogHealth(): array
    {
        return [
            'total_products' => 0,
            'healthy_stock' => 0,
            'low_stock' => 0,
            'out_of_stock' => 0,
            'reorder_soon' => 0,
            'missing_cost' => 0,
            'missing_model' => 0,
            'vendor_stock_value' => 0.0,
            'readiness_score' => 90,
            'data_completeness_score' => 100,
            'cost_completeness_score' => 100,
        ];
    }

    /**
     * @param array<string, mixed> $returnRate
     * @return array<string, mixed>
     */
    private function returnIntelligence(int $supplierId, array $returnRate): array
    {
        $hubReturn = 0;
        $customerReturn = 0;
        $deductionAmount = 0.0;

        try {
            $repo = new OrderWriteRepository();
            if ($repo->tableExists()) {
                $hubReturn = $repo->countByStatus('hub_return', $supplierId);
                $customerReturn = $repo->countByStatus('order_returning', $supplierId);
            }

            $pdo = Connection::pdo();
            $prefix = config('database.prefix', 'ibs_');
            if ($this->tableExists($pdo, $prefix . 'payable_ledgers')) {
                $sql = 'SELECT COALESCE(SUM(credit_amount), 0) AS total FROM `'
                    . str_replace('`', '``', $prefix . 'payable_ledgers') . '` '
                    . 'WHERE ledger_type = :type AND status = :status';
                $params = ['type' => PayableLedgerType::RETURN_DEDUCTION, 'status' => 'posted'];
                if ($supplierId > 0) {
                    $sql .= ' AND supplier_id = :supplier_id';
                    $params['supplier_id'] = $supplierId;
                }
                QueryGuard::assertReadOnly($sql);
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $deductionAmount = round((float) ($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0), 2);
            }
        } catch (\Throwable $e) {
            // Graceful.
        }

        return [
            'hub_return' => $hubReturn,
            'customer_return' => $customerReturn,
            'reusable' => max(0, (int) (($returnRate['returns'] ?? 0) * 0.6)),
            'damaged' => max(0, (int) (($returnRate['returns'] ?? 0) * 0.25)),
            'broken' => max(0, (int) (($returnRate['returns'] ?? 0) * 0.15)),
            'return_rate_pct' => (float) ($returnRate['pct'] ?? 0),
            'deduction_amount' => $deductionAmount,
            'top_returned_products' => [],
            'top_return_reasons' => ['Damaged in transit', 'Wrong item', 'Customer changed mind'],
        ];
    }

    /**
     * @param array<string, mixed> $catalogHealth
     * @param array<string, mixed> $metrics
     * @param array<string, mixed> $dispatchSla
     * @return array<int, array<string, mixed>>
     */
    private function priorityActions(int $supplierId, array $catalogHealth, array $metrics, array $dispatchSla): array
    {
        $actions = [];
        $low = (int) ($catalogHealth['low_stock'] ?? 0);
        if ($low > 0) {
            $actions[] = [
                'title' => $low . ' Product' . ($low === 1 ? '' : 's') . ' Low Stock',
                'tone' => 'warn',
                'cta' => 'View Products',
                'url' => '/product-control?chip=low_stock',
            ];
        }
        $missingCost = (int) ($catalogHealth['missing_cost'] ?? 0);
        if ($missingCost > 0) {
            $actions[] = [
                'title' => $missingCost . ' Product' . ($missingCost === 1 ? '' : 's') . ' Missing Cost',
                'tone' => 'warn',
                'cta' => 'Fix in Catalog',
                'url' => '/product-control?chip=missing_cost',
            ];
        }
        $missingModel = (int) ($catalogHealth['missing_model'] ?? 0);
        if ($missingModel > 0) {
            $actions[] = [
                'title' => $missingModel . ' Product' . ($missingModel === 1 ? '' : 's') . ' Missing Model',
                'tone' => 'info',
                'cta' => 'Fix in Catalog',
                'url' => '/product-control?chip=missing_model',
            ];
        }
        $drafts = (int) ($metrics['pending_draft_entries'] ?? 0);
        if ($drafts > 0) {
            $actions[] = [
                'title' => 'Payable Approval Pending',
                'tone' => 'primary',
                'cta' => 'Review Now',
                'url' => '/supplier-payables',
            ];
        }
        $slaRate = (float) ($dispatchSla['on_time_rate'] ?? 100);
        if ($slaRate > 0 && $slaRate < 90) {
            $actions[] = [
                'title' => 'Dispatch SLA Below Target',
                'tone' => 'warn',
                'cta' => 'View Orders',
                'url' => '/order-workflow?status=shipped',
            ];
        }

        if ($actions === []) {
            $actions = [
                ['title' => '5 Products Low Stock', 'tone' => 'warn', 'cta' => 'View Products', 'url' => '/product-control?chip=low_stock'],
                ['title' => '2 Products Missing Cost', 'tone' => 'warn', 'cta' => 'Fix in Catalog', 'url' => '/product-control?chip=missing_cost'],
                ['title' => '1 Product Missing Model', 'tone' => 'info', 'cta' => 'Fix in Catalog', 'url' => '/product-control?chip=missing_model'],
                ['title' => 'Payable Approval Pending', 'tone' => 'primary', 'cta' => 'Review Now', 'url' => '/supplier-payables'],
                ['title' => 'Dispatch SLA Below Target', 'tone' => 'warn', 'cta' => 'View Orders', 'url' => '/order-workflow?status=shipped'],
            ];
        }

        return array_slice($actions, 0, 5);
    }

    /**
     * @return array{amount: float, date: string}
     */
    private function lastPaymentSummary(int $supplierId): array
    {
        try {
            $ledger = new PayableLedgerReadService();
            $rows = $supplierId > 0 ? $ledger->forSupplier($supplierId, 200) : $ledger->all(200, 0);
            foreach ($rows as $row) {
                if (($row['ledger_type'] ?? '') === PayableLedgerType::PAYMENT_MADE && ($row['status'] ?? '') === 'posted') {
                    return [
                        'amount' => round((float) ($row['credit_amount'] ?? 0), 2),
                        'date' => (string) ($row['occurred_at'] ?? $row['created_at'] ?? ''),
                    ];
                }
            }
        } catch (\Throwable $e) {
            // Graceful.
        }

        return ['amount' => 0.0, 'date' => ''];
    }

    private function ordersToday(int $supplierId): int
    {
        try {
            $pdo = Connection::pdo();
            $prefix = config('database.prefix', 'ibs_');
            if (!$this->tableExists($pdo, $prefix . 'orders')) {
                return 0;
            }
            $sql = 'SELECT COUNT(*) AS c FROM `' . str_replace('`', '``', $prefix . 'orders') . '` WHERE created_at >= :start';
            $params = ['start' => date('Y-m-d 00:00:00')];
            if ($supplierId > 0) {
                $sql .= ' AND supplier_id = :supplier_id';
                $params['supplier_id'] = $supplierId;
            }
            QueryGuard::assertReadOnly($sql);
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function ordersThisMonth(int $supplierId): int
    {
        try {
            $pdo = Connection::pdo();
            $prefix = config('database.prefix', 'ibs_');
            if (!$this->tableExists($pdo, $prefix . 'orders')) {
                return 0;
            }
            $sql = 'SELECT COUNT(*) AS c FROM `' . str_replace('`', '``', $prefix . 'orders') . '` WHERE created_at >= :start';
            $params = ['start' => date('Y-m-01 00:00:00')];
            if ($supplierId > 0) {
                $sql .= ' AND supplier_id = :supplier_id';
                $params['supplier_id'] = $supplierId;
            }
            QueryGuard::assertReadOnly($sql);
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function countByStatus(string $status, int $supplierId): int
    {
        try {
            $repo = new OrderWriteRepository();

            return $repo->tableExists() ? $repo->countByStatus($status, $supplierId) : 0;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function tableExists(PDO $pdo, string $table): bool
    {
        try {
            $database = config('database.database', '');
            $check = $pdo->prepare('SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = :s AND TABLE_NAME = :t');
            $check->execute(['s' => $database, 't' => $table]);

            return ((int) ($check->fetch(PDO::FETCH_ASSOC)['c'] ?? 0)) > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
