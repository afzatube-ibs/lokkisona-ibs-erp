<?php

namespace App\Services\Write;

use App\ActivityLog;
use App\Repositories\Write\ProductVariantWriteRepository;

class ProductVariantWriteService
{
    private ProductVariantWriteRepository $repository;

    public function __construct(?ProductVariantWriteRepository $repository = null)
    {
        $this->repository = $repository ?? new ProductVariantWriteRepository();
    }

    public function tableReady(): bool
    {
        return $this->repository->tableExists();
    }

    public function create(array $input): WriteResult
    {
        if (!$this->tableReady()) {
            return WriteResult::fail('Product variant table not available.');
        }

        $productId = (int) ($input['product_id'] ?? 0);
        if ($productId <= 0) {
            return WriteResult::fail('Product ID is required.');
        }

        $id = $this->repository->create([
            'product_id' => $productId,
            'option_name' => trim((string) ($input['option_name'] ?? '')) ?: null,
            'option_value' => trim((string) ($input['option_value'] ?? '')) ?: null,
            'supplier_model' => trim((string) ($input['supplier_model'] ?? '')) ?: null,
            'product_cost' => isset($input['product_cost']) && $input['product_cost'] !== '' ? round((float) $input['product_cost'], 2) : null,
            'vendor_stock' => (int) ($input['vendor_stock'] ?? 0),
            'status' => in_array($input['status'] ?? 'active', ['active', 'inactive'], true) ? $input['status'] : 'active',
        ]);

        ActivityLog::record('product_variant_created', 'Product variant created', ['product_variant_id' => $id]);

        return WriteResult::ok('Product variant created.', $id);
    }

    public function applyEdit(int $id, array $input): WriteResult
    {
        if (!$this->tableReady() || $this->repository->find($id) === null) {
            return WriteResult::fail('Product variant not found.');
        }

        $this->repository->update($id, [
            'option_name' => trim((string) ($input['option_name'] ?? '')) ?: null,
            'option_value' => trim((string) ($input['option_value'] ?? '')) ?: null,
            'supplier_model' => trim((string) ($input['supplier_model'] ?? '')) ?: null,
            'product_cost' => isset($input['product_cost']) && $input['product_cost'] !== '' ? round((float) $input['product_cost'], 2) : null,
            'vendor_stock' => (int) ($input['vendor_stock'] ?? 0),
            'status' => in_array($input['status'] ?? 'active', ['active', 'inactive'], true) ? $input['status'] : 'active',
        ]);

        ActivityLog::record('product_variant_updated', 'Product variant updated', ['product_variant_id' => $id]);

        return WriteResult::ok('Product variant updated.', $id);
    }
}
