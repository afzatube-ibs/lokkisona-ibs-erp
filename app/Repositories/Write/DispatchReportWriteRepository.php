<?php

namespace App\Repositories\Write;

use App\Models\DispatchReport;

class DispatchReportWriteRepository extends BaseWriteRepository
{
    public function modelClass(): string
    {
        return DispatchReport::class;
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO `' . $this->escapeIdentifier($this->table()) . '` '
            . '(dispatch_reference, supplier_id, business_source_id, dispatch_date, total_orders, total_product_cost, status, locked_by, locked_at, created_by, created_at) '
            . 'VALUES (:dispatch_reference, :supplier_id, :business_source_id, :dispatch_date, :total_orders, :total_product_cost, :status, :locked_by, :locked_at, :created_by, NOW())';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($data);

        return (int) $this->pdo->lastInsertId();
    }

    public function findReferencesByDispatchDate(string $dispatchDate): array
    {
        if (!$this->tableExists() || $dispatchDate === '') {
            return [];
        }

        $sql = 'SELECT dispatch_reference FROM `' . $this->escapeIdentifier($this->table()) . '` '
            . 'WHERE dispatch_date = :dispatch_date ORDER BY dispatch_report_id ASC';
        $statement = $this->pdo->prepare($sql);
        $statement->execute(['dispatch_date' => $dispatchDate]);

        $rows = $statement->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        return array_values(array_filter(array_map(
            static fn (array $row): string => (string) ($row['dispatch_reference'] ?? ''),
            $rows
        )));
    }
}
