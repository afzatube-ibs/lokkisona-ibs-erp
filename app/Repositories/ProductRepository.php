<?php

namespace App\Repositories;

use App\Models\Product;

class ProductRepository extends BaseReadOnlyRepository
{
    public function modelClass(): string
    {
        return Product::class;
    }

    /**
     * @param array<int, int> $productIds
     * @return array<int, array<string, mixed>>
     */
    public function indexedByIds(array $productIds): array
    {
        $productIds = array_values(array_filter(array_map('intval', $productIds), static fn (int $id): bool => $id > 0));
        if (!$this->tableExists() || $productIds === []) {
            return [];
        }

        try {
            $table = TableName::forModel(Product::class);
            $placeholders = implode(', ', array_fill(0, count($productIds), '?'));
            $sql = 'SELECT * FROM `' . $this->escapeIdentifier($table) . '` WHERE product_id IN (' . $placeholders . ')';
            \App\Database\QueryGuard::assertReadOnly($sql);
            $statement = $this->pdo->prepare($sql);
            $statement->execute($productIds);
            $indexed = [];
            foreach ($statement->fetchAll(\PDO::FETCH_ASSOC) ?: [] as $row) {
                $pid = (int) ($row['product_id'] ?? 0);
                if ($pid > 0) {
                    $indexed[$pid] = $row;
                }
            }

            return $indexed;
        } catch (\Throwable $e) {
            return [];
        }
    }
}
