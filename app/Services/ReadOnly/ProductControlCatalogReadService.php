<?php

namespace App\Services\ReadOnly;

class ProductControlCatalogReadService
{
    public function build(array $productRows, array $variantRows, bool $isSupplierView = false): array
    {
        $variantsByProduct = [];
        foreach ($variantRows as $variant) {
            $productId = (int) ($variant['product_id'] ?? 0);
            if ($productId <= 0) {
                continue;
            }
            $variantsByProduct[$productId][] = $variant;
        }

        $catalogRows = [];
        $workspaces = [];

        foreach ($productRows as $product) {
            $productId = (int) ($product['product_id'] ?? 0);
            if ($productId <= 0) {
                continue;
            }

            $variants = $variantsByProduct[$productId] ?? [];
            $summary = $this->summarizeProduct($product, $variants, $isSupplierView);
            $catalogRows[] = $summary['catalog'];
            $workspaces[(string) $productId] = $summary['workspace'];
        }

        return [
            'kpis' => $this->summarizeKpis($catalogRows),
            'rows' => $catalogRows,
            'workspaces' => $workspaces,
        ];
    }

    public function summarizeKpis(array $catalogRows): array
    {
        $readyCount = 0;
        $needsWorkCount = 0;
        $variantLineCount = 0;

        foreach ($catalogRows as $row) {
            if (($row['completeness'] ?? '') === 'ready') {
                $readyCount++;
            } else {
                $needsWorkCount++;
            }
            $variantLineCount += (int) ($row['variant_count'] ?? 0);
        }

        return [
            'total_products' => count($catalogRows),
            'ready' => $readyCount,
            'variants' => $variantLineCount,
            'needs_work' => $needsWorkCount,
        ];
    }

    private function summarizeProduct(array $product, array $variants, bool $isSupplierView): array
    {
        $productId = (int) ($product['product_id'] ?? 0);
        $hasVariants = $variants !== [];
        $syncOptionsState = (string) ($product['sync_options_state'] ?? '');
        $noOptionsSynced = !$hasVariants && $syncOptionsState === 'missing_options';
        $isVariable = $hasVariants || $noOptionsSynced;
        $costLabel = $isSupplierView ? 'sale' : 'cost';
        $health = $this->healthStatus($product, $variants, $isSupplierView, $noOptionsSynced);
        $costDisplay = $this->costDisplay($product, $variants);
        $vendorStock = $this->vendorStockTotal($product, $variants);
        $ownerStock = $hasVariants
            ? $this->sumField($variants, 'source_stock')
            : (int) ($product['source_stock'] ?? 0);
        $syncStatus = $this->syncStatusLabel($product, $noOptionsSynced);

        $workspaceVariants = [];
        if ($hasVariants) {
            foreach ($variants as $variant) {
                $variantId = (int) ($variant['product_variant_id'] ?? 0);
                $lineHealth = $this->variantHealth($variant, $product, $isSupplierView);
                $workspaceVariants[] = [
                    'product_variant_id' => $variantId,
                    'line_label' => trim((string) ($variant['option_name'] ?? '') . ': ' . (string) ($variant['option_value'] ?? ''), ': '),
                    'option_name' => (string) ($variant['option_name'] ?? ''),
                    'option_value' => (string) ($variant['option_value'] ?? ''),
                    'image_path' => (string) ($variant['option_image_path'] ?? ''),
                    'source_model' => (string) ($variant['source_model'] ?? ''),
                    'supplier_model' => (string) ($variant['supplier_model'] ?? ''),
                    'supplier_note' => (string) ($variant['supplier_note'] ?? ''),
                    'source_stock' => $variant['source_stock'] ?? null,
                    'product_cost' => $variant['product_cost'] ?? '',
                    'vendor_stock' => (int) ($variant['vendor_stock'] ?? 0),
                    'warning' => $lineHealth['low_stock'] ? 'Low' : '',
                    'health' => $lineHealth['label'],
                    'status' => (string) ($variant['status'] ?? 'active'),
                ];
            }
        }

        $lastSynced = (string) ($product['last_synced_at'] ?? '');
        $lastSyncedDisplay = $lastSynced !== '' ? $lastSynced : '—';

        return [
            'catalog' => [
                'product_id' => $productId,
                'source_product_id' => (string) ($product['source_product_id'] ?? ''),
                'product_name' => (string) ($product['product_name'] ?? ''),
                'image_path' => (string) ($product['image_path'] ?? ''),
                'type' => $isVariable ? 'variable' : 'simple',
                'no_options_synced' => $noOptionsSynced,
                'source_model' => (string) ($product['source_model'] ?? ''),
                'supplier_model' => (string) ($product['supplier_model'] ?? ''),
                'average_cost' => $costDisplay,
                'owner_stock' => $ownerStock,
                'vendor_stock' => $vendorStock,
                'low_warning_threshold' => $product['low_warning_threshold'] ?? null,
                'low_warning' => $health['low_stock'],
                'badges' => $health['badges'],
                'completeness' => $health['completeness'],
                'health_label' => $health['primary_label'],
                'health_class' => $health['class'],
                'sync_status' => $syncStatus,
                'filter_flags' => $health['filter_flags'],
                'supplier_product_category' => (string) ($product['supplier_product_category'] ?? ''),
                'last_synced_at' => $lastSyncedDisplay,
                'status' => (string) ($product['status'] ?? 'active'),
                'variant_count' => count($variants),
                'search_blob' => strtolower(implode(' ', array_filter([
                    (string) ($product['product_name'] ?? ''),
                    (string) ($product['supplier_model'] ?? ''),
                    (string) ($product['source_model'] ?? ''),
                    (string) ($product['source_product_id'] ?? ''),
                    (string) ($product['supplier_product_category'] ?? ''),
                    (string) $productId,
                ]))),
            ],
            'workspace' => [
                'product_id' => $productId,
                'source_product_id' => (string) ($product['source_product_id'] ?? ''),
                'product_name' => (string) ($product['product_name'] ?? ''),
                'supplier_model' => (string) ($product['supplier_model'] ?? ''),
                'supplier_product_category' => (string) ($product['supplier_product_category'] ?? ''),
                'supplier_note' => (string) ($product['supplier_note'] ?? ''),
                'supplier_id' => $product['supplier_id'] ?? '',
                'source_model' => (string) ($product['source_model'] ?? ''),
                'source_stock' => $product['source_stock'] ?? null,
                'last_synced_at' => $lastSynced,
                'sync_status' => $syncStatus,
                'completeness' => $health['completeness'],
                'badges' => $health['badges'],
                'low_warning_threshold' => $product['low_warning_threshold'] ?? '',
                'image_path' => (string) ($product['image_path'] ?? ''),
                'type' => $isVariable ? 'variable' : 'simple',
                'no_options_synced' => $noOptionsSynced,
                'product_cost' => $product['product_cost'] ?? '',
                'vendor_stock' => (int) ($product['vendor_stock'] ?? 0),
                'business_source_id' => $product['business_source_id'] ?? '',
                'status' => (string) ($product['status'] ?? 'active'),
                'cost_label' => $costLabel,
                'variants' => $workspaceVariants,
            ],
        ];
    }

    private function healthStatus(array $product, array $variants, bool $isSupplierView, bool $noOptionsSynced = false): array
    {
        $missingModel = false;
        $missingCost = false;
        $lowWarning = (int) ($product['low_warning_threshold'] ?? 0);
        $vendorStock = $this->vendorStockTotal($product, $variants);
        $lowStock = $lowWarning > 0 && $vendorStock <= $lowWarning;
        $syncRequired = $noOptionsSynced || trim((string) ($product['last_synced_at'] ?? '')) === '';

        if ($variants === []) {
            $missingModel = trim((string) ($product['supplier_model'] ?? '')) === '';
            $missingCost = $product['product_cost'] === null || $product['product_cost'] === '';
        } else {
            foreach ($variants as $variant) {
                if (trim((string) ($variant['supplier_model'] ?? '')) === '') {
                    $missingModel = true;
                }
                if ($variant['product_cost'] === null || $variant['product_cost'] === '') {
                    $missingCost = true;
                }
            }
        }

        $needsWork = $missingModel || $missingCost;
        $isReady = !$needsWork;

        $badges = [];
        if ($isReady) {
            $badges[] = ['label' => 'Ready', 'class' => 'ok'];
        } else {
            $badges[] = ['label' => 'Needs Work', 'class' => 'warn'];
        }
        if ($lowStock) {
            $badges[] = ['label' => 'Low Stock', 'class' => 'warn'];
        }
        if ($missingCost) {
            $badges[] = ['label' => 'Missing Cost', 'class' => 'warn'];
        }
        if ($missingModel) {
            $badges[] = ['label' => 'Missing Supplier Model', 'class' => 'warn'];
        }
        if ($syncRequired) {
            $badges[] = ['label' => 'Sync Required', 'class' => 'info'];
        }

        return [
            'badges' => $badges,
            'completeness' => $isReady ? 'ready' : 'needs_work',
            'primary_label' => $isReady ? 'Ready' : 'Needs Work',
            'class' => $isReady ? 'ok' : 'warn',
            'is_ready' => $isReady,
            'low_stock' => $lowStock,
            'filter_flags' => [
                'low_stock' => $lowStock,
                'missing_cost' => $missingCost,
                'missing_model' => $missingModel,
                'needs_work' => $needsWork,
                'sync_required' => $syncRequired,
            ],
        ];
    }

    private function syncStatusLabel(array $product, bool $noOptionsSynced): string
    {
        if ($noOptionsSynced) {
            return 'Sync required — options missing';
        }

        $lastSynced = trim((string) ($product['last_synced_at'] ?? ''));
        if ($lastSynced === '') {
            return 'Never synced';
        }

        $state = (string) ($product['sync_options_state'] ?? '');
        if ($state === 'has_options') {
            return 'Synced with options';
        }
        if ($state === 'simple') {
            return 'Synced — simple product';
        }

        return 'Synced';
    }

    private function variantHealth(array $variant, array $product, bool $isSupplierView): array
    {
        $issues = [];
        if (trim((string) ($variant['supplier_model'] ?? '')) === '') {
            $issues[] = 'Missing Supplier Model';
        }
        if ($variant['product_cost'] === null || $variant['product_cost'] === '') {
            $issues[] = $isSupplierView ? 'Missing Sale' : 'Missing Cost';
        }

        $lowWarning = (int) ($product['low_warning_threshold'] ?? 0);
        $vendorStock = (int) ($variant['vendor_stock'] ?? 0);
        $lowStock = $lowWarning > 0 && $vendorStock <= $lowWarning;

        return [
            'label' => $issues === [] ? 'Ready' : implode(' · ', $issues),
            'low_stock' => $lowStock,
        ];
    }

    private function costDisplay(array $product, array $variants): string
    {
        if ($variants === []) {
            if ($product['product_cost'] === null || $product['product_cost'] === '') {
                return '—';
            }

            return (string) $product['product_cost'];
        }

        $costs = [];
        foreach ($variants as $variant) {
            if ($variant['product_cost'] !== null && $variant['product_cost'] !== '') {
                $costs[] = (float) $variant['product_cost'];
            }
        }

        if ($costs === []) {
            return 'By variants';
        }

        $min = min($costs);
        $max = max($costs);

        return $min === $max
            ? (string) round($min, 2)
            : round($min, 2) . ' – ' . round($max, 2);
    }

    private function vendorStockTotal(array $product, array $variants): int
    {
        if ($variants === []) {
            return (int) ($product['vendor_stock'] ?? 0);
        }

        return $this->sumField($variants, 'vendor_stock');
    }

    private function sumField(array $rows, string $field): int
    {
        $total = 0;
        foreach ($rows as $row) {
            if (isset($row[$field]) && $row[$field] !== '' && $row[$field] !== null) {
                $total += (int) $row[$field];
            }
        }

        return $total;
    }
}
