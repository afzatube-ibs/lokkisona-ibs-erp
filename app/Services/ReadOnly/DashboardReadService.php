<?php

namespace App\Services\ReadOnly;

use App\ActivityLog;
use App\Database\Connection;
use App\Database\QueryGuard;
use App\Domain\OrderWorkflowStatus;
use App\Repositories\Write\OrderWriteRepository;
use PDO;

class DashboardReadService
{
    private const ACTIVE_FULFILLMENT_STATUSES = [
        'new_order',
        'order_received',
        'packaging',
        'shipped',
        'dispatch_report_created',
        'delivery_stop',
        'hub_return',
        'order_returning',
    ];

    public function supplierTaskCounts(): array
    {
        $repo = new OrderWriteRepository();
        if (!$repo->tableExists()) {
            return $this->emptySupplierTasks();
        }

        $tasks = [
            ['key' => 'new_order', 'label' => 'New Orders', 'status' => 'new_order', 'path' => '/order-workflow?status=new_order'],
            ['key' => 'order_received', 'label' => 'Order Received', 'status' => 'order_received', 'path' => '/order-workflow?status=order_received'],
            ['key' => 'packaging', 'label' => 'Packaging', 'status' => 'packaging', 'path' => '/order-workflow?status=packaging'],
            ['key' => 'shipped', 'label' => 'Shipped', 'status' => 'shipped', 'path' => '/order-workflow?status=shipped'],
            ['key' => 'dispatch_report_created', 'label' => 'Created Report', 'status' => 'dispatch_report_created', 'path' => '/order-workflow?status=dispatch_report_created'],
            ['key' => 'delivery_stop', 'label' => 'Delivery Stop', 'status' => 'delivery_stop', 'path' => '/order-workflow?status=delivery_stop'],
            ['key' => 'hub_return', 'label' => 'Hub Return', 'status' => 'hub_return', 'path' => '/order-workflow?status=hub_return'],
            ['key' => 'order_returning', 'label' => 'Customer Return to Supplier', 'status' => 'order_returning', 'path' => '/order-workflow?status=order_returning'],
        ];

        foreach ($tasks as &$task) {
            $task['count'] = $repo->countByStatus($task['status']);
        }
        unset($task);

        $pendingReturns = 0;
        try {
            $pendingReturns = $repo->countReturnPending('hub_courier_return', 'hub_return')
                + $repo->countReturnPending('customer_return_to_supplier', 'order_returning');
        } catch (\Throwable $e) {
            $pendingReturns = 0;
        }

        $tasks[] = [
            'key' => 'pending_return',
            'label' => 'Pending Return Receive',
            'status' => '',
            'path' => '/return-receive',
            'count' => $pendingReturns,
        ];

        return $tasks;
    }

    public function supplierDashboardMetrics(int $supplierId): array
    {
        $repo = new OrderWriteRepository();
        $ledger = new PayableLedgerReadService();
        $ledgerSummary = $supplierId > 0 ? $ledger->summaryForSupplier($supplierId) : $ledger->summary();

        $activeOrders = 0;
        $newOrders = 0;
        $packaging = 0;
        $shipped = 0;
        $pendingReturns = 0;

        if ($repo->tableExists()) {
            $activeOrders = $repo->countByStatuses(self::ACTIVE_FULFILLMENT_STATUSES, $supplierId);
            $newOrders = $repo->countByStatus('new_order', $supplierId);
            $packaging = $repo->countByStatus('packaging', $supplierId);
            $shipped = $repo->countByStatus('shipped', $supplierId);
            try {
                $pendingReturns = $repo->countReturnPending('hub_courier_return', 'hub_return')
                    + $repo->countReturnPending('customer_return_to_supplier', 'order_returning');
            } catch (\Throwable $e) {
                $pendingReturns = 0;
            }
        }

        $dispatchBatchesMonth = 0;
        $shopInvoiceCount = 0;
        $shopInvoiceTotal = 0.0;

        try {
            $pdo = Connection::pdo();
            $prefix = config('database.prefix', 'ibs_');
            $database = config('database.database', '');
            $check = $pdo->prepare('SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = :s AND TABLE_NAME = :t');

            $check->execute(['s' => $database, 't' => $prefix . 'dispatch_reports']);
            if (((int) ($check->fetch(PDO::FETCH_ASSOC)['c'] ?? 0)) > 0) {
                $sql = 'SELECT COUNT(*) AS c FROM `' . str_replace('`', '``', $prefix . 'dispatch_reports') . '` '
                    . 'WHERE created_at >= :month_start';
                $dispatchParams = ['month_start' => date('Y-m-01 00:00:00')];
                if ($supplierId > 0) {
                    $sql .= ' AND supplier_id = :supplier_id';
                    $dispatchParams['supplier_id'] = $supplierId;
                }
                QueryGuard::assertReadOnly($sql);
                $stmt = $pdo->prepare($sql);
                $stmt->execute($dispatchParams);
                $dispatchBatchesMonth = (int) ($stmt->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);
            }

            $check->execute(['s' => $database, 't' => $prefix . 'supplier_quick_invoices']);
            if (((int) ($check->fetch(PDO::FETCH_ASSOC)['c'] ?? 0)) > 0) {
                $sql = 'SELECT COUNT(*) AS c, COALESCE(SUM(invoice_total), 0) AS total FROM `'
                    . str_replace('`', '``', $prefix . 'supplier_quick_invoices') . '` '
                    . 'WHERE created_at >= :month_start';
                $invoiceParams = ['month_start' => date('Y-m-01 00:00:00')];
                if ($supplierId > 0) {
                    $sql .= ' AND supplier_id = :supplier_id';
                    $invoiceParams['supplier_id'] = $supplierId;
                }
                QueryGuard::assertReadOnly($sql);
                $stmt = $pdo->prepare($sql);
                $stmt->execute($invoiceParams);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $shopInvoiceCount = (int) ($row['c'] ?? 0);
                $shopInvoiceTotal = round((float) ($row['total'] ?? 0), 2);
            }
        } catch (\Throwable $e) {
            // Graceful when DB unavailable.
        }

        return [
            'active_fulfillment_orders' => $activeOrders,
            'new_orders' => $newOrders,
            'packaging_orders' => $packaging,
            'shipped_orders' => $shipped,
            'pending_returns' => $pendingReturns,
            'net_payable' => (float) ($ledgerSummary['net_payable'] ?? 0),
            'pending_draft_entries' => (int) ($ledgerSummary['draft_count'] ?? 0),
            'dispatch_batches_month' => $dispatchBatchesMonth,
            'shop_invoices_month' => $shopInvoiceCount,
            'shop_invoices_month_total' => $shopInvoiceTotal,
        ];
    }

    /**
     * @return array<int, array{label: string, count: int, url: string, tone: string}>
     */
    public function supplierNeedsAttention(int $supplierId): array
    {
        $metrics = $this->supplierDashboardMetrics($supplierId);
        $items = [];

        if ((int) ($metrics['new_orders'] ?? 0) > 0) {
            $items[] = [
                'label' => 'New orders awaiting action',
                'count' => (int) $metrics['new_orders'],
                'url' => '/order-workflow?status=new_order',
                'tone' => 'primary',
            ];
        }

        if ((int) ($metrics['pending_returns'] ?? 0) > 0) {
            $items[] = [
                'label' => 'Returns pending receive',
                'count' => (int) $metrics['pending_returns'],
                'url' => '/return-receive',
                'tone' => 'info',
            ];
        }

        if ((int) ($metrics['pending_draft_entries'] ?? 0) > 0) {
            $items[] = [
                'label' => 'Ledger drafts awaiting owner post',
                'count' => (int) $metrics['pending_draft_entries'],
                'url' => '/supplier-payables',
                'tone' => 'warn',
            ];
        }

        if ((int) ($metrics['shipped_orders'] ?? 0) > 0) {
            $items[] = [
                'label' => 'Shipped orders in pipeline',
                'count' => (int) $metrics['shipped_orders'],
                'url' => '/order-workflow?status=shipped',
                'tone' => 'primary',
            ];
        }

        return $items;
    }

    public function ownerMetrics(): array
    {
        $ledger = new PayableLedgerReadService();
        $summary = $ledger->summary();

        $dispatchTotal = 0.0;
        $pendingReturns = 0;

        try {
            $pdo = Connection::pdo();
            $prefix = config('database.prefix', 'ibs_');
            $database = config('database.database', '');

            $check = $pdo->prepare('SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = :s AND TABLE_NAME = :t');
            $check->execute(['s' => $database, 't' => $prefix . 'dispatch_reports']);
            if (((int) ($check->fetch(PDO::FETCH_ASSOC)['c'] ?? 0)) > 0) {
                $sql = 'SELECT COALESCE(SUM(total_product_cost), 0) AS total FROM `' . str_replace('`', '``', $prefix . 'dispatch_reports') . '`';
                QueryGuard::assertReadOnly($sql);
                $row = $pdo->query($sql)->fetch(PDO::FETCH_ASSOC);
                $dispatchTotal = round((float) ($row['total'] ?? 0), 2);
            }

            $check->execute(['s' => $database, 't' => $prefix . 'return_receives']);
            if (((int) ($check->fetch(PDO::FETCH_ASSOC)['c'] ?? 0)) > 0) {
                $sql = 'SELECT COUNT(*) AS c FROM `' . str_replace('`', '``', $prefix . 'return_receives') . '` WHERE status != :status';
                QueryGuard::assertReadOnly($sql);
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['status' => 'received']);
                $pendingReturns = (int) ($stmt->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);
            }
        } catch (\Throwable $e) {
            // Graceful when DB unavailable.
        }

        $repo = new OrderWriteRepository();
        $activeOrders = 0;
        $shippedAwaitingDispatch = 0;
        if ($repo->tableExists()) {
            $activeOrders = $repo->countByStatuses(self::ACTIVE_FULFILLMENT_STATUSES);
            $shippedAwaitingDispatch = $repo->countByStatus('shipped');
        }

        return [
            'net_payable' => (float) ($summary['net_payable'] ?? 0),
            'pending_draft_entries' => (int) ($summary['draft_count'] ?? 0),
            'dispatch_snapshot_total' => $dispatchTotal,
            'pending_returns' => $pendingReturns,
            'active_fulfillment_orders' => $activeOrders,
            'shipped_awaiting_dispatch' => $shippedAwaitingDispatch,
        ];
    }

    /**
     * @return array<int, array{status: string, label: string, count: int, url: string}>
     */
    public function workflowStageCounts(): array
    {
        $repo = new OrderWriteRepository();
        if (!$repo->tableExists()) {
            return [];
        }

        $rows = [];
        foreach (self::ACTIVE_FULFILLMENT_STATUSES as $status) {
            $count = $repo->countByStatus($status);
            if ($count === 0) {
                continue;
            }
            $rows[] = [
                'status' => $status,
                'label' => OrderWorkflowStatus::groupDisplayLabel($status),
                'count' => $count,
                'url' => '/order-workflow?status=' . rawurlencode($status),
            ];
        }

        return $rows;
    }

    /**
     * @return array<int, array{label: string, count: int, url: string, tone: string}>
     */
    public function needsAttention(): array
    {
        $metrics = $this->ownerMetrics();
        $items = [];

        if ((int) ($metrics['shipped_awaiting_dispatch'] ?? 0) > 0) {
            $items[] = [
                'label' => 'Shipped — awaiting dispatch report',
                'count' => (int) $metrics['shipped_awaiting_dispatch'],
                'url' => '/dispatch-reports',
                'tone' => 'warn',
            ];
        }

        if ((int) ($metrics['pending_draft_entries'] ?? 0) > 0) {
            $items[] = [
                'label' => 'Payable drafts pending approval',
                'count' => (int) $metrics['pending_draft_entries'],
                'url' => '/supplier-payables',
                'tone' => 'warn',
            ];
        }

        if ((int) ($metrics['pending_returns'] ?? 0) > 0) {
            $items[] = [
                'label' => 'Returns pending receive / review',
                'count' => (int) $metrics['pending_returns'],
                'url' => '/return-receive',
                'tone' => 'info',
            ];
        }

        if ((int) ($metrics['active_fulfillment_orders'] ?? 0) > 0) {
            $items[] = [
                'label' => 'Active fulfillment orders',
                'count' => (int) $metrics['active_fulfillment_orders'],
                'url' => '/order-workflow',
                'tone' => 'primary',
            ];
        }

        return $items;
    }

    public function recentNotes(): array
    {
        $entries = ActivityLog::recent(8);
        $notes = [];
        foreach ($entries as $entry) {
            if (!in_array($entry['event'] ?? '', ['workflow_action', 'return_receive_confirmed', 'dispatch_report_created', 'payable_ledger_approved'], true)) {
                continue;
            }
            $notes[] = [
                'time' => (string) ($entry['timestamp'] ?? ''),
                'text' => (string) ($entry['message'] ?? ''),
            ];
        }

        return array_slice($notes, 0, 5);
    }

    private function emptySupplierTasks(): array
    {
        return [
            ['key' => 'new_order', 'label' => 'New Orders', 'count' => 0, 'path' => '/order-workflow?status=new_order'],
            ['key' => 'order_received', 'label' => 'Order Received', 'count' => 0, 'path' => '/order-workflow?status=order_received'],
            ['key' => 'packaging', 'label' => 'Packaging', 'count' => 0, 'path' => '/order-workflow?status=packaging'],
            ['key' => 'shipped', 'label' => 'Shipped', 'count' => 0, 'path' => '/order-workflow?status=shipped'],
            ['key' => 'pending_return', 'label' => 'Pending Return Receive', 'count' => 0, 'path' => '/return-receive'],
        ];
    }
}
