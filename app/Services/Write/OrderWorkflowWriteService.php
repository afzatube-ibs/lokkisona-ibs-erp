<?php

namespace App\Services\Write;

use App\ActivityLog;
use App\Repositories\Write\OrderWorkflowHistoryWriteRepository;
use App\Repositories\Write\OrderWriteRepository;

class OrderWorkflowWriteService
{
    private const TRANSITIONS = [
        'new_order' => ['confirmed', 'cancelled'],
        'confirmed' => ['processing', 'cancelled'],
        'processing' => ['ready_for_dispatch', 'cancelled'],
        'ready_for_dispatch' => ['shipped'],
    ];

    private OrderWriteRepository $orders;
    private OrderWorkflowHistoryWriteRepository $history;

    public function __construct(
        ?OrderWriteRepository $orders = null,
        ?OrderWorkflowHistoryWriteRepository $history = null
    ) {
        $this->orders = $orders ?? new OrderWriteRepository();
        $this->history = $history ?? new OrderWorkflowHistoryWriteRepository();
    }

    public function transition(int $orderId, string $toStatus, ?string $note = null): WriteResult
    {
        if (!$this->orders->tableExists()) {
            return WriteResult::fail('Orders table not available.');
        }

        $order = $this->orders->find($orderId);
        if ($order === null) {
            return WriteResult::fail('Order not found.');
        }

        $fromStatus = $order['ibs_status'] ?? 'new_order';
        $allowed = self::TRANSITIONS[$fromStatus] ?? [];

        if (!in_array($toStatus, $allowed, true)) {
            return WriteResult::fail('Invalid workflow transition from ' . $fromStatus . ' to ' . $toStatus . '.');
        }

        $this->orders->updateStatus($orderId, $toStatus);

        if ($this->history->tableExists()) {
            $this->history->insert($orderId, null, $fromStatus, $toStatus, $note);
        }

        ActivityLog::record('order_workflow_action', 'Order workflow transition', [
            'order_id' => $orderId,
            'from' => $fromStatus,
            'to' => $toStatus,
        ]);

        return WriteResult::ok('Order status updated to ' . $toStatus . '.', $orderId);
    }
}
