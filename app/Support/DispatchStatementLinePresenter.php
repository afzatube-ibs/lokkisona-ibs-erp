<?php

namespace App\Support;

/**
 * Builds Daily Dispatch Statement product line rows for detail and print views.
 * Payable snapshot only — no courier, return, or OC workflow columns.
 */
class DispatchStatementLinePresenter
{
    /**
     * @param array<int, array<string, mixed>> $items dispatch_report_items joined with orders
     * @param array<int, string> $sourceNames business_source_id => label
     * @param array<int, array<int, array<string, mixed>>> $itemsByOrder
     * @param array<int, array<string, mixed>|null> $productsById
     * @param array<int, array<int, array<string, mixed>>> $variantsByProduct
     * @param array<string, mixed> $report
     * @return array{
     *     product_rows: array<int, array<string, mixed>>,
     *     total_quantity: int,
     *     total_amount: float,
     *     legacy_warning: ?string,
     *     lines_empty: bool
     * }
     */
    public static function build(
        array $items,
        array $sourceNames,
        array $itemsByOrder,
        array $productsById,
        array $variantsByProduct,
        array $report
    ): array {
        $defaultSourceKey = (int) ($report['business_source_id'] ?? 0);
        $defaultSourceName = $sourceNames[$defaultSourceKey] ?? '—';
        $reportId = (int) ($report['dispatch_report_id'] ?? 0);
        $totalOrdersHeader = max(0, (int) ($report['total_orders'] ?? 0));
        $ambiguousLegacy = $reportId <= 0 && $totalOrdersHeader > 0 && count($items) > $totalOrdersHeader;

        $productRows = [];
        $totalQuantity = 0;
        $totalAmount = 0.0;
        $sl = 0;

        foreach ($items as $item) {
            $orderId = (int) ($item['order_id'] ?? 0);
            $sourceKey = (int) ($item['business_source_id'] ?? $defaultSourceKey);
            $sourceName = $sourceNames[$sourceKey] ?? $defaultSourceName;

            $orderRow = [
                'order_id' => $orderId,
                'order_reference' => (string) ($item['erp_order_reference'] ?? $item['order_reference'] ?? ''),
                'source_order_reference' => (string) ($item['source_order_reference'] ?? ''),
            ];
            $orderNo = OrderWorkflowRowPresenter::formatOrderNo($orderRow);
            $customerName = (string) ($item['customer_name'] ?? '');

            $formattedLines = OrderWorkflowRowPresenter::formatProductLines(
                $itemsByOrder[$orderId] ?? [],
                $productsById,
                $variantsByProduct
            );

            if ($formattedLines === []) {
                $formattedLines = self::snapshotFallbackLines($item);
            }

            foreach ($formattedLines as $line) {
                $sl++;
                $qty = (int) ($line['quantity'] ?? 0);
                $rate = round((float) ($line['cost_snapshot'] ?? 0), 2);
                $lineTotal = round((float) ($line['line_cost_total'] ?? ($rate * max(1, $qty))), 2);
                $totalQuantity += $qty;
                $totalAmount += $lineTotal;

                $productRows[] = [
                    'sl' => $sl,
                    'source_name' => $sourceName,
                    'order_no' => $orderNo,
                    'customer_name' => $customerName,
                    'product_name' => (string) ($line['product_name'] ?? $line['model'] ?? 'Product'),
                    'image_url' => $line['image_url'] ?? null,
                    'model' => (string) ($line['model'] ?? ''),
                    'option_chips' => $line['option_chips'] ?? [],
                    'quantity' => $qty,
                    'rate' => $rate,
                    'line_total' => $lineTotal,
                    'snapshot_only' => !empty($line['snapshot_only']),
                ];
            }
        }

        $legacyWarning = null;
        if ($productRows === [] && $totalOrdersHeader > 0) {
            $legacyWarning = 'Legacy batch has no item snapshot. Recreate dispatch batch.';
        } elseif ($reportId <= 0 && $productRows !== []) {
            $legacyWarning = 'Legacy batch (dispatch_report_id=0) — line rows loaded from best-effort snapshot.';
        } elseif ($ambiguousLegacy) {
            $legacyWarning = 'Legacy batch uses shared dispatch_report_id=0 — showing best-effort snapshot rows for this statement ref.';
        }

        return [
            'product_rows' => $productRows,
            'total_quantity' => $totalQuantity,
            'total_amount' => round($totalAmount, 2),
            'legacy_warning' => $legacyWarning,
            'lines_empty' => $productRows === [],
        ];
    }

    /**
     * @param array<string, mixed> $item
     * @return array<int, array<string, mixed>>
     */
    private static function snapshotFallbackLines(array $item): array
    {
        $qty = (int) ($item['item_count'] ?? 0);
        if ($qty < 1) {
            $qty = 1;
        }
        $lineTotal = round((float) ($item['product_cost_snapshot'] ?? 0), 2);
        $rate = $qty > 0 ? round($lineTotal / $qty, 2) : $lineTotal;
        $ref = trim((string) ($item['order_reference'] ?? $item['erp_order_reference'] ?? ''));

        return [[
            'product_name' => $ref !== '' ? $ref : 'Locked order snapshot',
            'image_url' => null,
            'model' => $ref !== '' ? $ref : '—',
            'quantity' => $qty,
            'cost_snapshot' => $rate,
            'line_cost_total' => $lineTotal,
            'option_chips' => [['label' => 'Order-level snapshot', 'meta' => null]],
            'snapshot_only' => true,
        ]];
    }
}
