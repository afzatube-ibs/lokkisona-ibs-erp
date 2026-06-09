<?php

namespace App\Services\ReadOnly;

use App\Domain\ProductControlHistoryNote;
use App\Repositories\ProductCostHistoryRepository;
use App\Repositories\ProductStockHistoryRepository;
use App\Repositories\ProductVariantRepository;
use App\Repositories\UserRepository;

/**
 * Per-product cost/stock history for Product Control modal.
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
        $users = new UserRepository();

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

        $entries = [];

        foreach ($costRows as $row) {
            $entries[] = $this->mapCostRow($row, $variantLabels, $users);
        }

        foreach ($stockRows as $row) {
            $entries[] = $this->mapStockRow($row, $variantLabels, $users);
        }

        usort($entries, function (array $a, array $b): int {
            $timeCompare = strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? ''));
            if ($timeCompare !== 0) {
                return $timeCompare;
            }

            return ((int) ($b['sort_id'] ?? 0)) <=> ((int) ($a['sort_id'] ?? 0));
        });

        return array_slice($entries, 0, $limit);
    }

    /**
     * @param array<string, mixed> $row
     * @param array<int, string> $variantLabels
     */
    private function mapCostRow(array $row, array $variantLabels, UserRepository $users): array
    {
        $variantId = (int) ($row['product_variant_id'] ?? 0);
        $rawNote = (string) ($row['note'] ?? '');
        $parsed = ProductControlHistoryNote::parse($rawNote);
        $changeType = $parsed['type'] !== '' ? $parsed['type'] : 'direct';

        return [
            'product_id' => (int) ($row['product_id'] ?? 0),
            'product_variant_id' => $variantId,
            'variant_label' => $variantId > 0 ? ($variantLabels[$variantId] ?? ('Variant #' . $variantId)) : 'Product level',
            'field_changed' => ProductControlHistoryNote::fieldLabel($parsed['field'] !== '' ? $parsed['field'] : 'supplier_cost'),
            'old_value' => $row['old_cost'] ?? '',
            'new_value' => $row['new_cost'] ?? '',
            'change_type' => ProductControlHistoryNote::typeLabel($changeType),
            'delta_amount' => $parsed['delta'] !== '' ? $parsed['delta'] : $this->costDelta($row['old_cost'] ?? null, $row['new_cost'] ?? null),
            'note' => $parsed['note'] !== '' ? $parsed['note'] : $rawNote,
            'user_label' => $this->userLabel($row, $users),
            'created_at' => (string) ($row['created_at'] ?? ''),
            'sort_id' => (int) ($row['product_cost_history_id'] ?? 0),
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @param array<int, string> $variantLabels
     */
    private function mapStockRow(array $row, array $variantLabels, UserRepository $users): array
    {
        $variantId = (int) ($row['product_variant_id'] ?? 0);
        $rawNote = (string) ($row['note'] ?? '');
        $parsed = ProductControlHistoryNote::parse($rawNote);
        $changeType = (string) ($row['change_type'] ?? ($parsed['type'] !== '' ? $parsed['type'] : 'direct'));

        return [
            'product_id' => (int) ($row['product_id'] ?? 0),
            'product_variant_id' => $variantId,
            'variant_label' => $variantId > 0 ? ($variantLabels[$variantId] ?? ('Variant #' . $variantId)) : 'Product level',
            'field_changed' => ProductControlHistoryNote::fieldLabel($parsed['field'] !== '' ? $parsed['field'] : 'vendor_stock'),
            'old_value' => $row['old_stock'] ?? '',
            'new_value' => $row['new_stock'] ?? '',
            'change_type' => ProductControlHistoryNote::typeLabel($changeType),
            'delta_amount' => $parsed['delta'] !== '' ? $parsed['delta'] : (string) ((int) ($row['new_stock'] ?? 0) - (int) ($row['old_stock'] ?? 0)),
            'note' => $parsed['note'] !== '' ? $parsed['note'] : $rawNote,
            'user_label' => $this->userLabel($row, $users),
            'created_at' => (string) ($row['created_at'] ?? ''),
            'sort_id' => (int) ($row['product_stock_history_id'] ?? 0),
        ];
    }

    /**
     * @param array<string, mixed> $row
     */
    private function userLabel(array $row, UserRepository $users): string
    {
        $changedBy = $row['changed_by'] ?? null;
        if ($changedBy === null || $changedBy === '') {
            return '—';
        }

        if (is_string($changedBy) && !ctype_digit($changedBy)) {
            return $changedBy;
        }

        $userId = (int) $changedBy;
        if ($userId <= 0 || !$users->tableExists()) {
            return '—';
        }

        $user = $users->findById($userId);
        if ($user === null) {
            return '—';
        }

        $username = trim((string) ($user['username'] ?? ''));

        return $username !== '' ? $username : '—';
    }

    /**
     * @param mixed $old
     * @param mixed $new
     */
    private function costDelta($old, $new): string
    {
        if ($old === '' || $old === null || $new === '' || $new === null) {
            return '';
        }

        return (string) round((float) $new - (float) $old, 2);
    }
}
