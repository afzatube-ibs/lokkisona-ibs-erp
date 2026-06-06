<?php

namespace App\Services\ReadOnly;

use App\Domain\SupplierTerminology;
use App\Database\Connection;
use App\Database\QueryGuard;
use App\Domain\OrderWorkflowStatus;
use App\Repositories\Write\OrderWriteRepository;
use PDO;

class SupplierDashboardAnalyticsService
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

    public function build(int $supplierId): array
    {
        $metrics = (new DashboardReadService())->supplierDashboardMetrics($supplierId);

        return [
            'hero' => $this->heroFromMetrics($metrics),
            'order_pipeline' => $this->orderPipeline(),
            'growth' => $this->monthlyGrowth($supplierId, 6),
            'payments' => $this->paymentAnalytics($supplierId, 6),
            'products' => $this->productSnapshot($supplierId),
            'action_queue' => (new DashboardReadService())->supplierNeedsAttention($supplierId),
        ];
    }

    private function heroFromMetrics(array $metrics): array
    {
        $pendingActions = (int) ($metrics['new_orders'] ?? 0)
            + (int) ($metrics['pending_returns'] ?? 0)
            + (int) ($metrics['pending_draft_entries'] ?? 0);

        return [
            'net_payable' => (float) ($metrics['net_payable'] ?? 0),
            'active_orders' => (int) ($metrics['active_fulfillment_orders'] ?? 0),
            'offline_sales_mtd' => (float) ($metrics['shop_invoices_month_total'] ?? 0),
            'offline_invoices_mtd' => (int) ($metrics['shop_invoices_month'] ?? 0),
            'pending_actions' => $pendingActions,
            'dispatch_batches_mtd' => (int) ($metrics['dispatch_batches_month'] ?? 0),
        ];
    }

    /**
     * @return array<int, array{label: string, count: int, pct: float}>
     */
    public function orderPipeline(): array
    {
        $repo = new OrderWriteRepository();
        if (!$repo->tableExists()) {
            return [];
        }

        $rows = [];
        $max = 0;
        foreach (self::PIPELINE_STATUSES as $status) {
            $count = $repo->countByStatus($status);
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

        return $rows;
    }

    /**
     * @return array{labels: array<int, string>, orders: array<int, int>, offline_sales: array<int, float>, dispatch_batches: array<int, int>}
     */
    public function monthlyGrowth(int $supplierId, int $months = 6): array
    {
        $labels = $this->lastMonthLabels($months);
        $orders = array_fill(0, $months, 0);
        $offlineSales = array_fill(0, $months, 0.0);
        $dispatchBatches = array_fill(0, $months, 0);

        try {
            $pdo = Connection::pdo();
            $prefix = config('database.prefix', 'ibs_');
            $database = config('database.database', '');
            $check = $pdo->prepare('SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = :s AND TABLE_NAME = :t');

            $check->execute(['s' => $database, 't' => $prefix . 'orders']);
            if (((int) ($check->fetch(PDO::FETCH_ASSOC)['c'] ?? 0)) > 0) {
                $start = $labels[0] . '-01';
                $sql = 'SELECT DATE_FORMAT(created_at, "%Y-%m") AS ym, COUNT(*) AS c FROM `'
                    . str_replace('`', '``', $prefix . 'orders') . '` '
                    . 'WHERE created_at >= :start GROUP BY ym';
                QueryGuard::assertReadOnly($sql);
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['start' => $start . ' 00:00:00']);
                $map = [];
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
                    $map[(string) ($row['ym'] ?? '')] = (int) ($row['c'] ?? 0);
                }
                foreach ($labels as $i => $ym) {
                    $orders[$i] = $map[$ym] ?? 0;
                }
            }

            if ($supplierId > 0) {
                $check->execute(['s' => $database, 't' => $prefix . 'supplier_quick_invoices']);
                if (((int) ($check->fetch(PDO::FETCH_ASSOC)['c'] ?? 0)) > 0) {
                    $start = $labels[0] . '-01';
                    $sql = 'SELECT DATE_FORMAT(COALESCE(generated_at, created_at), "%Y-%m") AS ym, COALESCE(SUM(invoice_total), 0) AS total FROM `'
                        . str_replace('`', '``', $prefix . 'supplier_quick_invoices') . '` '
                        . 'WHERE supplier_id = :supplier_id AND COALESCE(generated_at, created_at) >= :start GROUP BY ym';
                    QueryGuard::assertReadOnly($sql);
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute(['supplier_id' => $supplierId, 'start' => $start . ' 00:00:00']);
                    $map = [];
                    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
                        $map[(string) ($row['ym'] ?? '')] = round((float) ($row['total'] ?? 0), 2);
                    }
                    foreach ($labels as $i => $ym) {
                        $offlineSales[$i] = $map[$ym] ?? 0.0;
                    }
                }

                $check->execute(['s' => $database, 't' => $prefix . 'dispatch_reports']);
                if (((int) ($check->fetch(PDO::FETCH_ASSOC)['c'] ?? 0)) > 0) {
                    $start = $labels[0] . '-01';
                    $sql = 'SELECT DATE_FORMAT(created_at, "%Y-%m") AS ym, COUNT(*) AS c FROM `'
                        . str_replace('`', '``', $prefix . 'dispatch_reports') . '` '
                        . 'WHERE supplier_id = :supplier_id AND created_at >= :start GROUP BY ym';
                    QueryGuard::assertReadOnly($sql);
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute(['supplier_id' => $supplierId, 'start' => $start . ' 00:00:00']);
                    $map = [];
                    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
                        $map[(string) ($row['ym'] ?? '')] = (int) ($row['c'] ?? 0);
                    }
                    foreach ($labels as $i => $ym) {
                        $dispatchBatches[$i] = $map[$ym] ?? 0;
                    }
                }
            }
        } catch (\Throwable $e) {
            // Graceful when DB unavailable.
        }

        return [
            'labels' => array_map(fn (string $ym): string => date('M y', strtotime($ym . '-01')), $labels),
            'orders' => $orders,
            'offline_sales' => $offlineSales,
            'dispatch_batches' => $dispatchBatches,
        ];
    }

    /**
     * @return array{breakdown: array<int, array{label: string, amount: float, tone: string}>, trend: array{labels: array<int, string>, debits: array<int, float>, credits: array<int, float>}, payments_mtd: float, payables_mtd: float}
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

        if ($supplierId <= 0) {
            return [
                'breakdown' => $breakdown,
                'trend' => ['labels' => array_map(fn (string $ym): string => date('M y', strtotime($ym . '-01')), $labels), 'debits' => $debits, 'credits' => $credits],
                'payments_mtd' => 0.0,
                'payables_mtd' => 0.0,
            ];
        }

        $summary = (new PayableLedgerReadService())->summaryForSupplier($supplierId);
        $breakdown[0]['amount'] = (float) ($summary['net_payable'] ?? 0);
        $breakdown[3]['amount'] = (float) ($summary['draft_count'] ?? 0);

        try {
            $pdo = Connection::pdo();
            $prefix = config('database.prefix', 'ibs_');
            $database = config('database.database', '');
            $table = $prefix . 'payable_ledgers';
            $check = $pdo->prepare('SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = :s AND TABLE_NAME = :t');
            $check->execute(['s' => $database, 't' => $table]);
            if (((int) ($check->fetch(PDO::FETCH_ASSOC)['c'] ?? 0)) === 0) {
                return [
                    'breakdown' => $breakdown,
                    'trend' => ['labels' => array_map(fn (string $ym): string => date('M y', strtotime($ym . '-01')), $labels), 'debits' => $debits, 'credits' => $credits],
                    'payments_mtd' => 0.0,
                    'payables_mtd' => 0.0,
                ];
            }

            $monthStart = date('Y-m-01 00:00:00');
            $sql = 'SELECT COALESCE(SUM(credit_amount), 0) AS credits, COALESCE(SUM(debit_amount), 0) AS debits FROM `'
                . str_replace('`', '``', $table) . '` WHERE supplier_id = :sid AND status = :status AND created_at >= :start';
            QueryGuard::assertReadOnly($sql);
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['sid' => $supplierId, 'status' => 'posted', 'start' => $monthStart]);
            $mtd = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            $breakdown[1]['amount'] = round((float) ($mtd['credits'] ?? 0), 2);
            $breakdown[2]['amount'] = round((float) ($mtd['debits'] ?? 0), 2);

            $start = $labels[0] . '-01';
            $sql = 'SELECT DATE_FORMAT(created_at, "%Y-%m") AS ym, COALESCE(SUM(debit_amount), 0) AS deb, COALESCE(SUM(credit_amount), 0) AS cred FROM `'
                . str_replace('`', '``', $table) . '` WHERE supplier_id = :sid AND status = :status AND created_at >= :start GROUP BY ym';
            QueryGuard::assertReadOnly($sql);
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['sid' => $supplierId, 'status' => 'posted', 'start' => $start . ' 00:00:00']);
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
            'payments_mtd' => $breakdown[1]['amount'],
            'payables_mtd' => $breakdown[2]['amount'],
        ];
    }

    /**
     * @return array{total: int, low_stock: int, rows: array<int, array{label: string, stock: int, cost: float}>}
     */
    public function productSnapshot(int $supplierId): array
    {
        $result = ['total' => 0, 'low_stock' => 0, 'rows' => []];
        if ($supplierId <= 0) {
            return $result;
        }

        try {
            $pdo = Connection::pdo();
            $prefix = config('database.prefix', 'ibs_');
            $database = config('database.database', '');
            $table = $prefix . 'products';
            $check = $pdo->prepare('SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = :s AND TABLE_NAME = :t');
            $check->execute(['s' => $database, 't' => $table]);
            if (((int) ($check->fetch(PDO::FETCH_ASSOC)['c'] ?? 0)) === 0) {
                return $result;
            }

            $sql = 'SELECT COUNT(*) AS c FROM `' . str_replace('`', '``', $table) . '` WHERE supplier_id = :sid';
            QueryGuard::assertReadOnly($sql);
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['sid' => $supplierId]);
            $result['total'] = (int) ($stmt->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);

            $sql = 'SELECT COUNT(*) AS c FROM `' . str_replace('`', '``', $table) . '` '
                . 'WHERE supplier_id = :sid AND low_warning_threshold IS NOT NULL AND vendor_stock <= low_warning_threshold';
            QueryGuard::assertReadOnly($sql);
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['sid' => $supplierId]);
            $result['low_stock'] = (int) ($stmt->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);

            $sql = 'SELECT product_name, vendor_stock, product_cost FROM `' . str_replace('`', '``', $table) . '` '
                . 'WHERE supplier_id = :sid ORDER BY vendor_stock DESC, product_id DESC LIMIT 6';
            QueryGuard::assertReadOnly($sql);
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['sid' => $supplierId]);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
                $result['rows'][] = [
                    'label' => (string) ($row['product_name'] ?? 'Product'),
                    'stock' => (int) ($row['vendor_stock'] ?? 0),
                    'cost' => round((float) ($row['product_cost'] ?? 0), 2),
                ];
            }
        } catch (\Throwable $e) {
            // Graceful.
        }

        return $result;
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
