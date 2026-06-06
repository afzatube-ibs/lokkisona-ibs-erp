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
}
