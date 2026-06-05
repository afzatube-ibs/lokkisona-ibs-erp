<?php

namespace App\Repositories\Write;

use App\Models\ProductStockHistory;

class ProductStockHistoryWriteRepository extends BaseWriteRepository
{
    public function modelClass(): string
    {
        return ProductStockHistory::class;
    }

    public function insert(array $data): int
    {
        $sql = 'INSERT INTO `' . $this->escapeIdentifier($this->table()) . '` '
            . '(product_id, product_variant_id, supplier_id, old_stock, new_stock, change_type, note, created_at) '
            . 'VALUES (:product_id, :product_variant_id, :supplier_id, :old_stock, :new_stock, :change_type, :note, NOW())';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($data);

        return (int) $this->pdo->lastInsertId();
    }
}
