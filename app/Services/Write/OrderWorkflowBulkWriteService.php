<?php

namespace App\Services\Write;

use App\ActivityLog;
use App\Domain\OrderWorkflowStatus;
use App\ReadFoundation\WriteGate;
use App\Repositories\OrderItemRepository;
use App\Repositories\Write\OrderWriteRepository;
use App\Services\ReadOnly\OrderWorkflowListReadService;

class OrderWorkflowBulkWriteService
{
    private OrderWorkflowWriteService $workflow;

    private DispatchReportWriteService $dispatch;

    private OrderWriteRepository $orders;

    public function __construct(
        ?OrderWorkflowWriteService $workflow = null,
        ?DispatchReportWriteService $dispatch = null,
        ?OrderWriteRepository $orders = null
    ) {
        $this->workflow = $workflow ?? new OrderWorkflowWriteService();
        $this->dispatch = $dispatch ?? new DispatchReportWriteService();
        $this->orders = $orders ?? new OrderWriteRepository();
    }

    /**
     * @param array<string, mixed> $input
     */
    public function execute(string $action, array $orderIds, array $input): WriteResult
    {
        $action = trim($action);
        $orderIds = array_values(array_filter(array_map('intval', $orderIds), static fn (int $id): bool => $id > 0));
        if ($orderIds === []) {
            return WriteResult::fail('Select at least one order row.');
        }

        if (count($orderIds) > OrderWorkflowListReadService::MAX_PER_PAGE) {
            return WriteResult::fail('Maximum ' . OrderWorkflowListReadService::MAX_PER_PAGE . ' orders per bulk action.');
        }

        if ($action === 'bulk_dispatch') {
            $validation = $this->validateHomogeneousStatus($orderIds, 'shipped');
            if ($validation !== null) {
                return $validation;
            }

            return $this->bulkDispatch($orderIds, $input);
        }

        if ($action === 'bulk_hold') {
            return $this->bulkException($orderIds, 'hold', $input);
        }

        if ($action === 'bulk_cancel') {
            return $this->bulkException($orderIds, 'cancelled', $input);
        }

        $map = [
            'bulk_receive' => ['from' => 'new_order', 'to' => 'order_received', 'checkbox' => false],
            'bulk_packaging' => ['from' => 'order_received', 'to' => 'packaging', 'checkbox' => true],
            'bulk_shipped' => ['from' => 'packaging', 'to' => 'shipped', 'checkbox' => true],
        ];

        if (!isset($map[$action])) {
            return WriteResult::fail('Unknown bulk action.');
        }

        $rule = $map[$action];
        $validation = $this->validateHomogeneousStatus($orderIds, $rule['from']);
        if ($validation !== null) {
            return $validation;
        }

        $staffConfirmed = !empty($input['staff_confirmation']);
        $actionConfirmed = !empty($input['action_confirmed']);
        if (!$actionConfirmed) {
            return WriteResult::fail('Bulk action confirmation is required.');
        }

        if ($rule['checkbox'] && !$staffConfirmed) {
            return WriteResult::fail('Staff confirmation checkbox is required for this bulk action.');
        }

        $success = 0;
        $errors = [];
        foreach ($orderIds as $orderId) {
            $result = $this->workflow->transition(
                $orderId,
                $rule['to'],
                null,
                $staffConfirmed,
                true
            );
            if ($result->success) {
                $success++;
            } else {
                $errors[] = 'Order #' . $orderId . ': ' . $result->message;
            }
        }

        (new OrderWorkflowListReadService())->invalidateCache();

        if ($success === 0) {
            return WriteResult::fail($errors[0] ?? 'Bulk action failed for all selected orders.');
        }

        $message = $success . ' order(s) updated.';
        if ($errors !== []) {
            $message .= ' ' . count($errors) . ' failed.';
        }

        if ($action === 'bulk_packaging' && $success > 0) {
            $message .= ' Invoice tab opened for packing review.';
        }

        ActivityLog::record('order_workflow_bulk_action', 'Bulk workflow: ' . $action, [
            'action' => $action,
            'action_key' => $action,
            'order_ids' => $orderIds,
            'success' => $success,
            'failed' => count($errors),
        ]);

        return WriteResult::ok($message);
    }

    /**
     * @param array<int, int> $orderIds
     * @param array<string, mixed> $input
     */
    private function bulkException(array $orderIds, string $toStatus, array $input): WriteResult
    {
        if (empty($input['action_confirmed'])) {
            return WriteResult::fail('Bulk action confirmation is required.');
        }

        $note = trim((string) ($input['action_note'] ?? ''));
        if ($note === '') {
            return WriteResult::fail('Action note is required for bulk ' . OrderWorkflowStatus::label($toStatus) . '.');
        }

        $success = 0;
        $errors = [];
        foreach ($orderIds as $orderId) {
            $order = $this->orders->find($orderId);
            if ($order === null) {
                $errors[] = 'Order #' . $orderId . ': not found.';
                continue;
            }

            $fromStatus = OrderWorkflowStatus::normalize((string) ($order['ibs_status'] ?? 'new_order'));
            if (!OrderWorkflowStatus::canHoldOrCancel($fromStatus) && !($fromStatus === 'hold' && $toStatus === 'cancelled')) {
                $errors[] = 'Order #' . $orderId . ': ' . OrderWorkflowStatus::label($fromStatus) . ' cannot move to ' . OrderWorkflowStatus::label($toStatus) . '.';
                continue;
            }

            $result = $this->workflow->transition($orderId, $toStatus, $note, false, true);
            if ($result->success) {
                $success++;
            } else {
                $errors[] = 'Order #' . $orderId . ': ' . $result->message;
            }
        }

        (new OrderWorkflowListReadService())->invalidateCache();

        if ($success === 0) {
            return WriteResult::fail($errors[0] ?? 'Bulk action failed for all selected orders.');
        }

        $message = $success . ' order(s) moved to ' . OrderWorkflowStatus::label($toStatus) . '.';
        if ($errors !== []) {
            $message .= ' ' . count($errors) . ' failed.';
        }

        ActivityLog::record('order_workflow_bulk_action', 'Bulk workflow: bulk_' . $toStatus, [
            'action' => 'bulk_' . $toStatus,
            'action_key' => 'bulk_' . $toStatus,
            'order_ids' => $orderIds,
            'success' => $success,
            'failed' => count($errors),
        ]);

        return WriteResult::ok($message);
    }

    /**
     * @param array<int, int> $orderIds
     */
    private function validateHomogeneousStatus(array $orderIds, string $expectedStatus): ?WriteResult
    {
        if (!$this->orders->tableExists()) {
            return WriteResult::fail('Orders table not available.');
        }

        $expected = OrderWorkflowStatus::normalize($expectedStatus);
        $supplierId = null;

        foreach ($orderIds as $orderId) {
            $order = $this->orders->find($orderId);
            if ($order === null) {
                return WriteResult::fail('Order #' . $orderId . ' not found.');
            }

            $status = OrderWorkflowStatus::normalize((string) ($order['ibs_status'] ?? 'new_order'));
            if ($status !== $expected) {
                return WriteResult::fail(
                    'All selected orders must be in '
                    . OrderWorkflowStatus::label($expected)
                    . '. Order #' . $orderId . ' is '
                    . OrderWorkflowStatus::label($status)
                    . '.'
                );
            }

            $orderSupplierId = ($order['supplier_id'] ?? null) !== null && $order['supplier_id'] !== ''
                ? (int) $order['supplier_id']
                : null;
            if ($supplierId === null) {
                $supplierId = $orderSupplierId;
            } elseif ($orderSupplierId !== null && $orderSupplierId !== $supplierId) {
                return WriteResult::fail('All selected orders must belong to the same supplier.');
            }
        }

        return null;
    }

    /**
     * @param array<int, int> $orderIds
     * @param array<string, mixed> $input
     */
    private function bulkDispatch(array $orderIds, array $input): WriteResult
    {
        if (!(WriteGate::dispatchReports()['ready'] ?? false)) {
            return WriteResult::fail('Dispatch Reports module is not ready. Apply migration 0006 first.');
        }

        if (empty($input['batch_confirmed'])) {
            return WriteResult::fail('Dispatch batch confirmation is required.');
        }

        $payload = [
            'order_ids' => $orderIds,
            'batch_confirmed' => '1',
        ];

        $result = $this->dispatch->createDailyBatch($payload);
        (new OrderWorkflowListReadService())->invalidateCache();

        return $result;
    }

    /**
     * @param array<int, int> $orderIds
     * @return array<string, mixed>
     */
    public function selectionTotals(array $orderIds): array
    {
        $orderIds = array_values(array_filter(array_map('intval', $orderIds), static fn (int $id): bool => $id > 0));
        $totals = [
            'order_count' => count($orderIds),
            'total_quantity' => 0,
            'total_products' => 0,
            'total_cost' => 0.0,
            'statuses' => [],
            'bulk_action_key' => null,
            'homogeneous' => true,
        ];

        if ($orderIds === [] || !$this->orders->tableExists()) {
            return $totals;
        }

        $items = new OrderItemRepository();
        $statusSet = [];

        foreach ($orderIds as $orderId) {
            $order = $this->orders->find($orderId);
            if ($order === null) {
                continue;
            }

            $status = OrderWorkflowStatus::normalize((string) ($order['ibs_status'] ?? 'new_order'));
            $statusSet[$status] = true;
            $lineItems = $items->tableExists() ? $items->findByOrderId($orderId) : [];
            $totals['total_products'] += count($lineItems);
            foreach ($lineItems as $line) {
                $qty = max(1, (int) ($line['quantity'] ?? 0));
                $cost = (float) ($line['supplier_cost_snapshot'] ?? 0);
                $totals['total_quantity'] += $qty;
                $totals['total_cost'] += $cost * $qty;
            }
        }

        $statuses = array_keys($statusSet);
        $totals['statuses'] = $statuses;
        $totals['homogeneous'] = count($statuses) === 1;
        if ($totals['homogeneous'] && $statuses !== []) {
            $dispatchReady = WriteGate::dispatchReports()['ready'] ?? false;
            $totals['bulk_action_key'] = match ($statuses[0]) {
                'new_order' => 'bulk_receive',
                'order_received' => 'bulk_packaging',
                'packaging' => 'bulk_shipped',
                'shipped' => $dispatchReady ? 'bulk_dispatch' : null,
                default => null,
            };
        }

        $totals['total_cost'] = round($totals['total_cost'], 2);

        return $totals;
    }
}
