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
        $fields = [
            'return_reference',
            'supplier_id',
            'business_source_id',
            'return_type',
            'total_items',
            'total_cost_snapshot',
            'status',
            'received_by',
            'received_at',
        ];
        $params = [
            'return_reference' => $data['return_reference'],
            'supplier_id' => $data['supplier_id'],
            'business_source_id' => $data['business_source_id'],
            'return_type' => $data['return_type'],
            'total_items' => $data['total_items'],
            'total_cost_snapshot' => $data['total_cost_snapshot'],
            'status' => $data['status'],
            'received_by' => $data['received_by'],
            'received_at' => $data['received_at'],
        ];

        if ($this->columnExists('order_id')) {
            $fields[] = 'order_id';
            $params['order_id'] = $data['order_id'] ?? null;
        }

        if ($this->columnExists('return_reason')) {
            $fields[] = 'return_reason';
            $params['return_reason'] = $data['return_reason'] ?? null;
        }

        $placeholders = array_map(static fn (string $field): string => ':' . $field, $fields);
        $sql = 'INSERT INTO `' . $this->escapeIdentifier($this->table()) . '` ('
            . implode(', ', $fields)
            . ', created_at) VALUES ('
            . implode(', ', $placeholders)
            . ', NOW())';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        return (int) $this->pdo->lastInsertId();
    }

    public function existsForOrderAndType(int $orderId, string $returnType): bool
    {
        if (!$this->tableExists() || $orderId <= 0 || $returnType === '') {
            return false;
        }

        if ($this->columnExists('order_id')) {
            $sql = 'SELECT COUNT(*) AS row_count FROM `' . $this->escapeIdentifier($this->table()) . '` '
                . 'WHERE order_id = :order_id AND return_type = :return_type '
                . 'AND status IN (:status_received, :status_batched, :status_reported)';
            $statement = $this->pdo->prepare($sql);
            $statement->execute([
                'order_id' => $orderId,
                'return_type' => $returnType,
                'status_received' => 'received',
                'status_batched' => 'batched',
                'status_reported' => 'reported',
            ]);
            $row = $statement->fetch(\PDO::FETCH_ASSOC);
            if (((int) ($row['row_count'] ?? 0)) > 0) {
                return true;
            }
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
            . 'WHERE i.order_id = :order_id AND r.return_type = :return_type '
            . 'AND r.status IN (:status_received, :status_batched, :status_reported)';
        $statement = $this->pdo->prepare($sql);
        $statement->execute([
            'order_id' => $orderId,
            'return_type' => $returnType,
            'status_received' => 'received',
            'status_batched' => 'batched',
            'status_reported' => 'reported',
        ]);
        $row = $statement->fetch(\PDO::FETCH_ASSOC);

        return ((int) ($row['row_count'] ?? 0)) > 0;
    }

    public function markBatched(int $returnReceiveId): bool
    {
        if (!$this->tableExists() || $returnReceiveId <= 0) {
            return false;
        }

        $sql = 'UPDATE `' . $this->escapeIdentifier($this->table()) . '` '
            . 'SET status = :status, updated_at = NOW() '
            . 'WHERE return_receive_id = :id AND status = :current_status';
        $statement = $this->pdo->prepare($sql);

        return $statement->execute([
            'status' => 'batched',
            'id' => $returnReceiveId,
            'current_status' => 'received',
        ]);
    }

    public function markReported(int $returnReceiveId): bool
    {
        if (!$this->tableExists() || $returnReceiveId <= 0) {
            return false;
        }

        $sql = 'UPDATE `' . $this->escapeIdentifier($this->table()) . '` '
            . 'SET status = :status, updated_at = NOW() '
            . 'WHERE return_receive_id = :id AND status = :current_status';
        $statement = $this->pdo->prepare($sql);

        return $statement->execute([
            'status' => 'reported',
            'id' => $returnReceiveId,
            'current_status' => 'received',
        ]);
    }

    /**
     * @param array<string, mixed> $return
     */
    public function resolveOrderId(int $returnReceiveId, array $return = []): int
    {
        $orderId = (int) ($return['order_id'] ?? 0);
        if ($orderId > 0) {
            return $orderId;
        }

        if ($returnReceiveId <= 0) {
            return 0;
        }

        $itemsTable = config('database.prefix', 'ibs_') . 'return_batch_items';
        $database = config('database.database', '');
        try {
            $check = $this->pdo->prepare(
                'SELECT COUNT(*) AS table_count FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = :table'
            );
            $check->execute(['schema' => $database, 'table' => $itemsTable]);
            $row = $check->fetch(\PDO::FETCH_ASSOC);
            if (((int) ($row['table_count'] ?? 0)) === 0) {
                return 0;
            }

            $sql = 'SELECT order_id FROM `' . $this->escapeIdentifier($itemsTable) . '` '
                . 'WHERE return_receive_id = :return_receive_id AND order_id IS NOT NULL '
                . 'ORDER BY return_batch_item_id DESC LIMIT 1';
            $statement = $this->pdo->prepare($sql);
            $statement->execute(['return_receive_id' => $returnReceiveId]);
            $itemRow = $statement->fetch(\PDO::FETCH_ASSOC);

            return (int) ($itemRow['order_id'] ?? 0);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function columnExists(string $column): bool
    {
        if (!$this->tableExists()) {
            return false;
        }

        $database = config('database.database', '');
        $table = $this->table();

        try {
            $sql = 'SELECT COUNT(*) AS column_count FROM INFORMATION_SCHEMA.COLUMNS '
                . 'WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = :table AND COLUMN_NAME = :column';
            $statement = $this->pdo->prepare($sql);
            $statement->execute([
                'schema' => $database,
                'table' => $table,
                'column' => $column,
            ]);
            $row = $statement->fetch(\PDO::FETCH_ASSOC);

            return ((int) ($row['column_count'] ?? 0)) > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
