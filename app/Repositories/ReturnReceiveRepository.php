<?php

namespace App\Repositories;

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
}
