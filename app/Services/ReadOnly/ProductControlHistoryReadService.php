<?php

namespace App\Services\ReadOnly;

use App\Repositories\ProductCostHistoryRepository;
use App\Repositories\ProductStockHistoryRepository;
use App\Repositories\ProductVariantRepository;

/**
 * Per-product cost/stock history for Product Control modal (v1.9.1).
 */
class ProductControlHistoryReadService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function forProduct(int $productId, int $limit = 50): array
    {
        if ($productId <= 0) {
            return [];
        }

        $costRows = (new ProductCostHistoryRepository())->forProduct($productId, $limit);
        $stockRows = (new ProductStockHistoryRepository())->forProduct($productId, $limit);
        $variants = (new ProductVariantRepository())->findByProductId($productId);

        $variantLabels = [];
        foreach ($variants as $variant) {
            $variantId = (int) ($variant['product_variant_id'] ?? 0);
            if ($variantId <= 0) {
                continue;
            }
            $optionName = trim((string) ($variant['option_name'] ?? ''));
            $optionValue = trim((string) ($variant['option_value'] ?? ''));
            $supplierModel = trim((string) ($variant['supplier_model'] ?? ''));
            $label = trim($optionName . ': ' . $optionValue, ': ');
            if ($supplierModel !== '') {
                $label = $label !== '' ? $label . ' / ' . $supplierModel : $supplierModel;
            }
            $variantLabels[$variantId] = $label !== '' ? $label : 'Variant #' . $variantId;
        }

        $groups = [];

        foreach ($costRows as $row) {
            $variantId = (int) ($row['product_variant_id'] ?? 0);
            $createdAt = (string) ($row['created_at'] ?? '');
            $note = (string) ($row['note'] ?? '');
            $key = $createdAt . '|' . $productId . '|' . $variantId . '|' . md5($note);
            if (!isset($groups[$key])) {
                $groups[$key] = $this->emptyGroup($productId, $variantId, $createdAt, $note, $variantLabels);
            }
            $groups[$key]['old_cost'] = $row['old_cost'] ?? '';
            $groups[$key]['new_cost'] = $row['new_cost'] ?? '';
            $groups[$key]['sort_id'] = max((int) $groups[$key]['sort_id'], (int) ($row['product_cost_history_id'] ?? 0));
        }

        foreach ($stockRows as $row) {
            $variantId = (int) ($row['product_variant_id'] ?? 0);
            $createdAt = (string) ($row['created_at'] ?? '');
            $note = (string) ($row['note'] ?? '');
            $key = $createdAt . '|' . $productId . '|' . $variantId . '|' . md5($note);
            if (!isset($groups[$key])) {
                $groups[$key] = $this->emptyGroup($productId, $variantId, $createdAt, $note, $variantLabels);
            }
            $groups[$key]['old_stock'] = $row['old_stock'] ?? '';
            $groups[$key]['new_stock'] = $row['new_stock'] ?? '';
            $groups[$key]['change_type'] = $row['change_type'] ?? '';
            $groups[$key]['sort_id'] = max((int) $groups[$key]['sort_id'], (int) ($row['product_stock_history_id'] ?? 0));
        }

        $rows = array_values($groups);
        usort($rows, function (array $a, array $b): int {
            $timeCompare = strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? ''));
            if ($timeCompare !== 0) {
                return $timeCompare;
            }

            return ((int) ($b['sort_id'] ?? 0)) <=> ((int) ($a['sort_id'] ?? 0));
        });

        return array_slice($rows, 0, $limit);
    }

    /**
     * @param array<int, string> $variantLabels
     * @return array<string, mixed>
     */
    private function emptyGroup(int $productId, int $variantId, string $createdAt, string $note, array $variantLabels): array
    {
        return [
            'product_id' => $productId,
            'product_variant_id' => $variantId,
            'variant_label' => $variantId > 0 ? ($variantLabels[$variantId] ?? ('Variant #' . $variantId)) : 'Product level',
            'old_cost' => '',
            'new_cost' => '',
            'old_stock' => '',
            'new_stock' => '',
            'change_type' => '',
            'note' => $note,
            'created_at' => $createdAt,
            'sort_id' => 0,
        ];
    }
}
