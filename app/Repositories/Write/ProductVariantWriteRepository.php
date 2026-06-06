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
            . '(product_id, option_name, option_value, supplier_model, product_cost, vendor_stock, status, created_at) '
            . 'VALUES (:product_id, :option_name, :option_value, :supplier_model, :product_cost, :vendor_stock, :status, NOW())';
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

    public function updateSupplierControlFields(int $id, array $data): bool
    {
        $sql = 'UPDATE `' . $this->escapeIdentifier($this->table()) . '` SET '
            . 'supplier_model = :supplier_model, product_cost = :product_cost, vendor_stock = :vendor_stock, '
            . 'status = :status, updated_at = NOW() '
            . 'WHERE product_variant_id = :id';
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
