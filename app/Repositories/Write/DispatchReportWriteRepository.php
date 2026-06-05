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
            . '(dispatch_reference, supplier_id, business_source_id, dispatch_date, total_orders, total_product_cost, status, created_at) '
            . 'VALUES (:dispatch_reference, :supplier_id, :business_source_id, :dispatch_date, :total_orders, :total_product_cost, :status, NOW())';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($data);

        return (int) $this->pdo->lastInsertId();
    }
}
