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
}
