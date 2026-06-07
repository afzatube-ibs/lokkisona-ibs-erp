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
        $missingCost = 0;
        $missingModel = 0;
        $lowStock = 0;
        $syncedToday = 0;
        $variantLineCount = 0;
        $variantQtyTotal = 0;
        $needsWork = 0;
        $ready = 0;

        foreach ($catalogRows as $row) {
            $flags = $row['filter_flags'] ?? [];
            if (!empty($flags['missing_cost'])) {
                $missingCost++;
            }
            if (!empty($flags['missing_model'])) {
                $missingModel++;
            }
            if (!empty($flags['low_stock'])) {
                $lowStock++;
            }
            if (!empty($flags['synced_today'])) {
                $syncedToday++;
            }
            if (!empty($flags['needs_work'])) {
                $needsWork++;
            }
            if (!empty($flags['ready'])) {
                $ready++;
            }
            $variantLineCount += (int) ($row['variant_count'] ?? 0);
            $variantQtyTotal += (int) ($row['vendor_stock'] ?? 0);
        }

        return [
            'total_products' => count($catalogRows),
            'ready' => $ready,
            'variants' => $variantLineCount,
            'variant_qty_total' => $variantQtyTotal,
            'missing_cost' => $missingCost,
            'missing_model' => $missingModel,
            'low_stock' => $lowStock,
            'synced_today' => $syncedToday,
            'needs_work' => $needsWork,
        ];
    }

    /**
     * @return array{catalog: array<string, mixed>, workspace: array<string, mixed>}
     */
    public function buildProductViews(array $product, array $variants, bool $isSupplierView = false): array
    {
        return $this->summarizeProduct($product, $variants, $isSupplierView);
    }

    private function summarizeProduct(array $product, array $variants, bool $isSupplierView): array
    {
        $productId = (int) ($product['product_id'] ?? 0);
        $hasVariants = $variants !== [];
        $syncOptionsState = (string) ($product['sync_options_state'] ?? '');
        $noOptionsSynced = !$hasVariants && $syncOptionsState === 'missing_options';
        $isVariable = $hasVariants || $noOptionsSynced;
        $costLabel = $isSupplierView ? 'sale' : 'cost';
        $lastSynced = (string) ($product['last_synced_at'] ?? '');
        $lastSyncedDisplay = $lastSynced !== '' ? $lastSynced : '—';
        $syncedToday = $this->isSyncedToday($lastSynced);
        $rawImagePath = (string) ($product['image_path'] ?? '');
        $health = $this->healthStatus($product, $variants, $isSupplierView, $noOptionsSynced, $syncedToday);
        $costDisplay = $this->costDisplay($product, $variants);
        $vendorStock = $this->vendorStockTotal($product, $variants);
        $ownerStock = $hasVariants
            ? $this->sumField($variants, 'source_stock')
            : (int) ($product['source_stock'] ?? 0);
        $syncStatus = $this->syncStatusLabel($product, $noOptionsSynced);

        $workspaceVariants = $this->buildVariantRows($variants, $product, $isSupplierView);
        $displayHealth = $this->displayHealthLabel($health['primary_label'], $isSupplierView);
        $vendorStockIndicator = $this->vendorStockIndicator($product, $variants, $health['low_stock']);

        return [
            'catalog' => [
                'product_id' => $productId,
                'source_product_id' => (string) ($product['source_product_id'] ?? ''),
                'product_name' => (string) ($product['product_name'] ?? ''),
                'image_path' => $rawImagePath,
                'image_url' => opencart_media_url($rawImagePath),
                'type' => $isVariable ? 'variable' : 'simple',
                'no_options_synced' => $noOptionsSynced,
                'source_model' => (string) ($product['source_model'] ?? ''),
                'source_stock' => $hasVariants ? $ownerStock : ($product['source_stock'] ?? null),
                'supplier_model' => (string) ($product['supplier_model'] ?? ''),
                'product_cost' => $product['product_cost'] ?? '',
                'average_cost' => $costDisplay,
                'owner_stock' => $ownerStock,
                'vendor_stock' => $vendorStock,
                'low_warning_threshold' => $product['low_warning_threshold'] ?? null,
                'low_warning' => $health['low_stock'],
                'badges' => $health['badges'],
                'completeness' => $health['completeness'],
                'health_label' => $displayHealth,
                'health_class' => $health['class'],
                'health_status_display' => $displayHealth,
                'health_status_class' => $health['class'],
                'vendor_stock_label' => $vendorStockIndicator['label'],
                'vendor_stock_class' => $vendorStockIndicator['class'],
                'low_warning_label' => $health['low_stock'] ? 'Low' : 'OK',
                'low_warning_class' => $health['low_stock'] ? 'warn' : 'ok',
                'sync_status' => $syncStatus,
                'filter_flags' => $health['filter_flags'],
                'supplier_product_category' => (string) ($product['supplier_product_category'] ?? ''),
                'last_synced_at' => $lastSyncedDisplay,
                'status' => (string) ($product['status'] ?? 'active'),
                'variant_count' => count($variants),
                'variants' => $workspaceVariants,
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
                'image_path' => $rawImagePath,
                'image_url' => opencart_media_url($rawImagePath),
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

    private function healthStatus(array $product, array $variants, bool $isSupplierView, bool $noOptionsSynced = false, bool $syncedToday = false): array
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
        $isReady = !$needsWork && !$lowStock;
        $primaryLabel = $this->primaryHealthLabel($missingCost, $missingModel, $lowStock, $needsWork);
        $healthClass = match ($primaryLabel) {
            'Complete' => 'ok',
            'Low Stock' => 'warn',
            'Needs Cost', 'Needs Model', 'Needs Work' => 'warn',
            default => 'muted',
        };

        $badges = [];
        $displayLabel = $this->displayHealthLabel($primaryLabel, $isSupplierView);
        $badges[] = ['label' => $displayLabel, 'class' => $healthClass];
        if ($syncedToday && $primaryLabel !== 'Complete') {
            $badges[] = ['label' => 'Synced Today', 'class' => 'ok'];
        }
        if ($syncRequired) {
            $badges[] = ['label' => 'Sync Required', 'class' => 'info'];
        }

        return [
            'badges' => $badges,
            'completeness' => $isReady ? 'ready' : 'needs_work',
            'primary_label' => $primaryLabel,
            'class' => $healthClass,
            'is_ready' => $isReady,
            'low_stock' => $lowStock,
            'filter_flags' => [
                'low_stock' => $lowStock,
                'missing_cost' => $missingCost,
                'missing_model' => $missingModel,
                'needs_work' => $needsWork,
                'ready' => $isReady,
                'sync_required' => $syncRequired,
                'synced_today' => $syncedToday,
                'active' => ($product['status'] ?? 'active') === 'active',
                'inactive' => ($product['status'] ?? 'active') === 'inactive',
            ],
        ];
    }

    private function displayHealthLabel(string $primaryLabel, bool $isSupplierView): string
    {
        return match ($primaryLabel) {
            'Complete' => 'Ready',
            'Needs Cost' => $isSupplierView ? 'Missing Rate' : 'Missing Cost',
            'Needs Model' => 'Missing Supplier Model',
            'Low Stock' => 'Low Stock',
            'Needs Work' => 'Needs Work',
            default => $primaryLabel,
        };
    }

    private function vendorStockIndicator(array $product, array $variants, bool $lowStock): array
    {
        $vendorStock = $this->vendorStockTotal($product, $variants);
        $lowWarning = (int) ($product['low_warning_threshold'] ?? 0);
        $hasSupplierData = trim((string) ($product['supplier_model'] ?? '')) !== ''
            || ($product['product_cost'] !== null && $product['product_cost'] !== '')
            || $lowWarning > 0;

        if ($variants !== []) {
            foreach ($variants as $variant) {
                if (trim((string) ($variant['supplier_model'] ?? '')) !== ''
                    || ($variant['product_cost'] !== null && $variant['product_cost'] !== '')) {
                    $hasSupplierData = true;
                    break;
                }
            }
        }

        if (!$hasSupplierData && $vendorStock === 0) {
            return ['label' => 'Not Set', 'class' => 'muted'];
        }
        if ($lowStock || ($lowWarning > 0 && $vendorStock <= $lowWarning)) {
            return ['label' => 'Low', 'class' => 'warn'];
        }
        if ($vendorStock === 0) {
            return ['label' => 'Zero', 'class' => 'info'];
        }

        return ['label' => 'Healthy', 'class' => 'ok'];
    }

    private function primaryHealthLabel(bool $missingCost, bool $missingModel, bool $lowStock, bool $needsWork): string
    {
        if ($missingCost && $missingModel) {
            return 'Needs Work';
        }
        if ($missingCost) {
            return 'Needs Cost';
        }
        if ($missingModel) {
            return 'Needs Model';
        }
        if ($lowStock) {
            return 'Low Stock';
        }

        return 'Complete';
    }

    private function buildVariantRows(array $variants, array $product, bool $isSupplierView): array
    {
        if ($variants === []) {
            return [];
        }

        $rows = [];
        foreach ($variants as $variant) {
            $variantId = (int) ($variant['product_variant_id'] ?? 0);
            $lineHealth = $this->variantHealth($variant, $product, $isSupplierView);
            $rows[] = [
                'product_variant_id' => $variantId,
                'line_label' => trim((string) ($variant['option_name'] ?? '') . ': ' . (string) ($variant['option_value'] ?? ''), ': '),
                'option_name' => (string) ($variant['option_name'] ?? ''),
                'option_value' => (string) ($variant['option_value'] ?? ''),
                'image_path' => (string) ($variant['option_image_path'] ?? ''),
                'image_url' => opencart_media_url((string) ($variant['option_image_path'] ?? '')),
                'source_model' => (string) ($variant['source_model'] ?? ''),
                'supplier_model' => (string) ($variant['supplier_model'] ?? ''),
                'supplier_note' => (string) ($variant['supplier_note'] ?? ''),
                'source_stock' => $variant['source_stock'] ?? null,
                'source_price' => null,
                'source_price_label' => 'main price',
                'product_cost' => $variant['product_cost'] ?? '',
                'vendor_stock' => (int) ($variant['vendor_stock'] ?? 0),
                'warning' => $lineHealth['low_stock'] ? 'Low' : '',
                'health' => $lineHealth['label'],
                'health_class' => $lineHealth['class'],
                'status' => (string) ($variant['status'] ?? 'active'),
            ];
        }

        return $rows;
    }

    private function isSyncedToday(string $lastSynced): bool
    {
        $lastSynced = trim($lastSynced);
        if ($lastSynced === '') {
            return false;
        }

        $timestamp = strtotime($lastSynced);

        return $timestamp !== false && date('Y-m-d', $timestamp) === date('Y-m-d');
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
            $issues[] = 'Missing Model';
        }
        if ($variant['product_cost'] === null || $variant['product_cost'] === '') {
            $issues[] = $isSupplierView ? 'Missing Sale' : 'Missing Cost';
        }

        $lowWarning = (int) ($product['low_warning_threshold'] ?? 0);
        $vendorStock = (int) ($variant['vendor_stock'] ?? 0);
        $lowStock = $lowWarning > 0 && $vendorStock <= $lowWarning;

        return [
            'label' => $issues === [] ? 'Complete' : implode(' · ', $issues),
            'class' => $issues === [] ? 'ok' : 'warn',
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
