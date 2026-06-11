<?php

namespace App\Repositories;

use App\Models\ReturnReport;

class ReturnReportRepository extends BaseReadOnlyRepository
{
    public function modelClass(): string
    {
        return ReturnReport::class;
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
            $table = \App\Database\TableName::forModel(ReturnReport::class);
            $primaryKey = ReturnReport::primaryKey();
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

    public function findByReference(string $reference): ?array
    {
        $reference = trim($reference);
        if ($reference === '' || !$this->tableExists()) {
            return null;
        }

        try {
            $table = \App\Database\TableName::forModel(ReturnReport::class);
            $sql = 'SELECT * FROM `' . $this->escapeIdentifier($table) . '` '
                . 'WHERE return_report_reference = :reference LIMIT 1';
            \App\Database\QueryGuard::assertReadOnly($sql);
            $statement = $this->pdo->prepare($sql);
            $statement->execute(['reference' => $reference]);
            $row = $statement->fetch(\PDO::FETCH_ASSOC);

            return $row !== false ? $row : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * @param array<string, mixed> $report
     */
    public function findItemsForReport(array $report): array
    {
        $reportId = (int) ($report['return_report_id'] ?? 0);
        if ($reportId <= 0) {
            return [];
        }

        return $this->findItemsWithOrders($reportId);
    }

    public function findItemsWithOrders(int $returnReportId): array
    {
        if (!$this->tableExists() || $returnReportId <= 0) {
            return [];
        }

        $itemsTable = config('database.prefix', 'ibs_') . 'return_report_items';
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
                . 'o.source_order_reference, o.business_source_id '
                . 'FROM `' . $this->escapeIdentifier($itemsTable) . '` i '
                . 'LEFT JOIN `' . $this->escapeIdentifier($ordersTable) . '` o ON o.order_id = i.order_id '
                . 'WHERE i.return_report_id = :return_report_id AND i.status = :item_status '
                . 'ORDER BY i.return_report_item_id ASC';
            \App\Database\QueryGuard::assertReadOnly($sql);
            $statement = $this->pdo->prepare($sql);
            $statement->execute([
                'return_report_id' => $returnReportId,
                'item_status' => 'included',
            ]);

            return $statement->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }
}
