<?php

namespace App\Repositories\Write;

use App\Models\ManualOrderItem;

class ManualOrderItemWriteRepository extends BaseWriteRepository
{
    public function modelClass(): string
    {
        return ManualOrderItem::class;
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO `' . $this->escapeIdentifier($this->table()) . '` '
            . '(manual_order_id, product_id, product_variant_id, product_name, variant_label, quantity, selling_price, supplier_cost_snapshot, line_total, created_at) '
            . 'VALUES (:manual_order_id, :product_id, :product_variant_id, :product_name, :variant_label, :quantity, :selling_price, :supplier_cost_snapshot, :line_total, NOW())';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($data);

        return (int) $this->pdo->lastInsertId();
    }
}
