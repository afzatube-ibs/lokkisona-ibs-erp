<?php

namespace App\Services\Write;

use App\ActivityLog;
use App\Repositories\Write\ProductVariantWriteRepository;
use App\Repositories\Write\ProductWriteRepository;

class ProductWriteService
{
    private ProductWriteRepository $repository;
    private ProductVariantWriteRepository $variants;

    public function __construct(
        ?ProductWriteRepository $repository = null,
        ?ProductVariantWriteRepository $variants = null
    ) {
        $this->repository = $repository ?? new ProductWriteRepository();
        $this->variants = $variants ?? new ProductVariantWriteRepository();
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

        $createPayload = [
            'product_name' => $name,
            'business_source_id' => $businessSourceId,
            'supplier_id' => $this->nullableInt($input, 'supplier_id'),
            'source_product_id' => $sourceProductId,
            'source_model' => null,
            'source_stock' => null,
            'last_synced_at' => null,
            'supplier_model' => trim((string) ($input['supplier_model'] ?? '')) ?: null,
            'product_cost' => $this->nullableDecimal($input, 'product_cost'),
            'vendor_stock' => (int) ($input['vendor_stock'] ?? 0),
            'low_warning_threshold' => $this->nullableInt($input, 'low_warning_threshold'),
            'status' => $this->status($input),
        ];
        if ($this->repository->supplierProductCategoryColumnReady()) {
            $createPayload['supplier_product_category'] = $this->nullableCategory($input);
        }

        $id = $this->repository->create($createPayload);

        ActivityLog::record('product_created', 'Product created', ['product_id' => $id]);

        return WriteResult::ok('Product created.', $id);
    }

    public function applyEdit(int $id, array $input): WriteResult
    {
        return (new ProductWorkspaceWriteService())->save($id, $input);
    }

    public function upsertWarehouseProducts(int $businessSourceId, array $products): WriteResult
    {
        if (!$this->tableReady()) {
            return WriteResult::fail('Product table not available. Apply migration 0003 first.');
        }

        $defaultSupplierId = $this->defaultSupplierId();
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $variantsCreated = 0;
        $variantsUpdated = 0;
        $variantsSkipped = 0;
        $errors = [];
        $limit = max(1, (int) config('opencart.max_products_per_page', 20));
        $processed = 0;

        foreach ($products as $row) {
            if ($processed >= $limit) {
                break;
            }

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

            try {
                $syncedAt = date('Y-m-d H:i:s');
                $platformFields = [
                    'product_name' => $name,
                    'source_model' => trim((string) ($row['source_model'] ?? '')) ?: null,
                    'source_stock' => isset($row['source_stock']) ? (int) $row['source_stock'] : null,
                    'last_synced_at' => $syncedAt,
                ];
                if ($this->repository->imagePathColumnReady()) {
                    $platformFields['image_path'] = trim((string) ($row['image_path'] ?? '')) ?: null;
                }
                if ($this->repository->syncOptionsStateColumnReady()) {
                    $platformFields['sync_options_state'] = (string) ($row['sync_options_state'] ?? 'simple');
                }

                $existing = $this->repository->findBySourceProductId($businessSourceId, $sourceProductId);
                if ($existing !== null) {
                    $productId = (int) $existing['product_id'];
                    $this->repository->updatePlatformSyncFields($productId, $platformFields);
                    $variantStats = $this->upsertWarehouseVariants($productId, $row['options'] ?? []);
                    $variantsCreated += $variantStats['created'];
                    $variantsUpdated += $variantStats['updated'];
                    $variantsSkipped += $variantStats['skipped'];
                    $updated++;
                    $processed++;
                    continue;
                }

                $createData = [
                    'product_name' => $name,
                    'business_source_id' => $businessSourceId,
                    'supplier_id' => $defaultSupplierId,
                    'source_product_id' => $sourceProductId,
                    'source_model' => trim((string) ($row['source_model'] ?? '')) ?: null,
                    'source_stock' => isset($row['source_stock']) ? (int) $row['source_stock'] : null,
                    'last_synced_at' => $syncedAt,
                    'supplier_model' => null,
                    'product_cost' => null,
                    'vendor_stock' => 0,
                    'low_warning_threshold' => null,
                    'status' => 'active',
                ];
                if ($this->repository->imagePathColumnReady()) {
                    $createData['image_path'] = trim((string) ($row['image_path'] ?? '')) ?: null;
                }
                if ($this->repository->syncOptionsStateColumnReady()) {
                    $createData['sync_options_state'] = (string) ($row['sync_options_state'] ?? 'simple');
                }

                $productId = $this->repository->create($createData);
                $variantStats = $this->upsertWarehouseVariants($productId, $row['options'] ?? []);
                $variantsCreated += $variantStats['created'];
                $variantsUpdated += $variantStats['updated'];
                $variantsSkipped += $variantStats['skipped'];
                $created++;
                $processed++;
            } catch (\Throwable $e) {
                $skipped++;
                $errors[] = $sourceProductId . ': ' . $e->getMessage();
            }
        }

        ActivityLog::record('warehouse_product_pull', 'Warehouse product pull finished', [
            'business_source_id' => $businessSourceId,
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'variants_created' => $variantsCreated,
            'variants_updated' => $variantsUpdated,
            'variants_skipped' => $variantsSkipped,
            'errors' => $errors,
        ]);

        $productsImported = $created + $updated;
        $variantsImported = $variantsCreated + $variantsUpdated;
        $totalSkipped = $skipped + $variantsSkipped;

        $message = 'Products imported: ' . $productsImported . '. '
            . 'Variants imported: ' . $variantsImported . '. '
            . 'Skipped: ' . $totalSkipped . '.';
        if ($errors !== []) {
            $message .= ' Errors: ' . implode('; ', array_slice($errors, 0, 3));
            if (count($errors) > 3) {
                $message .= ' (+' . (count($errors) - 3) . ' more)';
            }
        }
        if (!$this->repository->supplierProductCategoryColumnReady()) {
            $message .= ' Dev note: supplier_product_category column not applied (migration 0011) — category field skipped.';
        }

        return WriteResult::ok($message);
    }

    /**
     * @return array{created:int,updated:int,skipped:int}
     */
    private function upsertWarehouseVariants(int $productId, array $options): array
    {
        $stats = ['created' => 0, 'updated' => 0, 'skipped' => 0];
        if (!$this->variants->tableExists() || $productId <= 0) {
            return $stats;
        }

        foreach ($options as $option) {
            if (!is_array($option)) {
                $stats['skipped']++;
                continue;
            }

            $sourceOptionId = trim((string) ($option['source_option_id'] ?? ''));
            $sourceOptionValueId = trim((string) ($option['source_option_value_id'] ?? ''));
            if ($sourceOptionId === '' || $sourceOptionValueId === '') {
                $stats['skipped']++;
                continue;
            }

            $platformFields = [
                'option_name' => (string) ($option['option_name'] ?? 'Option'),
                'option_value' => (string) ($option['option_value'] ?? ''),
                'source_model' => trim((string) ($option['source_model'] ?? '')) ?: null,
                'source_stock' => isset($option['source_stock']) ? (int) $option['source_stock'] : null,
                'option_image_path' => trim((string) ($option['option_image_path'] ?? '')) ?: null,
            ];

            $existing = $this->variants->findBySourceOption($productId, $sourceOptionId, $sourceOptionValueId);
            if ($existing !== null) {
                $this->variants->updatePlatformSyncFields((int) $existing['product_variant_id'], $platformFields);
                $stats['updated']++;
                continue;
            }

            $this->variants->create([
                'product_id' => $productId,
                'option_name' => $platformFields['option_name'],
                'option_value' => $platformFields['option_value'],
                'source_option_id' => $sourceOptionId,
                'source_option_value_id' => $sourceOptionValueId,
                'source_model' => $platformFields['source_model'],
                'source_stock' => $platformFields['source_stock'],
                'option_image_path' => $platformFields['option_image_path'],
                'supplier_model' => null,
                'product_cost' => null,
                'vendor_stock' => 0,
                'status' => 'active',
            ]);
            $stats['created']++;
        }

        return $stats;
    }

    private function defaultSupplierId(): ?int
    {
        $id = (int) config('opencart.default_supplier_id', 0);

        return $id > 0 ? $id : null;
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
