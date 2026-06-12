<?php

namespace App\Domain;

class DispatchCostSnapshot
{
    /**
     * Resolve immutable supplier cost snapshot for an order at dispatch create time.
     *
     * @param array<string, mixed> $order
     */
    public static function forOrder(array $order, float $lineCostSum, int $lineQuantitySum): array
    {
        $orderTotal = round((float) ($order['cost_snapshot_total'] ?? 0), 2);
        $lineCostSum = round($lineCostSum, 2);

        $snapshot = $orderTotal > 0 ? $orderTotal : $lineCostSum;
        $itemCount = $lineQuantitySum > 0 ? $lineQuantitySum : 0;

        return [
            'product_cost_snapshot' => $snapshot,
            'item_count' => $itemCount,
        ];
    }

    /**
     * Count order lines with missing supplier cost (<= 0).
     *
     * @param array<int, array<string, mixed>> $lines
     */
    public static function countMissingLineItems(array $lines): int
    {
        $missing = 0;
        foreach ($lines as $line) {
            if ((float) ($line['supplier_cost_snapshot'] ?? 0) <= 0) {
                $missing++;
            }
        }

        return $missing;
    }

    /**
     * Count order lines without a mapped ERP supplier product.
     *
     * @param array<int, array<string, mixed>> $lines
     */
    public static function countUnmappedLineItems(array $lines): int
    {
        $unmapped = 0;
        foreach ($lines as $line) {
            if ((int) ($line['product_id'] ?? 0) <= 0) {
                $unmapped++;
            }
        }

        return $unmapped;
    }

    /**
     * @param array<string, mixed> $order
     */
    public static function hasDispatchOrderNo(array $order): bool
    {
        $sourceRef = trim((string) ($order['source_order_reference'] ?? ''));
        if ($sourceRef !== '') {
            return true;
        }

        return trim((string) ($order['order_reference'] ?? '')) !== '';
    }
}
