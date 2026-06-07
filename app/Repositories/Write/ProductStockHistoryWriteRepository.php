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

    /**
     * @param array<int, int> $productIds
     */
    public function deleteForProductIds(array $productIds): int
    {
        if (!$this->tableExists() || $productIds === []) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $sql = 'DELETE FROM `' . $this->escapeIdentifier($this->table()) . '` WHERE product_id IN (' . $placeholders . ')';
        $statement = $this->pdo->prepare($sql);

        return $statement->execute($productIds) ? $statement->rowCount() : 0;
    }
}
