<?php

namespace App\Support;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Repositories\BusinessSourceRepository;
use App\Repositories\SupplierRepository;
use App\Services\ReadOnly\ProductReadService;
use App\Services\ReadOnly\ProductVariantReadService;

class ManualOrderFormOptions
{
    /**
     * @return array{
     *     businessSourceOptions: array<int, array{id: int, label: string}>,
     *     supplierOptions: array<int, array{id: int, label: string}>,
     *     productOptions: array<int, array{id: int, label: string}>,
     *     variantOptionsByProduct: array<int, array<int, array{id: int, label: string, cost: float}>>,
     *     productCostById: array<int, float>
     * }
     */
    public static function forCreateForm(): array
    {
        $productReadInventory = self::productReadInventory();
        $variantReadInventory = self::variantReadInventory();

        return [
            'businessSourceOptions' => self::businessSourceOptions(),
            'supplierOptions' => self::supplierOptions(),
            'productOptions' => self::productSelectOptionsFromInventory($productReadInventory),
            'variantOptionsByProduct' => self::variantOptionsByProductFromInventory(
                $productReadInventory,
                $variantReadInventory
            ),
            'productCostById' => self::productCostMapFromInventory($productReadInventory),
        ];
    }

    private static function productReadInventory(): array
    {
        return self::readInventoryRows(new ProductReadService(), Product::class);
    }

    private static function variantReadInventory(): array
    {
        return self::readInventoryRows(new ProductVariantReadService(), ProductVariant::class);
    }

    private static function readInventoryRows(object $service, string $modelClass): array
    {
        $defaults = ['status' => 'error', 'rows' => []];

        try {
            if (!$service->tableExists()) {
                $defaults['status'] = 'table_missing';

                return $defaults;
            }

            $defaults['status'] = $service->count() === 0 ? 'empty' : 'ok';
            $defaults['rows'] = $service->all(50, 0);

            return $defaults;
        } catch (\Throwable $e) {
            return $defaults;
        }
    }

    private static function businessSourceOptions(): array
    {
        try {
            $repo = new BusinessSourceRepository();
            if (!$repo->tableExists()) {
                return [];
            }

            $options = [];
            foreach ($repo->all(100, 0) as $row) {
                $id = (int) ($row['business_source_id'] ?? 0);
                if ($id <= 0) {
                    continue;
                }
                $name = trim((string) ($row['source_name'] ?? ''));
                $options[] = ['id' => $id, 'label' => $name !== '' ? $name : 'Source #' . $id];
            }

            return $options;
        } catch (\Throwable $e) {
            return [];
        }
    }

    private static function supplierOptions(): array
    {
        try {
            $repo = new SupplierRepository();
            if (!$repo->tableExists()) {
                return [];
            }

            $options = [];
            foreach ($repo->all(100, 0) as $row) {
                $id = (int) ($row['supplier_id'] ?? 0);
                if ($id <= 0) {
                    continue;
                }
                $name = trim((string) ($row['supplier_name'] ?? $row['name'] ?? ''));
                $options[] = ['id' => $id, 'label' => $name !== '' ? $name : 'Supplier #' . $id];
            }

            return $options;
        } catch (\Throwable $e) {
            return [];
        }
    }

    private static function productSelectOptionsFromInventory(array $productReadInventory): array
    {
        if (!in_array($productReadInventory['status'] ?? '', ['ok', 'empty'], true)) {
            return [];
        }

        $options = [];
        foreach ($productReadInventory['rows'] ?? [] as $row) {
            $id = (int) ($row['product_id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $name = trim((string) ($row['product_name'] ?? ''));
            $options[] = ['id' => $id, 'label' => $name !== '' ? $name : 'Product #' . $id];
        }

        return $options;
    }

    private static function variantOptionsByProductFromInventory(array $productReadInventory, array $variantReadInventory): array
    {
        if (!in_array($variantReadInventory['status'] ?? '', ['ok', 'empty'], true)) {
            return [];
        }

        $productCosts = self::productCostMapFromInventory($productReadInventory);
        $map = [];

        foreach ($variantReadInventory['rows'] ?? [] as $row) {
            $productId = (int) ($row['product_id'] ?? 0);
            $variantId = (int) ($row['product_variant_id'] ?? 0);
            if ($productId <= 0 || $variantId <= 0) {
                continue;
            }

            $optionName = trim((string) ($row['option_name'] ?? ''));
            $optionValue = trim((string) ($row['option_value'] ?? ''));
            $label = trim($optionName . ($optionValue !== '' ? ': ' . $optionValue : ''));
            if ($label === '') {
                $label = 'Variant #' . $variantId;
            }

            $cost = $row['product_cost'] ?? null;
            if ($cost === null || $cost === '') {
                $cost = $productCosts[$productId] ?? 0;
            }

            $map[$productId][] = [
                'id' => $variantId,
                'label' => $label,
                'cost' => round((float) $cost, 2),
            ];
        }

        return $map;
    }

    private static function productCostMapFromInventory(array $productReadInventory): array
    {
        $costs = [];
        foreach ($productReadInventory['rows'] ?? [] as $row) {
            $id = (int) ($row['product_id'] ?? 0);
            if ($id > 0) {
                $costs[$id] = round((float) ($row['product_cost'] ?? 0), 2);
            }
        }

        return $costs;
    }
}
