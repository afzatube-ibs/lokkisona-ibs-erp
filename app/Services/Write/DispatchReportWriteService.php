<?php

namespace App\Services\Write;

use App\ActivityLog;
use App\Repositories\Write\DispatchReportItemWriteRepository;
use App\Repositories\Write\DispatchReportWriteRepository;
use App\Repositories\Write\OrderWriteRepository;

class DispatchReportWriteService
{
    private DispatchReportWriteRepository $reports;
    private DispatchReportItemWriteRepository $items;
    private OrderWriteRepository $orders;

    public function __construct(
        ?DispatchReportWriteRepository $reports = null,
        ?DispatchReportItemWriteRepository $items = null,
        ?OrderWriteRepository $orders = null
    ) {
        $this->reports = $reports ?? new DispatchReportWriteRepository();
        $this->items = $items ?? new DispatchReportItemWriteRepository();
        $this->orders = $orders ?? new OrderWriteRepository();
    }

    public function createFromReadyOrders(array $input): WriteResult
    {
        if (!$this->reports->tableExists() || !$this->items->tableExists()) {
            return WriteResult::fail('Dispatch tables not available. Apply migration 0006 first.');
        }

        $readyOrders = $this->orders->findByStatus('ready_for_dispatch', 100);
        if (empty($readyOrders)) {
            return WriteResult::fail('No orders in ready_for_dispatch status.');
        }

        $supplierId = ($input['supplier_id'] ?? '') !== '' ? (int) $input['supplier_id'] : null;
        $sourceId = ($input['business_source_id'] ?? '') !== '' ? (int) $input['business_source_id'] : null;
        $ref = 'DR-' . date('dmY') . '-' . random_int(100, 999);
        $totalCost = 0.0;

        foreach ($readyOrders as $order) {
            $totalCost += (float) ($order['cost_snapshot_total'] ?? 0);
        }

        $reportId = $this->reports->create([
            'dispatch_reference' => $ref,
            'supplier_id' => $supplierId,
            'business_source_id' => $sourceId,
            'dispatch_date' => date('Y-m-d'),
            'total_orders' => count($readyOrders),
            'total_product_cost' => round($totalCost, 2),
            'status' => 'draft',
        ]);

        foreach ($readyOrders as $order) {
            $this->items->create([
                'dispatch_report_id' => $reportId,
                'order_id' => (int) $order['order_id'],
                'manual_order_id' => null,
                'order_reference' => $order['order_reference'],
                'product_cost_snapshot' => (float) ($order['cost_snapshot_total'] ?? 0),
                'item_count' => 1,
                'status' => 'included',
            ]);
        }

        ActivityLog::record('dispatch_report_created', 'Dispatch report created', [
            'dispatch_report_id' => $reportId,
            'dispatch_reference' => $ref,
        ]);

        return WriteResult::ok('Dispatch report ' . $ref . ' created with ' . count($readyOrders) . ' orders.', $reportId);
    }
}
