<?php

namespace App\Repositories;

use App\Database\QueryGuard;
use App\Database\ReadOnlyQueryException;
use App\Database\TableName;
use App\Models\ProductVariant;
use PDO;

class ProductVariantRepository extends BaseReadOnlyRepository
{
    public function modelClass(): string
    {
        return ProductVariant::class;
    }

    /**
     * @param array<int, int> $productIds
     * @return array<int, array<int, array<string, mixed>>>
     */
    public function groupedByProductIds(array $productIds): array
    {
        $productIds = array_values(array_filter(array_map('intval', $productIds), static fn (int $id): bool => $id > 0));
        if (!$this->tableExists() || $productIds === []) {
            return [];
        }

        try {
            $table = TableName::forModel(ProductVariant::class);
            $placeholders = implode(', ', array_fill(0, count($productIds), '?'));
            $sql = 'SELECT * FROM `' . $this->escapeIdentifier($table) . '` WHERE product_id IN (' . $placeholders . ') ORDER BY product_id ASC, product_variant_id ASC';
            QueryGuard::assertReadOnly($sql);
            $statement = $this->pdo->prepare($sql);
            $statement->execute($productIds);
            $grouped = [];
            foreach ($statement->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
                $pid = (int) ($row['product_id'] ?? 0);
                if ($pid > 0) {
                    $grouped[$pid][] = $row;
                }
            }

            return $grouped;
        } catch (ReadOnlyQueryException $e) {
            return [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findByProductId(int $productId): array
    {
        if ($productId <= 0) {
            return [];
        }

        return $this->groupedByProductIds([$productId])[$productId] ?? [];
    }
}
