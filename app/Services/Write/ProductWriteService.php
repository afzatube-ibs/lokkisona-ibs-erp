<?php

namespace App\Services\Write;

use App\ActivityLog;
use App\Repositories\Write\ProductWriteRepository;

class ProductWriteService
{
    private ProductWriteRepository $repository;

    public function __construct(?ProductWriteRepository $repository = null)
    {
        $this->repository = $repository ?? new ProductWriteRepository();
    }

    public function tableReady(): bool
    {
        return $this->repository->tableExists();
    }

    public function create(array $input): WriteResult
    {
        if (!$this->tableReady()) {
            return WriteResult::fail('Product table not available. Apply migration 0003 first.');
        }

        $name = trim((string) ($input['product_name'] ?? ''));
        if ($name === '') {
            return WriteResult::fail('Product name is required.');
        }

        $id = $this->repository->create([
            'product_name' => $name,
            'business_source_id' => $this->nullableInt($input, 'business_source_id'),
            'supplier_id' => $this->nullableInt($input, 'supplier_id'),
            'supplier_model' => trim((string) ($input['supplier_model'] ?? '')) ?: null,
            'product_cost' => $this->nullableDecimal($input, 'product_cost'),
            'vendor_stock' => (int) ($input['vendor_stock'] ?? 0),
            'low_warning_threshold' => $this->nullableInt($input, 'low_warning_threshold'),
            'status' => $this->status($input),
        ]);

        ActivityLog::record('product_created', 'Product created', ['product_id' => $id]);

        return WriteResult::ok('Product created.', $id);
    }

    public function applyEdit(int $id, array $input): WriteResult
    {
        if (!$this->tableReady() || $this->repository->find($id) === null) {
            return WriteResult::fail('Product not found or table unavailable.');
        }

        $name = trim((string) ($input['product_name'] ?? ''));
        if ($name === '') {
            return WriteResult::fail('Product name is required.');
        }

        $this->repository->update($id, [
            'product_name' => $name,
            'business_source_id' => $this->nullableInt($input, 'business_source_id'),
            'supplier_id' => $this->nullableInt($input, 'supplier_id'),
            'supplier_model' => trim((string) ($input['supplier_model'] ?? '')) ?: null,
            'product_cost' => $this->nullableDecimal($input, 'product_cost'),
            'vendor_stock' => (int) ($input['vendor_stock'] ?? 0),
            'low_warning_threshold' => $this->nullableInt($input, 'low_warning_threshold'),
            'status' => $this->status($input),
        ]);

        ActivityLog::record('product_updated', 'Product updated', ['product_id' => $id]);

        return WriteResult::ok('Product updated.', $id);
    }

    private function nullableInt(array $input, string $key): ?int
    {
        $val = $input[$key] ?? null;

        return ($val === '' || $val === null) ? null : (int) $val;
    }

    private function nullableDecimal(array $input, string $key): ?float
    {
        $val = $input[$key] ?? null;
        if ($val === '' || $val === null) {
            return null;
        }

        return round((float) $val, 2);
    }

    private function status(array $input): string
    {
        $status = trim((string) ($input['status'] ?? 'active'));

        return in_array($status, ['active', 'inactive'], true) ? $status : 'active';
    }
}
