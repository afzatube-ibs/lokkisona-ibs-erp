<?php

namespace App\Repositories\Write;

use App\Models\Order;

class OrderWriteRepository extends BaseWriteRepository
{
    public function modelClass(): string
    {
        return Order::class;
    }

    public function createFromManual(array $data): int
    {
        $sql = 'INSERT INTO `' . $this->escapeIdentifier($this->table()) . '` '
            . '(business_source_id, supplier_id, source_order_reference, order_reference, customer_name, customer_phone, customer_address, '
            . 'order_total, ibs_status, cost_snapshot_total, status, ordered_at, created_at) '
            . 'VALUES (:business_source_id, :supplier_id, :source_order_reference, :order_reference, :customer_name, :customer_phone, :customer_address, '
            . ':order_total, :ibs_status, :cost_snapshot_total, :status, NOW(), NOW())';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($data);

        return (int) $this->pdo->lastInsertId();
    }

    public function updateStatus(int $id, string $status): bool
    {
        $sql = 'UPDATE `' . $this->escapeIdentifier($this->table()) . '` SET ibs_status = :status, updated_at = NOW() WHERE order_id = :id';
        $statement = $this->pdo->prepare($sql);

        return $statement->execute(['status' => $status, 'id' => $id]);
    }

    public function find(int $id): ?array
    {
        return $this->findById($id);
    }

    public function findByStatus(string $status, int $limit = 50): array
    {
        if (!$this->tableExists()) {
            return [];
        }

        $sql = 'SELECT * FROM `' . $this->escapeIdentifier($this->table()) . '` WHERE ibs_status = :status ORDER BY order_id ASC LIMIT ' . (int) $limit;
        $statement = $this->pdo->prepare($sql);
        $statement->execute(['status' => $status]);

        return $statement->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    public function mirrorManualOrderStatusByReference(string $orderReference, string $status): bool
    {
        if ($orderReference === '') {
            return false;
        }

        $manualTable = config('database.prefix', 'ibs_') . 'manual_orders';
        $database = config('database.database', '');

        $checkSql = 'SELECT COUNT(*) AS table_count FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = :table';
        $check = $this->pdo->prepare($checkSql);
        $check->execute(['schema' => $database, 'table' => $manualTable]);
        $row = $check->fetch(\PDO::FETCH_ASSOC);
        if (((int) ($row['table_count'] ?? 0)) === 0) {
            return false;
        }

        $sql = 'UPDATE `' . $this->escapeIdentifier($manualTable) . '` '
            . 'SET ibs_status = :status, updated_at = NOW() '
            . 'WHERE manual_order_reference = :order_reference LIMIT 1';
        $statement = $this->pdo->prepare($sql);

        return $statement->execute([
            'status' => $status,
            'order_reference' => $orderReference,
        ]);
    }
}
