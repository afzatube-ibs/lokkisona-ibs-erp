<?php

namespace App\Repositories;

use App\Models\DispatchReport;

class DispatchReportRepository extends BaseReadOnlyRepository
{
    public function modelClass(): string
    {
        return DispatchReport::class;
    }

    public function latest(int $limit = 20): array
    {
        if (!$this->tableExists()) {
            return [];
        }

        try {
            $limit = max(1, min($limit, 50));
            $table = \App\Database\TableName::forModel(DispatchReport::class);
            $primaryKey = DispatchReport::primaryKey();
            $sql = 'SELECT * FROM `' . $this->escapeIdentifier($table) . '` '
                . 'ORDER BY created_at DESC, `' . $this->escapeIdentifier($primaryKey) . '` DESC LIMIT ' . $limit;
            \App\Database\QueryGuard::assertReadOnly($sql);
            $statement = $this->pdo->query($sql);

            return $statement ? ($statement->fetchAll(\PDO::FETCH_ASSOC) ?: []) : [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * @return array<int, string> order_id => dispatch_reference
     */
    public function findIncludedOrderReferences(int $limit = 50): array
    {
        if (!$this->tableExists()) {
            return [];
        }

        $itemsTable = config('database.prefix', 'ibs_') . 'dispatch_report_items';
        $reportsTable = config('database.prefix', 'ibs_') . 'dispatch_reports';
        $database = config('database.database', '');

        try {
            $check = $this->pdo->prepare(
                'SELECT COUNT(*) AS table_count FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = :table'
            );

            foreach ([$itemsTable, $reportsTable] as $tableName) {
                $check->execute(['schema' => $database, 'table' => $tableName]);
                $row = $check->fetch(\PDO::FETCH_ASSOC);
                if (((int) ($row['table_count'] ?? 0)) === 0) {
                    return [];
                }
            }

            $limit = max(1, min($limit, 50));
            $sql = 'SELECT i.order_id, r.dispatch_reference '
                . 'FROM `' . $this->escapeIdentifier($itemsTable) . '` i '
                . 'INNER JOIN `' . $this->escapeIdentifier($reportsTable) . '` r ON r.dispatch_report_id = i.dispatch_report_id '
                . 'WHERE i.order_id IS NOT NULL AND i.status = :item_status '
                . 'ORDER BY i.dispatch_report_item_id DESC LIMIT ' . $limit;
            \App\Database\QueryGuard::assertReadOnly($sql);
            $statement = $this->pdo->prepare($sql);
            $statement->execute(['item_status' => 'included']);
            $rows = $statement->fetchAll(\PDO::FETCH_ASSOC) ?: [];
            $map = [];

            foreach ($rows as $row) {
                $orderId = (int) ($row['order_id'] ?? 0);
                if ($orderId > 0 && !isset($map[$orderId])) {
                    $map[$orderId] = (string) ($row['dispatch_reference'] ?? '');
                }
            }

            return $map;
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function findItemsWithOrders(int $dispatchReportId): array
    {
        if (!$this->tableExists() || $dispatchReportId <= 0) {
            return [];
        }

        $itemsTable = config('database.prefix', 'ibs_') . 'dispatch_report_items';
        $ordersTable = config('database.prefix', 'ibs_') . 'orders';
        $database = config('database.database', '');

        try {
            $check = $this->pdo->prepare(
                'SELECT COUNT(*) AS table_count FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = :table'
            );

            foreach ([$itemsTable, $ordersTable] as $tableName) {
                $check->execute(['schema' => $database, 'table' => $tableName]);
                $row = $check->fetch(\PDO::FETCH_ASSOC);
                if (((int) ($row['table_count'] ?? 0)) === 0) {
                    return [];
                }
            }

            $sql = 'SELECT i.*, o.customer_name, o.customer_phone, o.ibs_status, o.order_reference AS erp_order_reference '
                . 'FROM `' . $this->escapeIdentifier($itemsTable) . '` i '
                . 'LEFT JOIN `' . $this->escapeIdentifier($ordersTable) . '` o ON o.order_id = i.order_id '
                . 'WHERE i.dispatch_report_id = :dispatch_report_id '
                . 'ORDER BY i.dispatch_report_item_id ASC';
            \App\Database\QueryGuard::assertReadOnly($sql);
            $statement = $this->pdo->prepare($sql);
            $statement->execute(['dispatch_report_id' => $dispatchReportId]);

            return $statement->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }
}
