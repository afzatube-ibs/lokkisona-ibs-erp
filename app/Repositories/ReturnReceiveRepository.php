<?php

namespace App\Repositories;

use App\Domain\ReturnReceiveType;
use App\Models\ReturnReceive;

class ReturnReceiveRepository extends BaseReadOnlyRepository
{
    public function modelClass(): string
    {
        return ReturnReceive::class;
    }

    public function latestReceived(int $limit = 20): array
    {
        if (!$this->tableExists()) {
            return [];
        }

        try {
            $limit = max(1, min($limit, 50));
            $table = \App\Database\TableName::forModel(ReturnReceive::class);
            $primaryKey = ReturnReceive::primaryKey();
            $sql = 'SELECT * FROM `' . $this->escapeIdentifier($table) . '` '
                . 'WHERE status = :status '
                . 'ORDER BY received_at DESC, `' . $this->escapeIdentifier($primaryKey) . '` DESC LIMIT ' . $limit;
            \App\Database\QueryGuard::assertReadOnly($sql);
            $statement = $this->pdo->prepare($sql);
            $statement->execute(['status' => 'received']);

            return $statement->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function findReceivedWithOrders(int $limit = 20): array
    {
        if (!$this->tableExists()) {
            return [];
        }

        $itemsTable = config('database.prefix', 'ibs_') . 'return_batch_items';
        $ordersTable = config('database.prefix', 'ibs_') . 'orders';
        $receivesTable = \App\Database\TableName::forModel(ReturnReceive::class);
        $database = config('database.database', '');

        try {
            $check = $this->pdo->prepare(
                'SELECT COUNT(*) AS table_count FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = :table'
            );

            foreach ([$itemsTable, $ordersTable] as $tableName) {
                $check->execute(['schema' => $database, 'table' => $tableName]);
                $row = $check->fetch(\PDO::FETCH_ASSOC);
                if (((int) ($row['table_count'] ?? 0)) === 0) {
                    return $this->latestReceived($limit);
                }
            }

            $limit = max(1, min($limit, 50));
            $sql = 'SELECT r.*, i.order_id, i.quantity AS item_quantity, i.cost_snapshot AS item_cost_snapshot, '
                . 'o.order_reference, o.customer_name, o.customer_phone, o.ibs_status '
                . 'FROM `' . $this->escapeIdentifier($receivesTable) . '` r '
                . 'LEFT JOIN `' . $this->escapeIdentifier($itemsTable) . '` i ON i.return_receive_id = r.return_receive_id '
                . 'LEFT JOIN `' . $this->escapeIdentifier($ordersTable) . '` o ON o.order_id = i.order_id '
                . 'WHERE r.status = :status '
                . 'ORDER BY r.received_at DESC, r.return_receive_id DESC LIMIT ' . $limit;
            \App\Database\QueryGuard::assertReadOnly($sql);
            $statement = $this->pdo->prepare($sql);
            $statement->execute(['status' => 'received']);

            return $statement->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Supplier-confirmed returns eligible for Return Report (not yet reported).
     *
     * @return array<int, array<string, mixed>>
     */
    public function findEligibleForReport(int $limit = 50): array
    {
        if (!$this->tableExists()) {
            return [];
        }

        $reportItemsTable = config('database.prefix', 'ibs_') . 'return_report_items';
        $database = config('database.database', '');

        try {
            $limit = max(1, min($limit, 50));
            $receivesTable = \App\Database\TableName::forModel(ReturnReceive::class);

            $reportItemsJoin = '';
            if ($this->tableExistsByName($reportItemsTable, $database)) {
                $reportItemsJoin = 'LEFT JOIN `' . $this->escapeIdentifier($reportItemsTable) . '` ri ON ri.return_receive_id = r.return_receive_id ';
            }

            $supplierTypes = ReturnReceiveType::supplierTypes();
            $typePlaceholders = implode(',', array_fill(0, count($supplierTypes), '?'));

            $sql = 'SELECT r.* FROM `' . $this->escapeIdentifier($receivesTable) . '` r '
                . $reportItemsJoin
                . 'WHERE r.status = ? AND r.return_type IN (' . $typePlaceholders . ') '
                . ($reportItemsJoin !== '' ? 'AND ri.return_report_item_id IS NULL ' : '')
                . 'AND r.total_cost_snapshot > 0 '
                . 'ORDER BY r.received_at DESC, r.return_receive_id DESC LIMIT ' . $limit;
            \App\Database\QueryGuard::assertReadOnly($sql);

            $params = array_merge(['received'], $supplierTypes);
            $statement = $this->pdo->prepare($sql);
            $statement->execute($params);

            return $statement->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function tableExistsByName(string $table, string $database): bool
    {
        try {
            $check = $this->pdo->prepare(
                'SELECT COUNT(*) AS table_count FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = :table'
            );
            $check->execute(['schema' => $database, 'table' => $table]);
            $row = $check->fetch(\PDO::FETCH_ASSOC);

            return ((int) ($row['table_count'] ?? 0)) > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Received returns not yet attached to any return batch (eligible for batching).
     * SELECT only. Mirrors dispatch 'shipped, not yet in a report' eligibility.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findEligibleForBatch(int $limit = 50): array
    {
        if (!$this->tableExists()) {
            return [];
        }

        try {
            $limit = max(1, min($limit, 50));
            $receivesTable = \App\Database\TableName::forModel(ReturnReceive::class);
            $batchItemsTable = config('database.prefix', 'ibs_') . 'return_batch_items';

            $sql = 'SELECT r.* FROM `' . $this->escapeIdentifier($receivesTable) . '` r '
                . 'LEFT JOIN `' . $this->escapeIdentifier($batchItemsTable) . '` bi ON bi.return_receive_id = r.return_receive_id '
                . 'WHERE r.status = :status AND bi.return_batch_item_id IS NULL '
                . 'ORDER BY r.received_at DESC, r.return_receive_id DESC LIMIT ' . $limit;
            \App\Database\QueryGuard::assertReadOnly($sql);
            $statement = $this->pdo->prepare($sql);
            $statement->execute(['status' => 'received']);

            return $statement->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * @param array<int, int> $orderIds
     * @return array<int, array<string, mixed>> order_id => return meta
     */
    public function findReturnMetaByOrderIds(array $orderIds): array
    {
        $orderIds = array_values(array_filter(array_map('intval', $orderIds), static fn (int $id): bool => $id > 0));
        if ($orderIds === [] || !$this->tableExists()) {
            return [];
        }

        $itemsTable = config('database.prefix', 'ibs_') . 'return_batch_items';
        $receivesTable = \App\Database\TableName::forModel(ReturnReceive::class);
        $database = config('database.database', '');

        try {
            $check = $this->pdo->prepare(
                'SELECT COUNT(*) AS table_count FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = :table'
            );
            foreach ([$itemsTable, $receivesTable] as $tableName) {
                $check->execute(['schema' => $database, 'table' => $tableName]);
                $row = $check->fetch(\PDO::FETCH_ASSOC);
                if (((int) ($row['table_count'] ?? 0)) === 0) {
                    return [];
                }
            }

            $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
            $sql = 'SELECT i.order_id, r.return_receive_id, r.return_reference, r.return_type, r.status AS return_status, '
                . 'r.total_cost_snapshot, r.received_at '
                . 'FROM `' . $this->escapeIdentifier($itemsTable) . '` i '
                . 'INNER JOIN `' . $this->escapeIdentifier($receivesTable) . '` r ON r.return_receive_id = i.return_receive_id '
                . 'WHERE i.order_id IN (' . $placeholders . ') '
                . 'ORDER BY r.received_at DESC';
            \App\Database\QueryGuard::assertReadOnly($sql);
            $statement = $this->pdo->prepare($sql);
            $statement->execute($orderIds);
            $map = [];
            foreach ($statement->fetchAll(\PDO::FETCH_ASSOC) ?: [] as $row) {
                $orderId = (int) ($row['order_id'] ?? 0);
                if ($orderId > 0 && !isset($map[$orderId])) {
                    $map[$orderId] = $row;
                }
            }

            return $map;
        } catch (\Throwable $e) {
            return [];
        }
    }
}

