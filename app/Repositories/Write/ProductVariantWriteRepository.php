<?php

namespace App\Repositories\Write;

use App\Models\ProductVariant;

class ProductVariantWriteRepository extends BaseWriteRepository
{
    public function modelClass(): string
    {
        return ProductVariant::class;
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO `' . $this->escapeIdentifier($this->table()) . '` '
            . '(product_id, option_name, option_value, source_option_id, source_option_value_id, source_model, source_stock, '
            . 'option_image_path, supplier_model, product_cost, vendor_stock, status, created_at) '
            . 'VALUES (:product_id, :option_name, :option_value, :source_option_id, :source_option_value_id, :source_model, :source_stock, '
            . ':option_image_path, :supplier_model, :product_cost, :vendor_stock, :status, NOW())';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($data);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $sql = 'UPDATE `' . $this->escapeIdentifier($this->table()) . '` SET '
            . 'option_name = :option_name, option_value = :option_value, supplier_model = :supplier_model, '
            . 'product_cost = :product_cost, vendor_stock = :vendor_stock, status = :status, updated_at = NOW() '
            . 'WHERE product_variant_id = :id';
        $data['id'] = $id;
        $statement = $this->pdo->prepare($sql);

        return $statement->execute($data);
    }

    public function find(int $id): ?array
    {
        return $this->findById($id);
    }

    public function findBySourceOption(int $productId, string $sourceOptionId, string $sourceOptionValueId): ?array
    {
        if (!$this->tableExists() || $productId <= 0) {
            return null;
        }

        $sql = 'SELECT * FROM `' . $this->escapeIdentifier($this->table()) . '` '
            . 'WHERE product_id = :product_id AND source_option_id = :source_option_id '
            . 'AND source_option_value_id = :source_option_value_id LIMIT 1';
        $statement = $this->pdo->prepare($sql);
        $statement->execute([
            'product_id' => $productId,
            'source_option_id' => $sourceOptionId,
            'source_option_value_id' => $sourceOptionValueId,
        ]);
        $row = $statement->fetch(\PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }

    public function forProduct(int $productId): array
    {
        if (!$this->tableExists() || $productId <= 0) {
            return [];
        }

        $sql = 'SELECT * FROM `' . $this->escapeIdentifier($this->table()) . '` WHERE product_id = :product_id ORDER BY product_variant_id ASC';
        $statement = $this->pdo->prepare($sql);
        $statement->execute(['product_id' => $productId]);

        return $statement->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    public function updatePlatformSyncFields(int $id, array $data): bool
    {
        $sql = 'UPDATE `' . $this->escapeIdentifier($this->table()) . '` SET '
            . 'option_name = :option_name, option_value = :option_value, source_model = :source_model, '
            . 'source_stock = :source_stock, option_image_path = :option_image_path, updated_at = NOW() '
            . 'WHERE product_variant_id = :id';
        $data['id'] = $id;
        $statement = $this->pdo->prepare($sql);

        return $statement->execute($data);
    }

    public function updateSupplierControlFields(int $id, array $data): bool
    {
        $fields = [
            'supplier_model = :supplier_model',
            'product_cost = :product_cost',
            'vendor_stock = :vendor_stock',
            'status = :status',
        ];

        if (array_key_exists('supplier_note', $data)) {
            $fields[] = 'supplier_note = :supplier_note';
        }

        $sql = 'UPDATE `' . $this->escapeIdentifier($this->table()) . '` SET '
            . implode(', ', $fields) . ', updated_at = NOW() WHERE product_variant_id = :id';
        $data['id'] = $id;
        $statement = $this->pdo->prepare($sql);

        return $statement->execute($data);
    }

    public function updateCostStock(int $id, ?float $cost, ?int $stock): bool
    {
        $sql = 'UPDATE `' . $this->escapeIdentifier($this->table()) . '` SET '
            . 'product_cost = :product_cost, vendor_stock = :vendor_stock, updated_at = NOW() WHERE product_variant_id = :id';
        $statement = $this->pdo->prepare($sql);

        return $statement->execute(['product_cost' => $cost, 'vendor_stock' => $stock, 'id' => $id]);
    }
}
