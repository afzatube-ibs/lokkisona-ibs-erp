<?php

namespace App\Repositories\Write;

use App\Models\ReturnReceive;

class ReturnReceiveWriteRepository extends BaseWriteRepository
{
    public function modelClass(): string
    {
        return ReturnReceive::class;
    }

    public function find(int $id): ?array
    {
        return $this->findById($id);
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO `' . $this->escapeIdentifier($this->table()) . '` '
            . '(return_reference, supplier_id, business_source_id, return_type, total_items, total_cost_snapshot, status, received_by, received_at, created_at) '
            . 'VALUES (:return_reference, :supplier_id, :business_source_id, :return_type, :total_items, :total_cost_snapshot, :status, :received_by, :received_at, NOW())';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($data);

        return (int) $this->pdo->lastInsertId();
    }

    public function existsForOrderAndType(int $orderId, string $returnType): bool
    {
        if (!$this->tableExists() || $orderId <= 0 || $returnType === '') {
            return false;
        }

        $itemsTable = config('database.prefix', 'ibs_') . 'return_batch_items';
        $database = config('database.database', '');
        $checkSql = 'SELECT COUNT(*) AS table_count FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = :table';
        $check = $this->pdo->prepare($checkSql);
        $check->execute(['schema' => $database, 'table' => $itemsTable]);
        $checkRow = $check->fetch(\PDO::FETCH_ASSOC);
        if (((int) ($checkRow['table_count'] ?? 0)) === 0) {
            return false;
        }

        $sql = 'SELECT COUNT(*) AS row_count FROM `' . $this->escapeIdentifier($itemsTable) . '` i '
            . 'INNER JOIN `' . $this->escapeIdentifier($this->table()) . '` r ON r.return_receive_id = i.return_receive_id '
            . 'WHERE i.order_id = :order_id AND r.return_type = :return_type AND r.status = :status';
        $statement = $this->pdo->prepare($sql);
        $statement->execute([
            'order_id' => $orderId,
            'return_type' => $returnType,
            'status' => 'received',
        ]);
        $row = $statement->fetch(\PDO::FETCH_ASSOC);

        return ((int) ($row['row_count'] ?? 0)) > 0;
    }
}
