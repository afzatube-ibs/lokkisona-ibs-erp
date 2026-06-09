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
        return $this->latestForSupplier(0, $limit);
    }

    public function latestForSupplier(int $supplierId = 0, int $limit = 20): array
    {
        if (!$this->tableExists()) {
            return [];
        }

        try {
            $limit = max(1, min($limit, 50));
            $table = \App\Database\TableName::forModel(DispatchReport::class);
            $primaryKey = DispatchReport::primaryKey();
            $sql = 'SELECT * FROM `' . $this->escapeIdentifier($table) . '` ';
            if ($supplierId > 0) {
                $sql .= 'WHERE supplier_id = :supplier_id ';
            }
            $sql .= 'ORDER BY created_at DESC, `' . $this->escapeIdentifier($primaryKey) . '` DESC LIMIT ' . $limit;
            \App\Database\QueryGuard::assertReadOnly($sql);
            if ($supplierId > 0) {
                $statement = $this->pdo->prepare($sql);
                $statement->execute(['supplier_id' => $supplierId]);
            } else {
                $statement = $this->pdo->query($sql);
            }

            return $statement ? ($statement->fetchAll(\PDO::FETCH_ASSOC) ?: []) : [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * @return array<int, array{dispatch_reference: string, dispatch_report_id: int}>
     */
    public function findIncludedOrderMeta(int $limit = 500): array
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

            $limit = max(1, min($limit, 500));
            $sql = 'SELECT i.order_id, r.dispatch_reference, r.dispatch_report_id '
                . 'FROM `' . $this->escapeIdentifier($itemsTable) . '` i '
                . 'INNER JOIN `' . $this->escapeIdentifier($reportsTable) . '` r ON r.dispatch_report_id = i.dispatch_report_id '
                . 'WHERE i.order_id IS NOT NULL AND i.status = :item_status '
                . 'ORDER BY i.dispatch_report_item_id DESC LIMIT ' . $limit;
            \App\Database\QueryGuard::assertReadOnly($sql);
            $statement = $this->pdo->prepare($sql);
            $statement->execute(['item_status' => 'included']);
            $map = [];
            foreach ($statement->fetchAll(\PDO::FETCH_ASSOC) ?: [] as $row) {
                $orderId = (int) ($row['order_id'] ?? 0);
                if ($orderId > 0 && !isset($map[$orderId])) {
                    $map[$orderId] = [
                        'dispatch_reference' => (string) ($row['dispatch_reference'] ?? ''),
                        'dispatch_report_id' => (int) ($row['dispatch_report_id'] ?? 0),
                    ];
                }
            }

            return $map;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * @return array<int, string> order_id => dispatch_reference
     */
    public function findIncludedOrderReferences(int $limit = 50): array
    {
        $map = [];
        foreach ($this->findIncludedOrderMeta($limit) as $orderId => $meta) {
            $map[$orderId] = $meta['dispatch_reference'];
        }

        return $map;
    }

    /**
     * @param array<int, int> $reportIds
     * @return array<int, int> dispatch_report_id => total item qty
     */
    public function sumItemCountsByReportIds(array $reportIds): array
    {
        $reportIds = array_values(array_filter(array_map('intval', $reportIds), static fn (int $id): bool => $id > 0));
        if ($reportIds === [] || !$this->tableExists()) {
            return [];
        }

        $itemsTable = config('database.prefix', 'ibs_') . 'dispatch_report_items';
        $database = config('database.database', '');

        try {
            $check = $this->pdo->prepare(
                'SELECT COUNT(*) AS table_count FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = :table'
            );
            $check->execute(['schema' => $database, 'table' => $itemsTable]);
            $row = $check->fetch(\PDO::FETCH_ASSOC);
            if (((int) ($row['table_count'] ?? 0)) === 0) {
                return [];
            }

            $placeholders = implode(',', array_fill(0, count($reportIds), '?'));
            $sql = 'SELECT dispatch_report_id, COALESCE(SUM(item_count), 0) AS total_qty '
                . 'FROM `' . $this->escapeIdentifier($itemsTable) . '` '
                . 'WHERE dispatch_report_id IN (' . $placeholders . ') '
                . 'GROUP BY dispatch_report_id';
            \App\Database\QueryGuard::assertReadOnly($sql);
            $statement = $this->pdo->prepare($sql);
            $statement->execute($reportIds);
            $map = [];
            foreach ($statement->fetchAll(\PDO::FETCH_ASSOC) ?: [] as $row) {
                $id = (int) ($row['dispatch_report_id'] ?? 0);
                if ($id > 0) {
                    $map[$id] = (int) ($row['total_qty'] ?? 0);
                }
            }

            return $map;
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function findByReference(string $reference): ?array
    {
        $reference = trim($reference);
        if ($reference === '' || !$this->tableExists()) {
            return null;
        }

        try {
            $table = \App\Database\TableName::forModel(DispatchReport::class);
            $sql = 'SELECT * FROM `' . $this->escapeIdentifier($table) . '` '
                . 'WHERE dispatch_reference = :reference LIMIT 1';
            \App\Database\QueryGuard::assertReadOnly($sql);
            $statement = $this->pdo->prepare($sql);
            $statement->execute(['reference' => $reference]);
            $row = $statement->fetch(\PDO::FETCH_ASSOC);

            return $row !== false ? $row : null;
        } catch (\Throwable $e) {
            return null;
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

            $sql = 'SELECT i.*, o.customer_name, o.customer_phone, o.ibs_status, o.order_reference AS erp_order_reference, '
                . 'o.courier_status, o.tracking_number, o.source_order_status, o.origin_order_status_name '
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
