<?php

namespace App\Services\ReadOnly;

use App\ActivityLog;
use App\Database\Connection;
use App\Database\QueryGuard;
use App\Repositories\Write\OrderWriteRepository;
use PDO;

class DashboardReadService
{
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
            $task['count'] = count($repo->findByStatus($task['status'], 50));
        }
        unset($task);

        $pendingReturns = 0;
        try {
            $pendingReturns = count($repo->findReturnPending('hub_courier_return', 'hub_return', 50))
                + count($repo->findReturnPending('customer_return_to_supplier', 'order_returning', 50));
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
        if ($repo->tableExists()) {
            foreach (['new_order', 'order_received', 'packaging', 'shipped', 'dispatch_report_created', 'delivery_stop', 'hub_return', 'order_returning'] as $status) {
                $activeOrders += count($repo->findByStatus($status, 50));
            }
        }

        return [
            'net_payable' => (float) ($summary['net_payable'] ?? 0),
            'pending_draft_entries' => (int) ($summary['draft_count'] ?? 0),
            'dispatch_snapshot_total' => $dispatchTotal,
            'pending_returns' => $pendingReturns,
            'active_fulfillment_orders' => $activeOrders,
            'sync_status' => 'Not connected — planning only',
        ];
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
