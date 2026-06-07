<?php

namespace App\Services\ReadOnly;

/**
 * ERP catalog gate for supplier-synced products only (v1.8.5).
 * Products must have been imported via sync (source_product_id + last_synced_at).
 */
class SupplierProductFilter
{
    public function filterProductInventory(array $inventory): array
    {
        $excluded = $this->nonSupplierSourceIds();
        $rows = array_values(array_filter(
            $inventory['rows'] ?? [],
            fn (array $row): bool => $this->isSupplierSyncedProduct($row, $excluded)
        ));
        $inventory['rows'] = $rows;
        $inventory['row_count'] = count($rows);
        if ($rows === [] && ($inventory['status'] ?? '') === 'ok') {
            $inventory['status'] = 'empty';
            $inventory['status_message'] = 'No supplier-synced products yet. Owner: import from Sync Preview (from_warehouse = 1 only).';
        }

        return $inventory;
    }

    public function filterVariantInventory(array $variantInventory, array $productInventory): array
    {
        $productIds = [];
        foreach ($productInventory['rows'] ?? [] as $product) {
            $pid = (int) ($product['product_id'] ?? 0);
            if ($pid > 0) {
                $productIds[$pid] = true;
            }
        }

        $rows = array_values(array_filter(
            $variantInventory['rows'] ?? [],
            static fn (array $row): bool => isset($productIds[(int) ($row['product_id'] ?? 0)])
        ));
        $variantInventory['rows'] = $rows;
        $variantInventory['row_count'] = count($rows);
        if ($rows === [] && ($variantInventory['status'] ?? '') === 'ok') {
            $variantInventory['status'] = 'empty';
        }

        return $variantInventory;
    }

    public function filterHistoryInventory(array $inventory, array $productInventory): array
    {
        $productIds = [];
        foreach ($productInventory['rows'] ?? [] as $product) {
            $pid = (int) ($product['product_id'] ?? 0);
            if ($pid > 0) {
                $productIds[$pid] = true;
            }
        }

        $rows = array_values(array_filter(
            $inventory['rows'] ?? [],
            static fn (array $row): bool => isset($productIds[(int) ($row['product_id'] ?? 0)])
        ));
        $inventory['rows'] = $rows;
        $inventory['row_count'] = count($rows);

        return $inventory;
    }

    public function isSupplierSyncedProduct(array $row, ?array $excludedSourceIds = null): bool
    {
        $sourceProductId = trim((string) ($row['source_product_id'] ?? ''));
        $lastSynced = trim((string) ($row['last_synced_at'] ?? ''));
        if ($sourceProductId === '' || $lastSynced === '') {
            return false;
        }

        $excluded = $excludedSourceIds ?? $this->nonSupplierSourceIds();

        return !in_array($sourceProductId, $excluded, true);
    }

    /**
     * @return array<int, string>
     */
    public function nonSupplierSourceIds(): array
    {
        $ids = [];
        foreach (config('opencart.demo_warehouse_products', []) as $product) {
            if (!is_array($product)) {
                continue;
            }
            if ((int) ($product['from_warehouse'] ?? 0) === 1) {
                continue;
            }
            $id = trim((string) ($product['product_id'] ?? $product['source_product_id'] ?? ''));
            if ($id !== '') {
                $ids[] = $id;
            }
        }

        return $ids;
    }
}
