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
            . '(product_id, product_variant_id, supplier_id, old_cost, new_cost, changed_by, note, created_at) '
            . 'VALUES (:product_id, :product_variant_id, :supplier_id, :old_cost, :new_cost, :changed_by, :note, NOW())';
        $statement = $this->pdo->prepare($sql);
        $statement->execute([
            'product_id' => $data['product_id'],
            'product_variant_id' => $data['product_variant_id'] ?? null,
            'supplier_id' => $data['supplier_id'] ?? null,
            'old_cost' => $data['old_cost'],
            'new_cost' => $data['new_cost'],
            'changed_by' => $data['changed_by'] ?? null,
            'note' => $data['note'] ?? null,
        ]);

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
