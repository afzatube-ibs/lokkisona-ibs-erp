<?php

namespace App\Repositories\Write;

use App\Models\ReturnReport;

class ReturnReportWriteRepository extends BaseWriteRepository
{
    public function modelClass(): string
    {
        return ReturnReport::class;
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO `' . $this->escapeIdentifier($this->table()) . '` '
            . '(return_report_reference, supplier_id, business_source_id, return_date, total_returns, total_quantity, total_adjustment_amount, status, locked_by, locked_at, created_by, created_at) '
            . 'VALUES (:return_report_reference, :supplier_id, :business_source_id, :return_date, :total_returns, :total_quantity, :total_adjustment_amount, :status, :locked_by, :locked_at, :created_by, NOW())';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($data);

        return (int) $this->pdo->lastInsertId();
    }

    public function find(int $id): ?array
    {
        return $this->findById($id);
    }

    public function findReferencesByReturnDate(string $returnDate): array
    {
        if (!$this->tableExists() || $returnDate === '') {
            return [];
        }

        $sql = 'SELECT return_report_reference FROM `' . $this->escapeIdentifier($this->table()) . '` '
            . 'WHERE return_date = :return_date ORDER BY return_report_id ASC';
        $statement = $this->pdo->prepare($sql);
        $statement->execute(['return_date' => $returnDate]);

        $rows = $statement->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        return array_values(array_filter(array_map(
            static fn (array $row): string => (string) ($row['return_report_reference'] ?? ''),
            $rows
        )));
    }
}
