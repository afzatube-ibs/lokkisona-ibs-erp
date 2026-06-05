<?php

namespace App\Repositories\Write;

use App\Models\ProductCostHistory;

class ProductCostHistoryWriteRepository extends BaseWriteRepository
{
    public function modelClass(): string
    {
        return ProductCostHistory::class;
    }

    public function insert(array $data): int
    {
        $sql = 'INSERT INTO `' . $this->escapeIdentifier($this->table()) . '` '
            . '(product_id, product_variant_id, supplier_id, old_cost, new_cost, note, created_at) '
            . 'VALUES (:product_id, :product_variant_id, :supplier_id, :old_cost, :new_cost, :note, NOW())';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($data);

        return (int) $this->pdo->lastInsertId();
    }
}
