<?php

namespace App\Domain;

class ReturnReceiveOrderContext
{
    public const RETURN_BATCH_FUTURE_NOTE = 'Return Batch will be built later like Dispatch Batch. Return Batch will group returned orders/products. Supplier return amount/cost will later be used for payable adjustment after owner/admin approval — not in v0.4.6.0.';

    public const LOKKISONA_STOCK_FUTURE_NOTE = 'Lokkisona warehouse return will later increase returned stock by product/option. If sold later, it deducts from returned stock.';

    /**
     * @param array<string, mixed> $order Row from ibs_orders
     * @param array<int, array<string, mixed>> $productLines Rows from ibs_order_items
     */
    public static function enrich(array $order, array $productLines = [], ?string $dispatchReference = null): array
    {
        $orderId = (int) ($order['order_id'] ?? 0);
        $ibsStatus = OrderWorkflowStatus::normalize((string) ($order['ibs_status'] ?? ''));
        $lineCost = 0.0;
        $lineQty = 0;
        $formattedLines = [];

        foreach ($productLines as $line) {
            $qty = (int) ($line['quantity'] ?? 0);
            $unitCost = (float) ($line['supplier_cost_snapshot'] ?? 0);
            $lineCost += $unitCost * max(1, $qty);
            $lineQty += $qty;
            $formattedLines[] = [
                'product_id' => (string) ($line['product_id'] ?? ''),
                'product_name' => (string) ($line['product_name'] ?? ''),
                'variant_label' => (string) ($line['variant_label'] ?? ''),
                'quantity' => $qty,
                'supplier_cost_snapshot' => number_format($unitCost, 2, '.', ''),
                'line_cost_snapshot' => number_format($unitCost * max(1, $qty), 2, '.', ''),
            ];
        }

        $snapshot = DispatchCostSnapshot::forOrder($order, $lineCost, $lineQty);

        return array_merge($order, [
            'erp_order_id' => $orderId,
            'fulfillment_status' => OrderWorkflowStatus::label($ibsStatus),
            'fulfillment_status_code' => $ibsStatus,
            'courier_status' => self::displayValue($order['courier_status'] ?? null),
            'consignment_id' => self::displayValue($order['tracking_number'] ?? null),
            'oc_order_status' => self::resolveOcOrderStatus($order),
            'source_order_reference' => self::displayValue($order['source_order_reference'] ?? null),
            'dispatch_report_reference' => $dispatchReference !== null && $dispatchReference !== ''
                ? $dispatchReference
                : '-',
            'preview_cost_snapshot' => $snapshot['product_cost_snapshot'],
            'preview_item_count' => $snapshot['item_count'],
            'product_lines' => $formattedLines,
            'product_summary' => self::productSummary($formattedLines),
        ]);
    }

    /**
     * @param array<int, array<string, mixed>> $lines
     */
    public static function productSummary(array $lines): string
    {
        if ($lines === []) {
            return '-';
        }

        $parts = [];
        foreach ($lines as $line) {
            $name = trim((string) ($line['product_name'] ?? ''));
            $productId = trim((string) ($line['product_id'] ?? ''));
            $variant = trim((string) ($line['variant_label'] ?? ''));
            $label = $name !== '' ? $name : ('Product #' . $productId);
            if ($variant !== '') {
                $label .= ' (' . $variant . ')';
            }
            $parts[] = $label . ' x' . (string) ($line['quantity'] ?? '0');
        }

        return implode('; ', $parts);
    }

    /**
     * @param array<string, mixed> $order
     */
    private static function resolveOcOrderStatus(array $order): string
    {
        $sourceRef = trim((string) ($order['source_order_reference'] ?? ''));
        if ($sourceRef !== '') {
            return $sourceRef;
        }

        $sourceId = trim((string) ($order['source_order_id'] ?? ''));

        return $sourceId !== '' ? $sourceId : '-';
    }

    private static function displayValue(mixed $value): string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : '-';
    }
}
