<?php

namespace App\Services\Write;

use App\ActivityLog;
use App\Auth;
use App\Domain\OrderFulfillmentPolicy;
use App\Domain\OrderWorkflowStatus;
use App\Repositories\DispatchReportRepository;
use App\Repositories\OrderWorkflowHistoryRepository;
use App\Repositories\UserRepository;
use App\Repositories\Write\OrderWorkflowHistoryWriteRepository;
use App\Repositories\Write\OrderWriteRepository;

class OrderWorkflowWriteService
{
    private OrderWriteRepository $orders;
    private OrderWorkflowHistoryWriteRepository $history;
    private OrderWorkflowHistoryRepository $historyRead;
    private UserRepository $users;
    private DispatchReportRepository $dispatchReports;

    public function __construct(
        ?OrderWriteRepository $orders = null,
        ?OrderWorkflowHistoryWriteRepository $history = null,
        ?OrderWorkflowHistoryRepository $historyRead = null,
        ?UserRepository $users = null,
        ?DispatchReportRepository $dispatchReports = null
    ) {
        $this->orders = $orders ?? new OrderWriteRepository();
        $this->history = $history ?? new OrderWorkflowHistoryWriteRepository();
        $this->historyRead = $historyRead ?? new OrderWorkflowHistoryRepository();
        $this->users = $users ?? new UserRepository();
        $this->dispatchReports = $dispatchReports ?? new DispatchReportRepository();
    }

    public function transition(
        int $orderId,
        string $toStatus,
        ?string $note = null,
        bool $staffConfirmed = false,
        bool $actionConfirmed = false
    ): WriteResult {
        if (!$this->orders->tableExists()) {
            return WriteResult::fail('Orders table not available.');
        }

        $order = $this->orders->find($orderId);
        if ($order === null) {
            return WriteResult::fail('Order not found.');
        }

        $fromStatus = OrderWorkflowStatus::normalize((string) ($order['ibs_status'] ?? 'new_order'));
        // #region agent log
        @file_put_contents(
            dirname(__DIR__, 3) . '/debug-f76280.log',
            json_encode([
                'sessionId' => 'f76280',
                'timestamp' => (int) round(microtime(true) * 1000),
                'location' => 'OrderWorkflowWriteService::transition:entry',
                'message' => 'transition requested',
                'data' => [
                    'orderId' => $orderId,
                    'fromStatus' => $fromStatus,
                    'rawToStatus' => trim($toStatus),
                ],
                'hypothesisId' => 'D',
            ], JSON_UNESCAPED_SLASHES) . PHP_EOL,
            FILE_APPEND
        );
        // #endregion
        $rawToStatus = trim($toStatus);
        $pendingToStatus = OrderWorkflowStatus::isResumeAction($rawToStatus)
            ? $rawToStatus
            : OrderWorkflowStatus::normalize($rawToStatus);
        if ($this->isBatchLocked($orderId, $fromStatus, $pendingToStatus)) {
            $hubTargets = ['hub_returning', 'hub_return'];
            if (in_array(OrderWorkflowStatus::normalize($pendingToStatus), $hubTargets, true)
                && OrderFulfillmentPolicy::hubReturnBlockedAfterDispatch($orderId)) {
                return WriteResult::fail(
                    'Hub return cannot be confirmed after dispatch. Use Customer Return to Supplier for post-dispatch returns.'
                );
            }

            return WriteResult::fail('Created Report orders are locked from normal workflow actions.');
        }

        if ($this->isDispatchImmutable($orderId, $fromStatus, $pendingToStatus)) {
            return WriteResult::fail(
                'This order is locked by a dispatch report. After dispatch, only Delivery Stop and Return workflow actions apply.'
            );
        }

        if (in_array($fromStatus, ['delivered', 'cancelled', 'hub_return', 'order_returning'], true)) {
            return WriteResult::fail('No workflow action is allowed for ' . OrderWorkflowStatus::label($fromStatus) . ' orders.');
        }

        if (!OrderWorkflowStatus::isResumeAction($rawToStatus)) {
            $hubTargets = ['hub_returning', 'hub_return'];
            if (in_array(OrderWorkflowStatus::normalize($pendingToStatus), $hubTargets, true)
                && OrderFulfillmentPolicy::hubReturnBlockedAfterDispatch($orderId)) {
                return WriteResult::fail(
                    'Hub return cannot be confirmed after dispatch. Use Customer Return to Supplier for post-dispatch returns.'
                );
            }
        }

        $resumeAdjustmentNote = null;

        if (OrderWorkflowStatus::isResumeAction($rawToStatus)) {
            if ($fromStatus !== 'hold') {
                return WriteResult::fail('Resume Order is only allowed from Hold.');
            }

            $resolved = $this->resolveResumeTarget($orderId);
            if ($resolved === null) {
                return WriteResult::fail('Cannot resume order: previous active status not found in workflow history.');
            }

            $toStatus = $resolved['status'];
            $resumeAdjustmentNote = $resolved['adjustment_note'];
        } else {
            $toStatus = OrderWorkflowStatus::normalize($rawToStatus);

            if ($toStatus === 'new_order') {
                return WriteResult::fail('Workflow cannot move backward to New Order.');
            }

            if (!OrderWorkflowStatus::isKnown($toStatus)) {
                return WriteResult::fail('Unknown workflow status: ' . $toStatus . '.');
            }

            if (!OrderWorkflowStatus::canTransition($fromStatus, $toStatus)) {
                $failMsg = 'Invalid workflow transition from '
                    . OrderWorkflowStatus::label($fromStatus)
                    . ' to '
                    . OrderWorkflowStatus::label($toStatus)
                    . '.';
                // #region agent log
                @file_put_contents(
                    dirname(__DIR__, 3) . '/debug-f76280.log',
                    json_encode([
                        'sessionId' => 'f76280',
                        'timestamp' => (int) round(microtime(true) * 1000),
                        'location' => 'OrderWorkflowWriteService::transition:invalid',
                        'message' => 'transition rejected',
                        'data' => ['orderId' => $orderId, 'fromStatus' => $fromStatus, 'toStatus' => $toStatus, 'failMsg' => $failMsg],
                        'hypothesisId' => 'D',
                    ], JSON_UNESCAPED_SLASHES) . PHP_EOL,
                    FILE_APPEND
                );
                // #endregion
                return WriteResult::fail($failMsg);
            }

            if (in_array($toStatus, ['hold', 'cancelled'], true)) {
                $holdCancelAllowed = OrderWorkflowStatus::canHoldOrCancel($fromStatus)
                    || ($fromStatus === 'hold' && $toStatus === 'cancelled');
                if (!$holdCancelAllowed) {
                    return WriteResult::fail(
                        'Hold and Cancel are not allowed after Created Report for '
                        . OrderWorkflowStatus::label($fromStatus)
                        . '.'
                    );
                }
            }
        }

        if (OrderWorkflowStatus::requiresConfirmDialog($fromStatus, $rawToStatus) && !$actionConfirmed) {
            return WriteResult::fail('Action confirmation is required before submitting this workflow move.');
        }

        if (OrderWorkflowStatus::requiresCheckbox($fromStatus, $toStatus) && !$staffConfirmed) {
            return WriteResult::fail('Staff confirmation checkbox is required for this action.');
        }

        if (OrderWorkflowStatus::requiresNote($toStatus) && trim((string) $note) === '') {
            return WriteResult::fail(
                'Action note is required for '
                . OrderWorkflowStatus::actionLabel($fromStatus, $rawToStatus)
                . '.'
            );
        }

        $actionKey = OrderWorkflowStatus::isResumeAction($rawToStatus)
            ? 'resume'
            : ($fromStatus . '|' . $toStatus);
        $historyNote = $this->buildHistoryNote($note, $resumeAdjustmentNote, $actionKey);

        $this->orders->updateStatus($orderId, $toStatus);

        $orderReference = (string) ($order['order_reference'] ?? '');
        if ($orderReference !== '') {
            $this->orders->mirrorManualOrderStatusByReference($orderReference, $toStatus);
        }

        $changedBy = $this->resolveChangedById();

        if ($this->history->tableExists()) {
            $this->history->insert($orderId, null, $fromStatus, $toStatus, $historyNote, $changedBy);
        }

        $actionLabel = OrderWorkflowStatus::isResumeAction($rawToStatus)
            ? 'Resume Order'
            : OrderWorkflowStatus::actionLabel($fromStatus, $toStatus);

        ActivityLog::record('order_workflow_action', 'Order workflow: ' . $actionLabel, [
            'order_id' => $orderId,
            'from' => $fromStatus,
            'to' => $toStatus,
            'action' => $actionLabel,
            'action_key' => $actionKey,
            'changed_by' => $changedBy,
            'user' => Auth::user(),
        ]);

        $message = OrderWorkflowStatus::isResumeAction($rawToStatus)
            ? 'Order resumed to ' . OrderWorkflowStatus::label($toStatus) . '.'
            : 'Order updated to ' . OrderWorkflowStatus::label($toStatus) . '.';

        return WriteResult::ok($message, $orderId);
    }

    public function recordDispatchInclusion(int $orderId, string $dispatchReference): WriteResult
    {
        if (!$this->orders->tableExists()) {
            return WriteResult::fail('Orders table not available.');
        }

        $order = $this->orders->find($orderId);
        if ($order === null) {
            return WriteResult::fail('Order not found.');
        }

        $fromStatus = OrderWorkflowStatus::normalize((string) ($order['ibs_status'] ?? 'new_order'));
        $toStatus = 'dispatch_report_created';

        if ($fromStatus !== 'shipped') {
            return WriteResult::fail('Dispatch inclusion requires order status Shipped.');
        }

        $dispatchReference = trim($dispatchReference);
        $historyNote = '[action:create_dispatch_report] Dispatch Report ' . $dispatchReference;

        if (!$this->orders->updateStatus($orderId, $toStatus)) {
            return WriteResult::fail('Failed to update order status to Dispatch Report Created.');
        }

        $orderReference = (string) ($order['order_reference'] ?? '');
        if ($orderReference !== '') {
            $this->orders->mirrorManualOrderStatusByReference($orderReference, $toStatus);
        }

        $changedBy = $this->resolveChangedById();

        if ($this->history->tableExists()) {
            $this->history->insert($orderId, null, $fromStatus, $toStatus, $historyNote, $changedBy);
        }

        ActivityLog::record('order_workflow_action', 'Order workflow: Create Dispatch Report', [
            'action' => 'create_dispatch_report',
            'order_id' => $orderId,
            'from' => $fromStatus,
            'to' => $toStatus,
            'old_status' => $fromStatus,
            'new_status' => $toStatus,
            'batch_reference' => $dispatchReference,
            'dispatch_reference' => $dispatchReference,
            'changed_by' => $changedBy,
            'user' => Auth::user(),
        ]);

        return WriteResult::ok('Order included in dispatch report ' . $dispatchReference . '.', $orderId);
    }

    public function recordReturnReceived(int $orderId, string $actionNote): WriteResult
    {
        if (!$this->orders->tableExists()) {
            return WriteResult::fail('Orders table not available.');
        }

        $order = $this->orders->find($orderId);
        if ($order === null) {
            return WriteResult::fail('Order not found.');
        }

        $currentStatus = OrderWorkflowStatus::normalize((string) ($order['ibs_status'] ?? 'new_order'));
        $actionNote = trim($actionNote);
        if ($actionNote === '') {
            return WriteResult::fail('Return receive history note is required.');
        }

        $changedBy = $this->resolveChangedById();

        if ($this->history->tableExists()) {
            $this->history->insert($orderId, null, $currentStatus, $currentStatus, $actionNote, $changedBy);
        }

        ActivityLog::record('order_workflow_action', 'Order workflow: Return Received confirmation', [
            'order_id' => $orderId,
            'status' => $currentStatus,
            'action_note' => $actionNote,
            'changed_by' => $changedBy,
            'user' => Auth::user(),
        ]);

        return WriteResult::ok('Return receive recorded in workflow history.', $orderId);
    }

    public function recordNote(int $orderId, string $note): WriteResult
    {
        if (!$this->orders->tableExists()) {
            return WriteResult::fail('Orders table not available.');
        }

        $order = $this->orders->find($orderId);
        if ($order === null) {
            return WriteResult::fail('Order not found.');
        }

        $note = trim($note);
        if ($note === '') {
            return WriteResult::fail('Note text is required.');
        }

        $currentStatus = OrderWorkflowStatus::normalize((string) ($order['ibs_status'] ?? 'new_order'));
        $historyNote = '[note] ' . $note;
        $changedBy = $this->resolveChangedById();

        if ($this->history->tableExists()) {
            $this->history->insert($orderId, null, $currentStatus, $currentStatus, $historyNote, $changedBy);
        }

        ActivityLog::record('order_workflow_note', 'Order workflow note added', [
            'order_id' => $orderId,
            'status' => $currentStatus,
            'action_key' => 'add_note',
            'changed_by' => $changedBy,
            'user' => Auth::user(),
        ]);

        return WriteResult::ok('Note added to workflow history.', $orderId);
    }

    /**
     * @return array{status: string, adjustment_note: ?string}|null
     */
    private function resolveResumeTarget(int $orderId): ?array
    {
        if (!$this->historyRead->tableExists()) {
            return null;
        }

        $histories = $this->historyRead->findByOrderId($orderId, 50);

        foreach ($histories as $row) {
            if (OrderWorkflowStatus::normalize((string) ($row['to_status'] ?? '')) !== 'hold') {
                continue;
            }

            $previous = OrderWorkflowStatus::normalize((string) ($row['from_status'] ?? ''));
            if ($previous === 'hold' || OrderWorkflowStatus::isTerminal($previous)) {
                continue;
            }

            $adjustmentNote = null;
            if ($previous === 'new_order') {
                $adjustmentNote = 'Resume adjusted: supplier cannot return to New Order; restored to Order Received.';
            } elseif ($previous !== 'order_received') {
                $adjustmentNote = 'Resume restored to Order Received (was ' . OrderWorkflowStatus::label($previous) . ').';
            }

            return [
                'status' => 'order_received',
                'adjustment_note' => $adjustmentNote,
            ];
        }

        return [
            'status' => 'order_received',
            'adjustment_note' => 'Resume restored to Order Received (no prior hold history found).',
        ];
    }

    private function buildHistoryNote(?string $note, ?string $adjustmentNote, ?string $actionKey = null): ?string
    {
        $parts = [];
        if ($actionKey !== null && trim($actionKey) !== '') {
            $parts[] = '[action:' . trim($actionKey) . ']';
        }
        $note = trim((string) $note);
        if ($note !== '') {
            $parts[] = $note;
        }
        if ($adjustmentNote !== null && trim($adjustmentNote) !== '') {
            $parts[] = trim($adjustmentNote);
        }

        if ($parts === []) {
            return null;
        }

        return implode(' ', $parts);
    }

    private function isDispatchImmutable(int $orderId, string $fromStatus, string $toStatus): bool
    {
        if ($orderId <= 0 || OrderWorkflowStatus::isResumeAction($toStatus) || !$this->orderIsDispatchLocked($orderId)) {
            return false;
        }

        $toStatus = OrderWorkflowStatus::normalize($toStatus);
        if ($toStatus === 'delivery_stop') {
            return false;
        }

        $hubPathTargets = ['hub_returning', 'hub_return'];
        if (in_array($toStatus, $hubPathTargets, true)) {
            return true;
        }

        $supplierRollbackTargets = ['new_order', 'order_received', 'packaging', 'shipped', 'hold', 'cancelled'];

        return in_array($toStatus, $supplierRollbackTargets, true);
    }

    private function isBatchLocked(int $orderId, string $fromStatus, string $toStatus = ''): bool
    {
        if (in_array($toStatus, ['delivery_stop'], true)) {
            return false;
        }

        $hubPathTargets = ['hub_returning', 'hub_return'];
        if (in_array($toStatus, $hubPathTargets, true) && $this->orderIsDispatchLocked($orderId)) {
            return true;
        }

        if (in_array($fromStatus, ['dispatch_report_created', 'in_review', 'in_transit', 'out_for_delivery'], true)) {
            return true;
        }

        return $this->orderIsDispatchLocked($orderId);
    }

    private function orderIsDispatchLocked(int $orderId): bool
    {
        if ($orderId <= 0 || !$this->dispatchReports->tableExists()) {
            return false;
        }

        return $this->dispatchReports->findDispatchItemForOrder($orderId) !== null;
    }

    private function resolveChangedById(): ?int
    {
        $username = Auth::user();
        if ($username === null || $username === '') {
            return null;
        }

        if (!$this->users->tableExists()) {
            return null;
        }

        $user = $this->users->findByUsername((string) $username);
        if ($user === null) {
            return null;
        }

        $userId = (int) ($user['user_id'] ?? 0);

        return $userId > 0 ? $userId : null;
    }
}
