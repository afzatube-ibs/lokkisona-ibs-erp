<?php

namespace App\Services\ReadOnly;

use App\Domain\OrderWorkflowStatus;
use App\Domain\SupplierTerminology;
use App\Database\Connection;
use App\Database\QueryGuard;
use App\Repositories\Write\OrderWriteRepository;
use PDO;

class BusinessDashboardAnalyticsService
{
    private const PIPELINE_STATUSES = [
        'new_order',
        'order_received',
        'packaging',
        'shipped',
        'dispatch_report_created',
        'delivery_stop',
        'hub_return',
        'order_returning',
    ];

    private const ACTIVE_STATUSES = [
        'new_order',
        'order_received',
        'packaging',
        'shipped',
        'dispatch_report_created',
        'delivery_stop',
        'hub_return',
        'order_returning',
    ];

    private const FULFILLED_STATUSES = ['shipped', 'dispatch_report_created'];

    public function build(int $supplierId, bool $showRetailAmounts): array
    {
        $dashboard = new DashboardReadService();
        $metrics = $dashboard->supplierDashboardMetrics($supplierId);

        return [
            'hero' => $this->heroMetrics($supplierId, $metrics, $showRetailAmounts),
            'orders_ratio' => $this->ordersRatio($supplierId),
            'top_products' => $this->topProducts($supplierId, $showRetailAmounts),
            'top_categories' => $this->topCategories($supplierId, $showRetailAmounts),
            'sales_trend' => $this->salesTrend($supplierId, $showRetailAmounts, 6),
            'avg_sale_per_order' => $this->avgSalePerOrder($supplierId),
            'return_rate' => $this->returnRate($supplierId),
            'order_pipeline' => $this->orderPipeline($supplierId),
            'payments' => $this->paymentAnalytics($supplierId, 6),
            'products' => $this->productSnapshot($supplierId),
            'action_queue' => $supplierId > 0
                ? $dashboard->supplierNeedsAttention($supplierId)
                : $dashboard->needsAttention(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function heroMetrics(int $supplierId, array $metrics, bool $showRetailAmounts): array
    {
        $salesMtd = $this->salesMtdTotal($supplierId);
        $priorSalesMtd = $this->salesMtdTotal($supplierId, true);
        $pctChange = $priorSalesMtd > 0
            ? round((($salesMtd - $priorSalesMtd) / $priorSalesMtd) * 100, 1)
            : ($salesMtd > 0 ? 100.0 : 0.0);

        $hero = [
            'sales_mtd' => $salesMtd,
            'sales_mtd_pct_change' => $pctChange,
            'sales_mtd_up' => $salesMtd >= $priorSalesMtd,
            'net_payable' => (float) ($metrics['net_payable'] ?? 0),
            'active_orders' => (int) ($metrics['active_fulfillment_orders'] ?? 0),
            'offline_sales_mtd' => (float) ($metrics['shop_invoices_month_total'] ?? 0),
            'offline_invoices_mtd' => (int) ($metrics['shop_invoices_month'] ?? 0),
            'pending_actions' => (int) ($metrics['new_orders'] ?? 0)
                + (int) ($metrics['pending_returns'] ?? 0)
                + (int) ($metrics['pending_draft_entries'] ?? 0),
            'dispatch_batches_mtd' => (int) ($metrics['dispatch_batches_month'] ?? 0),
            'retail_mtd' => 0.0,
        ];

        if ($showRetailAmounts) {
            $hero['retail_mtd'] = $this->retailMtd($supplierId);
        }

        return $hero;
    }

    private function salesMtdTotal(int $supplierId, bool $priorMonth = false): float
    {
        $start = $priorMonth
            ? date('Y-m-01 00:00:00', strtotime('first day of last month'))
            : date('Y-m-01 00:00:00');
        $end = $priorMonth
            ? date('Y-m-t 23:59:59', strtotime('last day of last month'))
            : null;

        $total = 0.0;

        try {
            $pdo = Connection::pdo();
            $prefix = config('database.prefix', 'ibs_');

            if ($this->tableExists($pdo, $prefix . 'payable_ledgers')) {
                $sql = 'SELECT COALESCE(SUM(debit_amount), 0) AS total FROM `'
                    . str_replace('`', '``', $prefix . 'payable_ledgers') . '` '
                    . 'WHERE status = :status AND created_at >= :start';
                $params = ['status' => 'posted', 'start' => $start];
                if ($end !== null) {
                    $sql .= ' AND created_at <= :end';
                    $params['end'] = $end;
                }
                if ($supplierId > 0) {
                    $sql .= ' AND supplier_id = :supplier_id';
                    $params['supplier_id'] = $supplierId;
                }
                QueryGuard::assertReadOnly($sql);
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $total += (float) ($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
            }

            if ($this->tableExists($pdo, $prefix . 'supplier_quick_invoices')) {
                $sql = 'SELECT COALESCE(SUM(invoice_total), 0) AS total FROM `'
                    . str_replace('`', '``', $prefix . 'supplier_quick_invoices') . '` '
                    . 'WHERE COALESCE(generated_at, created_at) >= :start';
                $params = ['start' => $start];
                if ($end !== null) {
                    $sql .= ' AND COALESCE(generated_at, created_at) <= :end';
                    $params['end'] = $end;
                }
                if ($supplierId > 0) {
                    $sql .= ' AND supplier_id = :supplier_id';
                    $params['supplier_id'] = $supplierId;
                }
                QueryGuard::assertReadOnly($sql);
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $total += (float) ($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
            }
        } catch (\Throwable $e) {
            // Graceful.
        }

        return round($total, 2);
    }

    private function retailMtd(int $supplierId): float
    {
        try {
            $pdo = Connection::pdo();
            $prefix = config('database.prefix', 'ibs_');
            if (!$this->tableExists($pdo, $prefix . 'orders')) {
                return 0.0;
            }

            $sql = 'SELECT COALESCE(SUM(order_total), 0) AS total FROM `'
                . str_replace('`', '``', $prefix . 'orders') . '` '
                . 'WHERE created_at >= :start';
            $params = ['start' => date('Y-m-01 00:00:00')];
            if ($supplierId > 0) {
                $sql .= ' AND supplier_id = :supplier_id';
                $params['supplier_id'] = $supplierId;
            }
            QueryGuard::assertReadOnly($sql);
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            return round((float) ($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0), 2);
        } catch (\Throwable $e) {
            return 0.0;
        }
    }

    /**
     * @return array{fulfilled: int, active: int, pct: float}
     */
    private function ordersRatio(int $supplierId): array
    {
        $repo = new OrderWriteRepository();
        $fulfilled = $repo->countByStatuses(self::FULFILLED_STATUSES, $supplierId);
        $active = $repo->countByStatuses(self::ACTIVE_STATUSES, $supplierId);

        return [
            'fulfilled' => $fulfilled,
            'active' => $active,
            'pct' => $active > 0 ? round(($fulfilled / $active) * 100, 1) : 0.0,
        ];
    }

    /**
     * @return array<int, array{product: string, category: string, qty: int, sale_bdt: float, retail_bdt: float}>
     */
    private function topProducts(int $supplierId, bool $showRetailAmounts): array
    {
        try {
            $pdo = Connection::pdo();
            $prefix = config('database.prefix', 'ibs_');
            if (!$this->tableExists($pdo, $prefix . 'order_items') || !$this->tableExists($pdo, $prefix . 'orders')) {
                return [];
            }

            $categorySelect = $this->hasProductCategoryColumn($pdo, $prefix)
                ? "COALESCE(NULLIF(p.supplier_product_category, ''), 'Uncategorized')"
                : "'Uncategorized'";

            $sql = 'SELECT COALESCE(p.product_name, CONCAT("Product #", oi.product_id)) AS product_name, '
                . $categorySelect . ' AS category, '
                . 'COALESCE(SUM(oi.quantity), 0) AS qty, '
                . 'COALESCE(SUM(oi.supplier_cost_snapshot * oi.quantity), 0) AS sale_bdt';
            if ($showRetailAmounts) {
                $sql .= ', COALESCE(SUM(oi.selling_price * oi.quantity), 0) AS retail_bdt';
            }
            $sql .= ' FROM `' . str_replace('`', '``', $prefix . 'order_items') . '` oi '
                . 'INNER JOIN `' . str_replace('`', '``', $prefix . 'orders') . '` o ON o.order_id = oi.order_id '
                . 'LEFT JOIN `' . str_replace('`', '``', $prefix . 'products') . '` p ON p.product_id = oi.product_id '
                . 'WHERE oi.product_id IS NOT NULL';
            $params = [];
            if ($supplierId > 0) {
                $sql .= ' AND o.supplier_id = :supplier_id';
                $params['supplier_id'] = $supplierId;
            }
            $sql .= ' GROUP BY oi.product_id, product_name, category ORDER BY sale_bdt DESC LIMIT 10';
            QueryGuard::assertReadOnly($sql);
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            $rows = [];
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
                $item = [
                    'product' => (string) ($row['product_name'] ?? ''),
                    'category' => (string) ($row['category'] ?? 'Uncategorized'),
                    'qty' => (int) ($row['qty'] ?? 0),
                    'sale_bdt' => round((float) ($row['sale_bdt'] ?? 0), 2),
                    'retail_bdt' => 0.0,
                ];
                if ($showRetailAmounts) {
                    $item['retail_bdt'] = round((float) ($row['retail_bdt'] ?? 0), 2);
                }
                $rows[] = $item;
            }

            return $rows;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * @return array<int, array{category: string, orders: int, sale_bdt: float, retail_bdt: float}>
     */
    private function topCategories(int $supplierId, bool $showRetailAmounts): array
    {
        try {
            $pdo = Connection::pdo();
            $prefix = config('database.prefix', 'ibs_');
            if (!$this->tableExists($pdo, $prefix . 'order_items') || !$this->tableExists($pdo, $prefix . 'orders')) {
                return [];
            }

            $categorySelect = $this->hasProductCategoryColumn($pdo, $prefix)
                ? "COALESCE(NULLIF(p.supplier_product_category, ''), 'Uncategorized')"
                : "'Uncategorized'";

            $sql = 'SELECT ' . $categorySelect . ' AS category, '
                . 'COUNT(DISTINCT o.order_id) AS orders, '
                . 'COALESCE(SUM(oi.supplier_cost_snapshot * oi.quantity), 0) AS sale_bdt';
            if ($showRetailAmounts) {
                $sql .= ', COALESCE(SUM(oi.selling_price * oi.quantity), 0) AS retail_bdt';
            }
            $sql .= ' FROM `' . str_replace('`', '``', $prefix . 'order_items') . '` oi '
                . 'INNER JOIN `' . str_replace('`', '``', $prefix . 'orders') . '` o ON o.order_id = oi.order_id '
                . 'LEFT JOIN `' . str_replace('`', '``', $prefix . 'products') . '` p ON p.product_id = oi.product_id '
                . 'WHERE 1=1';
            $params = [];
            if ($supplierId > 0) {
                $sql .= ' AND o.supplier_id = :supplier_id';
                $params['supplier_id'] = $supplierId;
            }
            $sql .= ' GROUP BY category ORDER BY sale_bdt DESC LIMIT 10';
            QueryGuard::assertReadOnly($sql);
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            $rows = [];
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
                $item = [
                    'category' => (string) ($row['category'] ?? 'Uncategorized'),
                    'orders' => (int) ($row['orders'] ?? 0),
                    'sale_bdt' => round((float) ($row['sale_bdt'] ?? 0), 2),
                    'retail_bdt' => 0.0,
                ];
                if ($showRetailAmounts) {
                    $item['retail_bdt'] = round((float) ($row['retail_bdt'] ?? 0), 2);
                }
                $rows[] = $item;
            }

            return $rows;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * @return array{labels: array<int, string>, sale: array<int, float>, retail: array<int, float>}
     */
    private function salesTrend(int $supplierId, bool $showRetailAmounts, int $weeks = 6): array
    {
        $labels = [];
        $sale = [];
        $retail = [];
        for ($i = $weeks - 1; $i >= 0; $i--) {
            $weekStart = date('Y-m-d', strtotime('-' . $i . ' weeks monday this week'));
            $labels[] = date('d M', strtotime($weekStart));
            $sale[] = 0.0;
            $retail[] = 0.0;
        }

        try {
            $pdo = Connection::pdo();
            $prefix = config('database.prefix', 'ibs_');
            $rangeStart = date('Y-m-d 00:00:00', strtotime('-' . ($weeks - 1) . ' weeks monday this week'));

            if ($this->tableExists($pdo, $prefix . 'orders')) {
                $sql = 'SELECT YEARWEEK(created_at, 1) AS yw, COALESCE(SUM(cost_snapshot_total), 0) AS sale_total';
                if ($showRetailAmounts) {
                    $sql .= ', COALESCE(SUM(order_total), 0) AS retail_total';
                }
                $sql .= ' FROM `' . str_replace('`', '``', $prefix . 'orders') . '` WHERE created_at >= :start';
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
                $retailMap = [];
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
                    $map[(int) ($row['yw'] ?? 0)] = round((float) ($row['sale_total'] ?? 0), 2);
                    if ($showRetailAmounts) {
                        $retailMap[(int) ($row['yw'] ?? 0)] = round((float) ($row['retail_total'] ?? 0), 2);
                    }
                }
                for ($i = 0; $i < $weeks; $i++) {
                    $yw = (int) date('oW', strtotime('-' . ($weeks - 1 - $i) . ' weeks monday this week'));
                    $sale[$i] = $map[$yw] ?? 0.0;
                    if ($showRetailAmounts) {
                        $retail[$i] = $retailMap[$yw] ?? 0.0;
                    }
                }
            }

            if ($this->tableExists($pdo, $prefix . 'supplier_quick_invoices')) {
                $sql = 'SELECT YEARWEEK(COALESCE(generated_at, created_at), 1) AS yw, COALESCE(SUM(invoice_total), 0) AS total '
                    . 'FROM `' . str_replace('`', '``', $prefix . 'supplier_quick_invoices') . '` '
                    . 'WHERE COALESCE(generated_at, created_at) >= :start';
                $params = ['start' => $rangeStart];
                if ($supplierId > 0) {
                    $sql .= ' AND supplier_id = :supplier_id';
                    $params['supplier_id'] = $supplierId;
                }
                $sql .= ' GROUP BY yw';
                QueryGuard::assertReadOnly($sql);
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
                    $yw = (int) ($row['yw'] ?? 0);
                    for ($i = 0; $i < $weeks; $i++) {
                        if ((int) date('oW', strtotime('-' . ($weeks - 1 - $i) . ' weeks monday this week')) === $yw) {
                            $sale[$i] += round((float) ($row['total'] ?? 0), 2);
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            // Graceful.
        }

        return ['labels' => $labels, 'sale' => $sale, 'retail' => $retail];
    }

    private function avgSalePerOrder(int $supplierId): float
    {
        try {
            $pdo = Connection::pdo();
            $prefix = config('database.prefix', 'ibs_');
            if (!$this->tableExists($pdo, $prefix . 'orders')) {
                return 0.0;
            }

            $sql = 'SELECT COALESCE(AVG(cost_snapshot_total), 0) AS avg_sale FROM `'
                . str_replace('`', '``', $prefix . 'orders') . '` WHERE cost_snapshot_total > 0';
            $params = [];
            if ($supplierId > 0) {
                $sql .= ' AND supplier_id = :supplier_id';
                $params['supplier_id'] = $supplierId;
            }
            QueryGuard::assertReadOnly($sql);
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            return round((float) ($stmt->fetch(PDO::FETCH_ASSOC)['avg_sale'] ?? 0), 2);
        } catch (\Throwable $e) {
            return 0.0;
        }
    }

    /**
     * @return array{pct: float, returns: int, base: int}
     */
    private function returnRate(int $supplierId): array
    {
        $returns = 0;
        $base = 0;

        try {
            $pdo = Connection::pdo();
            $prefix = config('database.prefix', 'ibs_');
            if ($this->tableExists($pdo, $prefix . 'return_receives')) {
                $sql = 'SELECT COUNT(*) AS c FROM `' . str_replace('`', '``', $prefix . 'return_receives') . '` WHERE 1=1';
                $params = [];
                if ($supplierId > 0) {
                    $sql .= ' AND supplier_id = :supplier_id';
                    $params['supplier_id'] = $supplierId;
                }
                QueryGuard::assertReadOnly($sql);
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $returns = (int) ($stmt->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);
            }

            $repo = new OrderWriteRepository();
            $base = $repo->countByStatuses(['shipped', 'dispatch_report_created', 'delivered'], $supplierId);
            if ($base === 0) {
                $base = $repo->countByStatuses(self::ACTIVE_STATUSES, $supplierId);
            }
        } catch (\Throwable $e) {
            // Graceful.
        }

        return [
            'returns' => $returns,
            'base' => $base,
            'pct' => $base > 0 ? round(($returns / $base) * 100, 1) : 0.0,
        ];
    }

    /**
     * @return array<int, array{label: string, count: int, pct: float}>
     */
    public function orderPipeline(int $supplierId): array
    {
        $repo = new OrderWriteRepository();
        if (!$repo->tableExists()) {
            return [];
        }

        $rows = [];
        $max = 0;
        foreach (self::PIPELINE_STATUSES as $status) {
            $count = $repo->countByStatus($status, $supplierId);
            $max = max($max, $count);
            $rows[] = [
                'status' => $status,
                'label' => OrderWorkflowStatus::groupDisplayLabel($status),
                'count' => $count,
                'pct' => 0.0,
            ];
        }

        foreach ($rows as &$row) {
            $row['pct'] = $max > 0 ? round(($row['count'] / $max) * 100, 1) : 0.0;
        }
        unset($row);

        return array_values(array_filter($rows, static fn (array $r): bool => $r['count'] > 0));
    }

    /**
     * @return array{breakdown: array<int, array{label: string, amount: float, tone: string}>, trend: array{labels: array<int, string>, debits: array<int, float>, credits: array<int, float>}}
     */
    public function paymentAnalytics(int $supplierId, int $months = 6): array
    {
        $breakdown = [
            ['label' => 'Net balance', 'amount' => 0.0, 'tone' => 'primary'],
            ['label' => 'Payments received (MTD)', 'amount' => 0.0, 'tone' => 'success'],
            ['label' => SupplierTerminology::salesMtd(), 'amount' => 0.0, 'tone' => 'warn'],
            ['label' => 'Pending drafts', 'amount' => 0.0, 'tone' => 'muted'],
        ];
        $labels = $this->lastMonthLabels($months);
        $debits = array_fill(0, $months, 0.0);
        $credits = array_fill(0, $months, 0.0);

        $ledger = new PayableLedgerReadService();
        $summary = $supplierId > 0 ? $ledger->summaryForSupplier($supplierId) : $ledger->summary();
        $breakdown[0]['amount'] = (float) ($summary['net_payable'] ?? 0);
        $breakdown[3]['amount'] = (float) ($summary['draft_count'] ?? 0);

        try {
            $pdo = Connection::pdo();
            $prefix = config('database.prefix', 'ibs_');
            if (!$this->tableExists($pdo, $prefix . 'payable_ledgers')) {
                return [
                    'breakdown' => $breakdown,
                    'trend' => [
                        'labels' => array_map(fn (string $ym): string => date('M y', strtotime($ym . '-01')), $labels),
                        'debits' => $debits,
                        'credits' => $credits,
                    ],
                ];
            }

            $monthStart = date('Y-m-01 00:00:00');
            $sql = 'SELECT COALESCE(SUM(credit_amount), 0) AS credits, COALESCE(SUM(debit_amount), 0) AS debits FROM `'
                . str_replace('`', '``', $prefix . 'payable_ledgers') . '` WHERE status = :status AND created_at >= :start';
            $params = ['status' => 'posted', 'start' => $monthStart];
            if ($supplierId > 0) {
                $sql .= ' AND supplier_id = :supplier_id';
                $params['supplier_id'] = $supplierId;
            }
            QueryGuard::assertReadOnly($sql);
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $mtd = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            $breakdown[1]['amount'] = round((float) ($mtd['credits'] ?? 0), 2);
            $breakdown[2]['amount'] = round((float) ($mtd['debits'] ?? 0), 2);

            $start = $labels[0] . '-01';
            $sql = 'SELECT DATE_FORMAT(created_at, "%Y-%m") AS ym, COALESCE(SUM(debit_amount), 0) AS deb, COALESCE(SUM(credit_amount), 0) AS cred FROM `'
                . str_replace('`', '``', $prefix . 'payable_ledgers') . '` WHERE status = :status AND created_at >= :start';
            $params = ['status' => 'posted', 'start' => $start . ' 00:00:00'];
            if ($supplierId > 0) {
                $sql .= ' AND supplier_id = :supplier_id';
                $params['supplier_id'] = $supplierId;
            }
            $sql .= ' GROUP BY ym';
            QueryGuard::assertReadOnly($sql);
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $debitMap = [];
            $creditMap = [];
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
                $ym = (string) ($row['ym'] ?? '');
                $debitMap[$ym] = round((float) ($row['deb'] ?? 0), 2);
                $creditMap[$ym] = round((float) ($row['cred'] ?? 0), 2);
            }
            foreach ($labels as $i => $ym) {
                $debits[$i] = $debitMap[$ym] ?? 0.0;
                $credits[$i] = $creditMap[$ym] ?? 0.0;
            }
        } catch (\Throwable $e) {
            // Graceful.
        }

        return [
            'breakdown' => $breakdown,
            'trend' => [
                'labels' => array_map(fn (string $ym): string => date('M y', strtotime($ym . '-01')), $labels),
                'debits' => $debits,
                'credits' => $credits,
            ],
        ];
    }

    /**
     * @return array{total: int, low_stock: int, rows: array<int, array{label: string, stock: int, cost: float, category: string}>}
     */
    public function productSnapshot(int $supplierId): array
    {
        $result = ['total' => 0, 'low_stock' => 0, 'rows' => []];

        try {
            $pdo = Connection::pdo();
            $prefix = config('database.prefix', 'ibs_');
            $table = $prefix . 'products';
            if (!$this->tableExists($pdo, $table)) {
                return $result;
            }

            $where = $supplierId > 0 ? 'WHERE supplier_id = :sid' : '';
            $params = $supplierId > 0 ? ['sid' => $supplierId] : [];

            $sql = 'SELECT COUNT(*) AS c FROM `' . str_replace('`', '``', $table) . '` ' . $where;
            QueryGuard::assertReadOnly($sql);
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $result['total'] = (int) ($stmt->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);

            $sql = 'SELECT COUNT(*) AS c FROM `' . str_replace('`', '``', $table) . '` '
                . ($where !== '' ? $where . ' AND ' : 'WHERE ')
                . 'low_warning_threshold IS NOT NULL AND vendor_stock <= low_warning_threshold';
            QueryGuard::assertReadOnly($sql);
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $result['low_stock'] = (int) ($stmt->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);

            $categoryCol = $this->hasProductCategoryColumn($pdo, $prefix)
                ? 'supplier_product_category'
                : 'NULL AS supplier_product_category';
            $sql = 'SELECT product_name, vendor_stock, product_cost, ' . $categoryCol . ' FROM `'
                . str_replace('`', '``', $table) . '` ' . $where
                . ' ORDER BY vendor_stock DESC, product_id DESC LIMIT 6';
            QueryGuard::assertReadOnly($sql);
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
                $result['rows'][] = [
                    'label' => (string) ($row['product_name'] ?? 'Product'),
                    'stock' => (int) ($row['vendor_stock'] ?? 0),
                    'cost' => round((float) ($row['product_cost'] ?? 0), 2),
                    'category' => trim((string) ($row['supplier_product_category'] ?? '')) ?: '—',
                ];
            }
        } catch (\Throwable $e) {
            // Graceful.
        }

        return $result;
    }

    private function hasProductCategoryColumn(PDO $pdo, string $prefix): bool
    {
        try {
            $database = config('database.database', '');
            $check = $pdo->prepare(
                'SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.COLUMNS '
                . 'WHERE TABLE_SCHEMA = :s AND TABLE_NAME = :t AND COLUMN_NAME = :c'
            );
            $check->execute([
                's' => $database,
                't' => $prefix . 'products',
                'c' => 'supplier_product_category',
            ]);

            return ((int) ($check->fetch(PDO::FETCH_ASSOC)['c'] ?? 0)) > 0;
        } catch (\Throwable $e) {
            return false;
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

    /**
     * @return array<int, string>
     */
    private function lastMonthLabels(int $months): array
    {
        $labels = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $labels[] = date('Y-m', strtotime('-' . $i . ' months'));
        }

        return $labels;
    }
}
