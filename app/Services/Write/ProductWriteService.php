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

        $businessSourceId = $this->nullableInt($input, 'business_source_id');
        $sourceProductId = $this->nullableSourceProductId($input);
        if ($sourceProductId !== null && $businessSourceId !== null) {
            $duplicate = $this->repository->findBySourceProductId($businessSourceId, $sourceProductId);
            if ($duplicate !== null) {
                return WriteResult::fail('Another product already uses this OpenCart source product ID for this business source.');
            }
        }

        $id = $this->repository->create([
            'product_name' => $name,
            'business_source_id' => $businessSourceId,
            'supplier_id' => $this->nullableInt($input, 'supplier_id'),
            'source_product_id' => $sourceProductId,
            'source_model' => null,
            'source_stock' => null,
            'last_synced_at' => null,
            'supplier_model' => trim((string) ($input['supplier_model'] ?? '')) ?: null,
            'supplier_product_category' => $this->nullableCategory($input),
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

        $businessSourceId = $this->nullableInt($input, 'business_source_id');
        $sourceProductId = $this->nullableSourceProductId($input);
        if ($sourceProductId !== null && $businessSourceId !== null) {
            $duplicate = $this->repository->findBySourceProductId($businessSourceId, $sourceProductId);
            if ($duplicate !== null && (int) ($duplicate['product_id'] ?? 0) !== $id) {
                return WriteResult::fail('Another product already uses this OpenCart source product ID for this business source.');
            }
        }

        $this->repository->update($id, [
            'product_name' => $name,
            'business_source_id' => $businessSourceId,
            'supplier_id' => $this->nullableInt($input, 'supplier_id'),
            'source_product_id' => $sourceProductId,
            'supplier_model' => trim((string) ($input['supplier_model'] ?? '')) ?: null,
            'supplier_product_category' => $this->nullableCategory($input),
            'product_cost' => $this->nullableDecimal($input, 'product_cost'),
            'vendor_stock' => (int) ($input['vendor_stock'] ?? 0),
            'low_warning_threshold' => $this->nullableInt($input, 'low_warning_threshold'),
            'status' => $this->status($input),
        ]);

        ActivityLog::record('product_updated', 'Product updated', ['product_id' => $id]);

        return WriteResult::ok('Product updated.', $id);
    }

    public function upsertWarehouseProducts(int $businessSourceId, array $products): WriteResult
    {
        if (!$this->tableReady()) {
            return WriteResult::fail('Product table not available. Apply migration 0003 first.');
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        foreach ($products as $row) {
            if (!is_array($row)) {
                $skipped++;
                continue;
            }

            if ((int) ($row['from_warehouse'] ?? 0) !== 1) {
                $skipped++;
                continue;
            }

            $sourceProductId = trim((string) ($row['source_product_id'] ?? ''));
            if ($sourceProductId === '') {
                $skipped++;
                continue;
            }

            $name = trim((string) ($row['product_name'] ?? ''));
            if ($name === '') {
                $name = 'OC Product ' . $sourceProductId;
            }

            $syncedAt = date('Y-m-d H:i:s');
            $existing = $this->repository->findBySourceProductId($businessSourceId, $sourceProductId);
            if ($existing !== null) {
                $this->repository->updatePlatformSyncFields((int) $existing['product_id'], [
                    'source_model' => trim((string) ($row['source_model'] ?? '')) ?: null,
                    'source_stock' => isset($row['source_stock']) ? (int) $row['source_stock'] : null,
                    'last_synced_at' => $syncedAt,
                ]);
                $updated++;
                continue;
            }

            $this->repository->create([
                'product_name' => $name,
                'business_source_id' => $businessSourceId,
                'supplier_id' => null,
                'source_product_id' => $sourceProductId,
                'source_model' => trim((string) ($row['source_model'] ?? '')) ?: null,
                'source_stock' => isset($row['source_stock']) ? (int) $row['source_stock'] : null,
                'last_synced_at' => $syncedAt,
                'supplier_model' => null,
                'supplier_product_category' => null,
                'product_cost' => null,
                'vendor_stock' => 0,
                'low_warning_threshold' => null,
                'status' => 'active',
            ]);
            $created++;
        }

        ActivityLog::record('warehouse_product_pull', 'Warehouse product pull finished', [
            'business_source_id' => $businessSourceId,
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
        ]);

        return WriteResult::ok('Warehouse product pull: ' . $created . ' created, ' . $updated . ' updated, ' . $skipped . ' skipped.');
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

    private function nullableCategory(array $input): ?string
    {
        $category = trim((string) ($input['supplier_product_category'] ?? ''));

        return $category !== '' ? $category : null;
    }

    private function nullableSourceProductId(array $input): ?string
    {
        $id = trim((string) ($input['source_product_id'] ?? ''));

        return $id !== '' ? $id : null;
    }

    private function status(array $input): string
    {
        $status = trim((string) ($input['status'] ?? 'active'));

        return in_array($status, ['active', 'inactive'], true) ? $status : 'active';
    }
}
