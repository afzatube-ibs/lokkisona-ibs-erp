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
}
