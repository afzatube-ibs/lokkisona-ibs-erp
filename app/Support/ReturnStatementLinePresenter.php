<?php

namespace App\Support;

use App\Domain\ReturnReceiveReason;
use App\Domain\ReturnReceiveType;

/**
 * Builds Supplier Return Statement product line rows for detail and print views.
 */
class ReturnStatementLinePresenter
{
    /**
     * @param array<int, array<string, mixed>> $items return_report_items joined with orders
     * @param array<int, string> $sourceNames
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

            $returnType = ReturnReceiveType::normalize((string) ($item['return_type'] ?? ''));
            $returnReason = ReturnReceiveReason::normalize((string) ($item['return_reason'] ?? ''));
            $returnTypeLabel = ReturnReceiveType::label($returnType);
            $returnReasonLabel = ReturnReceiveReason::isKnown($returnReason)
                ? ReturnReceiveReason::label($returnReason)
                : ($returnReason !== '' ? $returnReason : '—');

            $formattedLines = OrderWorkflowRowPresenter::formatProductLines(
                $itemsByOrder[$orderId] ?? [],
                $productsById,
                $variantsByProduct
            );

            if ($formattedLines === []) {
                $formattedLines = self::snapshotFallbackLines($item);
            }

            $formattedLines = self::reconcileLinesToLockedSnapshot($formattedLines, $item);

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
                    'product_name' => (string) ($line['product_name'] ?? $line['model'] ?? 'Product'),
                    'image_url' => $line['image_url'] ?? null,
                    'model' => (string) ($line['model'] ?? ''),
                    'option_chips' => $line['option_chips'] ?? [],
                    'quantity' => $qty,
                    'rate' => $rate,
                    'line_total' => $lineTotal,
                    'return_reason' => $returnReasonLabel,
                    'return_type' => $returnTypeLabel,
                    'snapshot_only' => !empty($line['snapshot_only']),
                ];
            }
        }

        $lockedReportTotal = 0.0;
        foreach ($items as $item) {
            $lockedReportTotal += round((float) ($item['product_cost_snapshot'] ?? 0), 2);
        }
        if ($lockedReportTotal > 0) {
            $totalAmount = round($lockedReportTotal, 2);
        }

        $legacyWarning = null;
        if ($productRows === [] && max(0, (int) ($report['total_returns'] ?? 0)) > 0) {
            $legacyWarning = 'Return report has no product line snapshot.';
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
     * Align displayed line costs with the locked report-item snapshot (dispatch-era cost, not live catalog).
     *
     * @param array<int, array<string, mixed>> $lines
     * @param array<string, mixed> $item
     * @return array<int, array<string, mixed>>
     */
    private static function reconcileLinesToLockedSnapshot(array $lines, array $item): array
    {
        $lockedTotal = round((float) ($item['product_cost_snapshot'] ?? 0), 2);
        if ($lockedTotal <= 0 || $lines === []) {
            return $lines;
        }

        $computed = 0.0;
        foreach ($lines as $line) {
            $computed += round((float) ($line['line_cost_total'] ?? 0), 2);
        }

        if (abs($computed - $lockedTotal) < 0.01) {
            return $lines;
        }

        if (count($lines) === 1) {
            $qty = max(1, (int) ($lines[0]['quantity'] ?? 1));
            $lines[0]['line_cost_total'] = $lockedTotal;
            $lines[0]['cost_snapshot'] = round($lockedTotal / $qty, 2);
            $lines[0]['snapshot_locked'] = true;

            return $lines;
        }

        if ($computed <= 0) {
            return $lines;
        }

        $factor = $lockedTotal / $computed;
        foreach ($lines as $index => $line) {
            $lineTotal = round((float) ($line['line_cost_total'] ?? 0) * $factor, 2);
            $qty = max(1, (int) ($line['quantity'] ?? 1));
            $lines[$index]['line_cost_total'] = $lineTotal;
            $lines[$index]['cost_snapshot'] = round($lineTotal / $qty, 2);
            $lines[$index]['snapshot_locked'] = true;
        }

        return $lines;
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
            'product_name' => $ref !== '' ? $ref : 'Locked return snapshot',
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
