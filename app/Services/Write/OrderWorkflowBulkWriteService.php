<?php

namespace App\Services\Write;

use App\ActivityLog;
use App\ReadFoundation\WriteGate;
use App\Services\ReadOnly\OrderWorkflowListReadService;

class OrderWorkflowBulkWriteService
{
    private OrderWorkflowWriteService $workflow;

    private DispatchReportWriteService $dispatch;

    public function __construct(
        ?OrderWorkflowWriteService $workflow = null,
        ?DispatchReportWriteService $dispatch = null
    ) {
        $this->workflow = $workflow ?? new OrderWorkflowWriteService();
        $this->dispatch = $dispatch ?? new DispatchReportWriteService();
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
            return $this->bulkDispatch($orderIds, $input);
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
            $message .= ' Prepare packing documents on Invoice Printing if needed.';
        }

        ActivityLog::record('order_workflow_bulk_action', 'Bulk workflow: ' . $action, [
            'action' => $action,
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
}
