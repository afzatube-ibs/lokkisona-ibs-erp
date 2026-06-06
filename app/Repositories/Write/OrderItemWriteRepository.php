<?php

namespace App\Repositories\Write;

use App\Models\OrderItem;

class OrderItemWriteRepository extends BaseWriteRepository
{
    public function modelClass(): string
    {
        return OrderItem::class;
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO `' . $this->escapeIdentifier($this->table()) . '` '
            . '(order_id, product_id, product_variant_id, source_product_id, product_name, variant_label, quantity, selling_price, supplier_cost_snapshot, line_total, created_at) '
            . 'VALUES (:order_id, :product_id, :product_variant_id, :source_product_id, :product_name, :variant_label, :quantity, :selling_price, :supplier_cost_snapshot, :line_total, NOW())';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($data);

        return (int) $this->pdo->lastInsertId();
    }
}
