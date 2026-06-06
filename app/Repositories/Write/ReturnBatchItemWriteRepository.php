<?php

namespace App\Repositories\Write;

use App\Models\ReturnBatchItem;

class ReturnBatchItemWriteRepository extends BaseWriteRepository
{
    public function modelClass(): string
    {
        return ReturnBatchItem::class;
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO `' . $this->escapeIdentifier($this->table()) . '` '
            . '(return_batch_id, return_receive_id, order_id, manual_order_id, product_id, product_variant_id, quantity, cost_snapshot, adjustment_amount, status, created_at) '
            . 'VALUES (:return_batch_id, :return_receive_id, :order_id, :manual_order_id, :product_id, :product_variant_id, :quantity, :cost_snapshot, :adjustment_amount, :status, NOW())';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($data);

        return (int) $this->pdo->lastInsertId();
    }
}
