<?php

namespace App\Services\Write;

use App\ActivityLog;
use App\Auth;
use App\Domain\OrderWorkflowStatus;
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

    public function __construct(
        ?OrderWriteRepository $orders = null,
        ?OrderWorkflowHistoryWriteRepository $history = null,
        ?OrderWorkflowHistoryRepository $historyRead = null,
        ?UserRepository $users = null
    ) {
        $this->orders = $orders ?? new OrderWriteRepository();
        $this->history = $history ?? new OrderWorkflowHistoryWriteRepository();
        $this->historyRead = $historyRead ?? new OrderWorkflowHistoryRepository();
        $this->users = $users ?? new UserRepository();
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
        $rawToStatus = trim($toStatus);
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
                return WriteResult::fail(
                    'Invalid workflow transition from '
                    . OrderWorkflowStatus::label($fromStatus)
                    . ' to '
                    . OrderWorkflowStatus::label($toStatus)
                    . '.'
                );
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

        $historyNote = $this->buildHistoryNote($note, $resumeAdjustmentNote);

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
            'changed_by' => $changedBy,
            'user' => Auth::user(),
        ]);

        $message = OrderWorkflowStatus::isResumeAction($rawToStatus)
            ? 'Order resumed to ' . OrderWorkflowStatus::label($toStatus) . '.'
            : 'Order updated to ' . OrderWorkflowStatus::label($toStatus) . '.';

        return WriteResult::ok($message, $orderId);
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

            if ($previous === 'new_order') {
                return [
                    'status' => 'order_received',
                    'adjustment_note' => 'Resume adjusted: supplier cannot return to New Order; restored to Order Received.',
                ];
            }

            if (in_array($previous, OrderWorkflowStatus::validResumeTargets(), true)) {
                return [
                    'status' => $previous,
                    'adjustment_note' => null,
                ];
            }

            return null;
        }

        return null;
    }

    private function buildHistoryNote(?string $note, ?string $adjustmentNote): ?string
    {
        $parts = [];
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
