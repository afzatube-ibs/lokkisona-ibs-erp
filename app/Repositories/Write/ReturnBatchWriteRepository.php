<?php

namespace App\Repositories\Write;

use App\Models\ReturnBatch;

class ReturnBatchWriteRepository extends BaseWriteRepository
{
    public function modelClass(): string
    {
        return ReturnBatch::class;
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO `' . $this->escapeIdentifier($this->table()) . '` '
            . '(return_batch_reference, supplier_id, total_returns, total_adjustment_amount, status, created_at) '
            . 'VALUES (:return_batch_reference, :supplier_id, :total_returns, :total_adjustment_amount, :status, NOW())';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($data);

        return (int) $this->pdo->lastInsertId();
    }
}
