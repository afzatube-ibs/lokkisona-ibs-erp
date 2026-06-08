<?php

namespace App\Repositories\Write;

use App\Models\Order;
use App\Support\SchemaColumnProbe;

class OrderWriteRepository extends BaseWriteRepository
{
    public function modelClass(): string
    {
        return Order::class;
    }

    public function createFromManual(array $data): int
    {
        return $this->createOrder($data);
    }

    public function createFromSync(array $data): int
    {
        return $this->createOrder($data);
    }

    private function createOrder(array $data): int
    {
        $table = $this->table();
        $columns = [
            'business_source_id' => $data['business_source_id'],
            'supplier_id' => $data['supplier_id'] ?? null,
            'source_order_id' => $data['source_order_id'] ?? null,
            'source_order_reference' => $data['source_order_reference'],
            'source_invoice_reference' => $data['source_invoice_reference'] ?? null,
            'order_reference' => $data['order_reference'],
            'customer_name' => $data['customer_name'],
            'customer_phone' => $data['customer_phone'] ?? null,
            'customer_address' => $data['customer_address'] ?? null,
            'order_total' => $data['order_total'],
            'ibs_status' => $data['ibs_status'],
            'courier_name' => $data['courier_name'] ?? null,
            'tracking_number' => $data['tracking_number'] ?? null,
            'courier_status' => $data['courier_status'] ?? null,
            'cost_snapshot_total' => $data['cost_snapshot_total'],
            'status' => $data['status'] ?? 'active',
        ];

        $optional = [
            'origin_order_status_id' => $data['origin_order_status_id'] ?? null,
            'origin_order_status_name' => $data['origin_order_status_name'] ?? null,
            'sync_source' => $data['sync_source'] ?? null,
            'imported_at' => $data['imported_at'] ?? null,
            'last_synced_at' => $data['last_synced_at'] ?? null,
        ];
        foreach ($optional as $column => $value) {
            if ($value !== null && SchemaColumnProbe::tableHasColumn($table, $column, $this->pdo)) {
                $columns[$column] = $value;
            }
        }

        $fieldNames = array_keys($columns);
        $placeholders = array_map(static fn (string $name): string => ':' . $name, $fieldNames);
        $sql = 'INSERT INTO `' . $this->escapeIdentifier($table) . '` ('
            . implode(', ', array_map(fn (string $c): string => '`' . $this->escapeIdentifier($c) . '`', $fieldNames))
            . ', ordered_at, created_at) VALUES ('
            . implode(', ', $placeholders) . ', NOW(), NOW())';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($columns);

        return (int) $this->pdo->lastInsertId();
    }

    public function findBySourceReference(string $sourceReference, int $businessSourceId): ?array
    {
        if (!$this->tableExists() || $sourceReference === '') {
            return null;
        }

        $sql = 'SELECT * FROM `' . $this->escapeIdentifier($this->table()) . '` '
            . 'WHERE source_order_reference = :source_reference AND business_source_id = :source_id LIMIT 1';
        $statement = $this->pdo->prepare($sql);
        $statement->execute(['source_reference' => $sourceReference, 'source_id' => $businessSourceId]);
        $row = $statement->fetch(\PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }

    public function findBySourceOrderId(string $sourceOrderId, int $businessSourceId): ?array
    {
        if (!$this->tableExists() || trim($sourceOrderId) === '') {
            return null;
        }

        $sql = 'SELECT * FROM `' . $this->escapeIdentifier($this->table()) . '` '
            . 'WHERE source_order_id = :source_order_id AND business_source_id = :source_id LIMIT 1';
        $statement = $this->pdo->prepare($sql);
        $statement->execute(['source_order_id' => trim($sourceOrderId), 'source_id' => $businessSourceId]);
        $row = $statement->fetch(\PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }

    public function findExistingForSync(int $businessSourceId, string $sourceOrderId, string $sourceReference): ?array
    {
        $sourceOrderId = trim($sourceOrderId);
        $sourceReference = trim($sourceReference);

        if ($sourceOrderId !== '') {
            $byId = $this->findBySourceOrderId($sourceOrderId, $businessSourceId);
            if ($byId !== null) {
                return $byId;
            }
        }

        if ($sourceReference !== '') {
            return $this->findBySourceReference($sourceReference, $businessSourceId);
        }

        return null;
    }

    /**
     * Refresh OpenCart snapshot fields only — never touches ibs_status or dispatch locks.
     */
    public function updateOriginSnapshot(int $orderId, array $data): bool
    {
        if (!$this->tableExists() || $orderId <= 0) {
            return false;
        }

        $table = $this->table();
        $set = [];
        $params = ['id' => $orderId];
        $allowed = [
            'origin_order_status_id' => 'origin_order_status_id',
            'origin_order_status_name' => 'origin_order_status_name',
            'courier_status' => 'courier_status',
            'tracking_number' => 'tracking_number',
            'customer_name' => 'customer_name',
            'customer_phone' => 'customer_phone',
            'customer_address' => 'customer_address',
            'last_synced_at' => 'last_synced_at',
        ];

        foreach ($allowed as $key => $column) {
            if (!array_key_exists($key, $data) || !SchemaColumnProbe::tableHasColumn($table, $column, $this->pdo)) {
                continue;
            }
            $set[] = '`' . $this->escapeIdentifier($column) . '` = :' . $key;
            $params[$key] = $data[$key];
        }

        if ($set === []) {
            $set[] = 'updated_at = NOW()';
        } else {
            $set[] = 'updated_at = NOW()';
        }

        $sql = 'UPDATE `' . $this->escapeIdentifier($table) . '` SET ' . implode(', ', $set) . ' WHERE order_id = :id LIMIT 1';
        $statement = $this->pdo->prepare($sql);

        return $statement->execute($params);
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

    public function countByStatus(string $status, int $supplierId = 0): int
    {
        if (!$this->tableExists()) {
            return 0;
        }

        $sql = 'SELECT COUNT(*) AS row_count FROM `' . $this->escapeIdentifier($this->table()) . '` WHERE ibs_status = :status';
        $params = ['status' => $status];
        if ($supplierId > 0) {
            $sql .= ' AND supplier_id = :supplier_id';
            $params['supplier_id'] = $supplierId;
        }
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
        $row = $statement->fetch(\PDO::FETCH_ASSOC);

        return (int) ($row['row_count'] ?? 0);
    }

    /**
     * @param array<int, string> $statuses
     */
    public function countByStatuses(array $statuses, int $supplierId = 0): int
    {
        if (!$this->tableExists() || $statuses === []) {
            return 0;
        }

        $placeholders = [];
        $params = [];
        foreach (array_values($statuses) as $index => $status) {
            $key = 'status_' . $index;
            $placeholders[] = ':' . $key;
            $params[$key] = $status;
        }

        $sql = 'SELECT COUNT(*) AS row_count FROM `' . $this->escapeIdentifier($this->table()) . '` '
            . 'WHERE ibs_status IN (' . implode(', ', $placeholders) . ')';
        if ($supplierId > 0) {
            $sql .= ' AND supplier_id = :supplier_id';
            $params['supplier_id'] = $supplierId;
        }
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
        $row = $statement->fetch(\PDO::FETCH_ASSOC);

        return (int) ($row['row_count'] ?? 0);
    }

    public function countReturnPending(string $returnType, string $ibsStatus): int
    {
        if (!$this->tableExists() || $returnType === '' || $ibsStatus === '') {
            return 0;
        }

        $ordersTable = $this->escapeIdentifier($this->table());
        $excludeSql = '';

        $itemsTable = config('database.prefix', 'ibs_') . 'return_batch_items';
        $receivesTable = config('database.prefix', 'ibs_') . 'return_receives';
        $database = config('database.database', '');
        $checkSql = 'SELECT COUNT(*) AS table_count FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = :table';
        $check = $this->pdo->prepare($checkSql);

        foreach ([$itemsTable, $receivesTable] as $tableName) {
            $check->execute(['schema' => $database, 'table' => $tableName]);
            $checkRow = $check->fetch(\PDO::FETCH_ASSOC);
            if (((int) ($checkRow['table_count'] ?? 0)) === 0) {
                return $this->countByStatus($ibsStatus);
            }
        }

        $excludeSql = ' AND o.order_id NOT IN ('
            . 'SELECT i.order_id FROM `' . $this->escapeIdentifier($itemsTable) . '` i '
            . 'INNER JOIN `' . $this->escapeIdentifier($receivesTable) . '` r ON r.return_receive_id = i.return_receive_id '
            . 'WHERE i.order_id IS NOT NULL AND r.return_type = :return_type AND r.status = :receive_status'
            . ')';

        $sql = 'SELECT COUNT(*) AS row_count FROM `' . $ordersTable . '` o '
            . 'WHERE o.ibs_status = :ibs_status' . $excludeSql;
        $statement = $this->pdo->prepare($sql);
        $statement->execute([
            'ibs_status' => $ibsStatus,
            'return_type' => $returnType,
            'receive_status' => 'received',
        ]);
        $row = $statement->fetch(\PDO::FETCH_ASSOC);

        return (int) ($row['row_count'] ?? 0);
    }

    public function findShippedEligible(int $limit = 50, bool $excludeDispatched = true): array
    {
        if (!$this->tableExists()) {
            return [];
        }

        $limit = max(1, min($limit, 50));
        $ordersTable = $this->escapeIdentifier($this->table());
        $excludeSql = '';

        if ($excludeDispatched) {
            $itemsTable = config('database.prefix', 'ibs_') . 'dispatch_report_items';
            $database = config('database.database', '');
            $checkSql = 'SELECT COUNT(*) AS table_count FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = :table';
            $check = $this->pdo->prepare($checkSql);
            $check->execute(['schema' => $database, 'table' => $itemsTable]);
            $checkRow = $check->fetch(\PDO::FETCH_ASSOC);

            if (((int) ($checkRow['table_count'] ?? 0)) > 0) {
                $excludeSql = ' AND o.order_id NOT IN ('
                    . 'SELECT i.order_id FROM `' . $this->escapeIdentifier($itemsTable) . '` i '
                    . 'WHERE i.order_id IS NOT NULL AND i.status = \'included\''
                    . ')';
            }
        }

        $sql = 'SELECT o.* FROM `' . $ordersTable . '` o '
            . 'WHERE o.ibs_status = :status' . $excludeSql
            . ' ORDER BY o.order_id ASC LIMIT ' . $limit;
        $statement = $this->pdo->prepare($sql);
        $statement->execute(['status' => 'shipped']);

        return $statement->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    public function findReturnPending(string $returnType, string $ibsStatus, int $limit = 50): array
    {
        if (!$this->tableExists() || $returnType === '' || $ibsStatus === '') {
            return [];
        }

        $limit = max(1, min($limit, 50));
        $ordersTable = $this->escapeIdentifier($this->table());
        $excludeSql = '';

        $itemsTable = config('database.prefix', 'ibs_') . 'return_batch_items';
        $receivesTable = config('database.prefix', 'ibs_') . 'return_receives';
        $database = config('database.database', '');
        $checkSql = 'SELECT COUNT(*) AS table_count FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = :table';
        $check = $this->pdo->prepare($checkSql);

        foreach ([$itemsTable, $receivesTable] as $tableName) {
            $check->execute(['schema' => $database, 'table' => $tableName]);
            $checkRow = $check->fetch(\PDO::FETCH_ASSOC);
            if (((int) ($checkRow['table_count'] ?? 0)) === 0) {
                return $this->findByStatus($ibsStatus, $limit);
            }
        }

        $excludeSql = ' AND o.order_id NOT IN ('
            . 'SELECT i.order_id FROM `' . $this->escapeIdentifier($itemsTable) . '` i '
            . 'INNER JOIN `' . $this->escapeIdentifier($receivesTable) . '` r ON r.return_receive_id = i.return_receive_id '
            . 'WHERE i.order_id IS NOT NULL AND r.return_type = :return_type AND r.status = :receive_status'
            . ')';

        $sql = 'SELECT o.* FROM `' . $ordersTable . '` o '
            . 'WHERE o.ibs_status = :ibs_status' . $excludeSql
            . ' ORDER BY o.order_id ASC LIMIT ' . $limit;
        $statement = $this->pdo->prepare($sql);
        $statement->execute([
            'ibs_status' => $ibsStatus,
            'return_type' => $returnType,
            'receive_status' => 'received',
        ]);

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
