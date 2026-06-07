<?php

namespace App\Repositories\Write;

use App\Models\Product;
use App\ReadFoundation\WriteGate;

class ProductWriteRepository extends BaseWriteRepository
{
    public function modelClass(): string
    {
        return Product::class;
    }

    public function create(array $data): int
    {
        $columns = [
            'product_name', 'business_source_id', 'supplier_id', 'source_product_id', 'source_model', 'source_stock',
            'last_synced_at', 'supplier_model', 'product_cost', 'vendor_stock',
            'low_warning_threshold', 'status',
        ];
        $values = [
            ':product_name', ':business_source_id', ':supplier_id', ':source_product_id', ':source_model', ':source_stock',
            ':last_synced_at', ':supplier_model', ':product_cost', ':vendor_stock',
            ':low_warning_threshold', ':status',
        ];

        if ($this->supplierProductCategoryColumnReady() && array_key_exists('supplier_product_category', $data)) {
            $columns[] = 'supplier_product_category';
            $values[] = ':supplier_product_category';
        }
        if (array_key_exists('image_path', $data)) {
            $columns[] = 'image_path';
            $values[] = ':image_path';
        }
        if ($this->syncOptionsStateColumnReady() && array_key_exists('sync_options_state', $data)) {
            $columns[] = 'sync_options_state';
            $values[] = ':sync_options_state';
        }

        $sql = 'INSERT INTO `' . $this->escapeIdentifier($this->table()) . '` ('
            . implode(', ', $columns) . ', created_at) VALUES ('
            . implode(', ', $values) . ', NOW())';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($data);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $fields = [
            'product_name = :product_name',
            'business_source_id = :business_source_id',
            'supplier_id = :supplier_id',
            'source_product_id = :source_product_id',
            'supplier_model = :supplier_model',
            'product_cost = :product_cost',
            'vendor_stock = :vendor_stock',
            'low_warning_threshold = :low_warning_threshold',
            'status = :status',
        ];
        if ($this->supplierProductCategoryColumnReady() && array_key_exists('supplier_product_category', $data)) {
            $fields[] = 'supplier_product_category = :supplier_product_category';
        }

        $sql = 'UPDATE `' . $this->escapeIdentifier($this->table()) . '` SET '
            . implode(', ', $fields) . ', updated_at = NOW() '
            . 'WHERE product_id = :id';
        $data['id'] = $id;
        $statement = $this->pdo->prepare($sql);

        return $statement->execute($data);
    }

    public function findBySourceProductId(int $businessSourceId, string $sourceProductId): ?array
    {
        if ($sourceProductId === '' || !$this->tableExists()) {
            return null;
        }

        $sql = 'SELECT * FROM `' . $this->escapeIdentifier($this->table()) . '` '
            . 'WHERE business_source_id = :business_source_id AND source_product_id = :source_product_id LIMIT 1';
        $statement = $this->pdo->prepare($sql);
        $statement->execute([
            'business_source_id' => $businessSourceId,
            'source_product_id' => $sourceProductId,
        ]);
        $row = $statement->fetch(\PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }

    public function updatePlatformSyncFields(int $id, array $data): bool
    {
        $fields = [
            'product_name = :product_name',
            'source_model = :source_model',
            'source_stock = :source_stock',
            'last_synced_at = :last_synced_at',
        ];

        if (array_key_exists('image_path', $data)) {
            $fields[] = 'image_path = :image_path';
        }
        if ($this->syncOptionsStateColumnReady() && array_key_exists('sync_options_state', $data)) {
            $fields[] = 'sync_options_state = :sync_options_state';
        }

        $sql = 'UPDATE `' . $this->escapeIdentifier($this->table()) . '` SET '
            . implode(', ', $fields) . ', updated_at = NOW() WHERE product_id = :id';
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
            'low_warning_threshold = :low_warning_threshold',
            'status = :status',
        ];

        if ($this->supplierProductCategoryColumnReady() && array_key_exists('supplier_product_category', $data)) {
            $fields[] = 'supplier_product_category = :supplier_product_category';
        }
        if ($this->supplierNoteColumnReady() && array_key_exists('supplier_note', $data)) {
            $fields[] = 'supplier_note = :supplier_note';
        }

        $sql = 'UPDATE `' . $this->escapeIdentifier($this->table()) . '` SET '
            . implode(', ', $fields) . ', updated_at = NOW() WHERE product_id = :id';
        $data['id'] = $id;
        $statement = $this->pdo->prepare($sql);

        return $statement->execute($data);
    }

    public function updateSupplierAssignment(int $id, ?int $supplierId): bool
    {
        $sql = 'UPDATE `' . $this->escapeIdentifier($this->table()) . '` SET '
            . 'supplier_id = :supplier_id, updated_at = NOW() WHERE product_id = :id';
        $statement = $this->pdo->prepare($sql);

        return $statement->execute(['supplier_id' => $supplierId, 'id' => $id]);
    }

    public function find(int $id): ?array
    {
        return $this->findById($id);
    }

    public function updateCostStock(int $id, ?float $cost, ?int $stock): bool
    {
        $sql = 'UPDATE `' . $this->escapeIdentifier($this->table()) . '` SET '
            . 'product_cost = :product_cost, vendor_stock = :vendor_stock, updated_at = NOW() WHERE product_id = :id';
        $statement = $this->pdo->prepare($sql);

        return $statement->execute(['product_cost' => $cost, 'vendor_stock' => $stock, 'id' => $id]);
    }

    public function updateLowWarningThreshold(int $id, ?int $threshold): bool
    {
        $sql = 'UPDATE `' . $this->escapeIdentifier($this->table()) . '` SET '
            . 'low_warning_threshold = :low_warning_threshold, updated_at = NOW() WHERE product_id = :id';
        $statement = $this->pdo->prepare($sql);

        return $statement->execute(['low_warning_threshold' => $threshold, 'id' => $id]);
    }

    public function supplierNoteColumnReady(): bool
    {
        return (bool) (WriteGate::supplierProductNoteColumn()['ready'] ?? false);
    }

    public function supplierProductCategoryColumnReady(): bool
    {
        return (bool) (WriteGate::supplierProductCategoryColumn()['ready'] ?? false);
    }

    public function syncOptionsStateColumnReady(): bool
    {
        return (bool) (WriteGate::syncOptionsStateColumn()['ready'] ?? false);
    }
}
