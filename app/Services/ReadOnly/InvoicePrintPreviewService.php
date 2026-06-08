<?php

namespace App\Services\ReadOnly;

use App\Repositories\Write\OrderWriteRepository;

/**
 * ERP invoice/packing print preview (v0.5.4) — read-only order snapshot display. No invoice persistence.
 */
class InvoicePrintPreviewService
{
    public function packagingPreview(int $limit = 10): array
    {
        $repo = new OrderWriteRepository();
        if (!$repo->tableExists()) {
            return ['orders' => [], 'message' => 'Orders table not applied.'];
        }

        $orders = [];
        foreach (['packaging', 'shipped', 'dispatch_report_created'] as $status) {
            foreach ($repo->findByStatus($status, $limit) as $order) {
                $orders[] = [
                    'order_reference' => (string) ($order['order_reference'] ?? ''),
                    'customer_name' => (string) ($order['customer_name'] ?? ''),
                    'ibs_status' => (string) ($order['ibs_status'] ?? ''),
                    'cost_snapshot_total' => number_format((float) ($order['cost_snapshot_total'] ?? 0), 2),
                    'template' => 'ERP Packing Slip (preview)',
                ];
            }
        }

        return [
            'orders' => array_slice($orders, 0, $limit),
            'message' => 'Customer invoice hides supplier cost. Internal packing may show supplier model/cost when enabled.',
        ];
    }

    /**
     * @param array<int, int> $orderIds
     * @return array{orders: array<int, array<string, mixed>>, message: string}
     */
    public function batchByOrderIds(array $orderIds): array
    {
        $repo = new OrderWriteRepository();
        if (!$repo->tableExists()) {
            return ['orders' => [], 'message' => 'Orders table not applied.'];
        }

        $orderIds = array_values(array_filter(array_map('intval', $orderIds), static fn (int $id): bool => $id > 0));
        if ($orderIds === []) {
            return ['orders' => [], 'message' => 'No orders selected for batch packing.'];
        }

        $items = new \App\Repositories\OrderItemRepository();
        $orders = [];
        foreach ($orderIds as $orderId) {
            $order = $repo->find($orderId);
            if ($order === null) {
                continue;
            }

            $lineCost = $items->tableExists() ? $items->sumSupplierCostByOrderId($orderId) : 0.0;
            $lineQty = $items->tableExists() ? $items->sumQuantityByOrderId($orderId) : 0;
            $orders[] = [
                'order_id' => $orderId,
                'order_reference' => (string) ($order['order_reference'] ?? ''),
                'customer_name' => (string) ($order['customer_name'] ?? ''),
                'customer_phone' => (string) ($order['customer_phone'] ?? ''),
                'ibs_status' => (string) ($order['ibs_status'] ?? ''),
                'cost_snapshot_total' => number_format((float) $lineCost, 2),
                'total_qty' => $lineQty,
                'template' => 'ERP Packing Slip (batch)',
            ];
        }

        return [
            'orders' => $orders,
            'message' => count($orders) . ' order(s) ready for batch packing review. Scroll and use Print All or Ctrl+P.',
        ];
    }
}
