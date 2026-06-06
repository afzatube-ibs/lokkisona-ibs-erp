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
        $readyCount = 0;
        $needsWorkCount = 0;
        $variantLineCount = 0;

        foreach ($productRows as $product) {
            $productId = (int) ($product['product_id'] ?? 0);
            if ($productId <= 0) {
                continue;
            }

            $variants = $variantsByProduct[$productId] ?? [];
            $variantLineCount += count($variants);
            $summary = $this->summarizeProduct($product, $variants, $isSupplierView);
            if ($summary['is_ready']) {
                $readyCount++;
            } else {
                $needsWorkCount++;
            }

            $catalogRows[] = $summary['catalog'];
            $workspaces[(string) $productId] = $summary['workspace'];
        }

        return [
            'kpis' => [
                'total_products' => count($catalogRows),
                'ready' => $readyCount,
                'variants' => $variantLineCount,
                'needs_work' => $needsWorkCount,
            ],
            'rows' => $catalogRows,
            'workspaces' => $workspaces,
        ];
    }

    private function summarizeProduct(array $product, array $variants, bool $isSupplierView): array
    {
        $productId = (int) ($product['product_id'] ?? 0);
        $hasVariants = $variants !== [];
        $costLabel = $isSupplierView ? 'sale' : 'cost';
        $health = $this->healthStatus($product, $variants, $isSupplierView);
        $costDisplay = $this->costDisplay($product, $variants);
        $vendorStock = $this->vendorStockTotal($product, $variants);
        $ownerStock = $hasVariants
            ? $this->sumField($variants, 'source_stock')
            : (int) ($product['source_stock'] ?? 0);

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
                    'source_stock' => $variant['source_stock'] ?? null,
                    'product_cost' => $variant['product_cost'] ?? '',
                    'vendor_stock' => (int) ($variant['vendor_stock'] ?? 0),
                    'warning' => $lineHealth['low_stock'] ? 'Low' : '',
                    'health' => $lineHealth['label'],
                    'status' => (string) ($variant['status'] ?? 'active'),
                ];
            }
        }

        return [
            'is_ready' => $health['is_ready'],
            'catalog' => [
                'product_id' => $productId,
                'source_product_id' => (string) ($product['source_product_id'] ?? ''),
                'product_name' => (string) ($product['product_name'] ?? ''),
                'image_path' => (string) ($product['image_path'] ?? ''),
                'type' => $hasVariants ? 'variable' : 'simple',
                'source_model' => (string) ($product['source_model'] ?? ''),
                'supplier_model' => (string) ($product['supplier_model'] ?? ''),
                'average_cost' => $costDisplay,
                'owner_stock' => $ownerStock,
                'vendor_stock' => $vendorStock,
                'low_warning_threshold' => $product['low_warning_threshold'] ?? null,
                'low_warning' => $health['low_stock'],
                'health_label' => $health['label'],
                'health_class' => $health['class'],
                'supplier_product_category' => (string) ($product['supplier_product_category'] ?? ''),
                'status' => (string) ($product['status'] ?? 'active'),
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
                'source_model' => (string) ($product['source_model'] ?? ''),
                'source_stock' => $product['source_stock'] ?? null,
                'last_synced_at' => (string) ($product['last_synced_at'] ?? ''),
                'low_warning_threshold' => $product['low_warning_threshold'] ?? '',
                'image_path' => (string) ($product['image_path'] ?? ''),
                'type' => $hasVariants ? 'variable' : 'simple',
                'product_cost' => $product['product_cost'] ?? '',
                'vendor_stock' => (int) ($product['vendor_stock'] ?? 0),
                'business_source_id' => $product['business_source_id'] ?? '',
                'status' => (string) ($product['status'] ?? 'active'),
                'cost_label' => $costLabel,
                'variants' => $workspaceVariants,
            ],
        ];
    }

    private function healthStatus(array $product, array $variants, bool $isSupplierView): array
    {
        $issues = [];
        $lowWarning = (int) ($product['low_warning_threshold'] ?? 0);
        $vendorStock = $this->vendorStockTotal($product, $variants);
        $lowStock = $lowWarning > 0 && $vendorStock <= $lowWarning;

        if ($variants === []) {
            if (trim((string) ($product['supplier_model'] ?? '')) === '') {
                $issues[] = 'Missing Model';
            }
            if ($product['product_cost'] === null || $product['product_cost'] === '') {
                $issues[] = $isSupplierView ? 'Missing Sale' : 'Missing Rate';
            }
        } else {
            $missingModel = 0;
            $missingRate = 0;
            foreach ($variants as $variant) {
                if (trim((string) ($variant['supplier_model'] ?? '')) === '') {
                    $missingModel++;
                }
                if ($variant['product_cost'] === null || $variant['product_cost'] === '') {
                    $missingRate++;
                }
            }
            if ($missingModel > 0) {
                $issues[] = $missingModel . '/' . count($variants) . ' Missing Model';
            }
            if ($missingRate > 0) {
                $issues[] = $missingRate . '/' . count($variants) . ($isSupplierView ? ' Missing Sale' : ' Missing Rate');
            }
        }

        if ($lowStock) {
            $issues[] = 'Low Stock';
        }

        if ($issues === []) {
            return [
                'label' => 'OK',
                'class' => 'ok',
                'is_ready' => true,
                'low_stock' => false,
            ];
        }

        return [
            'label' => implode(' · ', $issues),
            'class' => 'warn',
            'is_ready' => false,
            'low_stock' => $lowStock,
        ];
    }

    private function variantHealth(array $variant, array $product, bool $isSupplierView): array
    {
        $issues = [];
        if (trim((string) ($variant['supplier_model'] ?? '')) === '') {
            $issues[] = 'Missing Model';
        }
        if ($variant['product_cost'] === null || $variant['product_cost'] === '') {
            $issues[] = $isSupplierView ? 'Missing Sale' : 'Missing Rate';
        }

        $lowWarning = (int) ($product['low_warning_threshold'] ?? 0);
        $vendorStock = (int) ($variant['vendor_stock'] ?? 0);
        $lowStock = $lowWarning > 0 && $vendorStock <= $lowWarning;

        return [
            'label' => $issues === [] ? 'OK' : implode(' · ', $issues),
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
