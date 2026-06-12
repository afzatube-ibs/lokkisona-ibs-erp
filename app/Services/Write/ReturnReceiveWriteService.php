<?php

namespace App\Services\Write;

use App\ActivityLog;
use App\Auth;
use App\Database\Connection;
use App\Domain\DispatchCostSnapshot;
use App\Domain\OrderFulfillmentPolicy;
use App\Domain\OrderWorkflowStatus;
use App\Domain\ReturnReceiveCondition;
use App\Domain\ReturnReceiveNote;
use App\Domain\ReturnReceivePhysicalConfirmation;
use App\Domain\ReturnReceiveReason;
use App\Domain\ReturnReceiveReference;
use App\Domain\ReturnReceiveType;
use App\Repositories\DispatchReportRepository;
use App\Repositories\OrderItemRepository;
use App\Repositories\UserRepository;
use App\Repositories\Write\OrderWriteRepository;
use App\Repositories\Write\ReturnBatchItemWriteRepository;
use App\Repositories\Write\ReturnBatchWriteRepository;
use App\Repositories\Write\ReturnReceiveWriteRepository;

class ReturnReceiveWriteService
{
    private ReturnReceiveWriteRepository $receives;
    private ReturnBatchWriteRepository $batches;
    private ReturnBatchItemWriteRepository $batchItems;
    private OrderWriteRepository $orders;
    private OrderItemRepository $orderItems;
    private OrderWorkflowWriteService $workflow;
    private UserRepository $users;

    public function __construct(
        ?ReturnReceiveWriteRepository $receives = null,
        ?ReturnBatchWriteRepository $batches = null,
        ?ReturnBatchItemWriteRepository $batchItems = null,
        ?OrderWriteRepository $orders = null,
        ?OrderItemRepository $orderItems = null,
        ?OrderWorkflowWriteService $workflow = null,
        ?UserRepository $users = null
    ) {
        $this->receives = $receives ?? new ReturnReceiveWriteRepository();
        $this->batches = $batches ?? new ReturnBatchWriteRepository();
        $this->batchItems = $batchItems ?? new ReturnBatchItemWriteRepository();
        $this->orders = $orders ?? new OrderWriteRepository();
        $this->orderItems = $orderItems ?? new OrderItemRepository();
        $this->workflow = $workflow ?? new OrderWorkflowWriteService();
        $this->users = $users ?? new UserRepository();
    }

    public function confirmReceive(array $input): WriteResult
    {
        if (!$this->receives->tableExists() || !$this->batches->tableExists() || !$this->batchItems->tableExists()) {
            return WriteResult::fail('Return tables not available. Apply migration 0006_dispatch_returns_payables.sql manually first.');
        }

        if (!$this->orders->tableExists()) {
            return WriteResult::fail('Orders table not available.');
        }

        if (empty($input['receive_confirmed'])) {
            return WriteResult::fail('Return receive confirmation is required before submitting.');
        }

        if (empty($input['staff_confirmation'])) {
            return WriteResult::fail('Staff confirmation checkbox is required for Return Received.');
        }

        $orderId = (int) ($input['order_id'] ?? 0);
        if ($orderId <= 0) {
            return WriteResult::fail('Order is required for return receive.');
        }

        $returnType = ReturnReceiveType::normalize((string) ($input['return_type'] ?? ''));
        if (!ReturnReceiveType::isKnown($returnType)) {
            return WriteResult::fail('Unknown return type.');
        }

        $returnReason = ReturnReceiveReason::normalize((string) ($input['return_reason'] ?? ''));
        if (!ReturnReceiveReason::isKnown($returnReason)) {
            return WriteResult::fail('Return reason/source is required.');
        }

        $receivedConfirmation = ReturnReceivePhysicalConfirmation::normalize((string) ($input['received_confirmation'] ?? ''));
        if (!ReturnReceivePhysicalConfirmation::isKnown($receivedConfirmation)) {
            return WriteResult::fail('Received confirmation is required.');
        }

        $verificationNote = trim((string) ($input['verification_note'] ?? ''));
        $supplierNote = trim((string) ($input['supplier_note'] ?? ''));
        $ownerNote = trim((string) ($input['owner_note'] ?? ''));
        $supplierCondition = null;

        if (ReturnReceiveType::isSupplierReturn($returnType)) {
            $supplierCondition = ReturnReceiveCondition::normalize((string) ($input['supplier_condition'] ?? ''));
            if (!ReturnReceiveCondition::isKnown($supplierCondition)) {
                return WriteResult::fail('Supplier condition is required for supplier returns.');
            }
            if ($supplierNote === '') {
                return WriteResult::fail('Supplier received note is required for supplier returns.');
            }
        } else {
            if ($ownerNote === '') {
                return WriteResult::fail('Owner note is required for Lokkisona / Owner Warehouse returns.');
            }
        }

        $order = $this->orders->find($orderId);
        if ($order === null) {
            return WriteResult::fail('Order #' . $orderId . ' not found.');
        }

        if ($returnType === ReturnReceiveType::HUB_COURIER_RETURN
            && OrderFulfillmentPolicy::hubReturnBlockedAfterDispatch($orderId)) {
            return WriteResult::fail(
                'Hub courier return cannot be confirmed after dispatch. Use Customer Return to Supplier for post-dispatch returns.'
            );
        }

        if ($returnType === ReturnReceiveType::CUSTOMER_RETURN_TO_SUPPLIER
            && !OrderFulfillmentPolicy::customerReturnRequiresDispatch($orderId)) {
            return WriteResult::fail(
                'Customer return to supplier requires a dispatch report first. Pre-dispatch returns use Hub Return workflow only.'
            );
        }

        $expectedStatus = ReturnReceiveType::ibsStatusFor($returnType);
        $currentStatus = OrderWorkflowStatus::normalize((string) ($order['ibs_status'] ?? ''));
        if ($expectedStatus === null || $currentStatus !== $expectedStatus) {
            return WriteResult::fail(
                'Order #' . $orderId . ' must be at '
                . OrderWorkflowStatus::label((string) $expectedStatus)
                . ' for this return type.'
            );
        }

        if ($this->receives->existsForOrderAndType($orderId, $returnType)) {
            return WriteResult::fail('Order #' . $orderId . ' already has a received return record for this type.');
        }

        $dispatchItem = OrderFulfillmentPolicy::lockedDispatchItemForOrder($orderId);
        $lineCost = $this->orderItems->sumSupplierCostByOrderId($orderId);
        $lineQty = $this->orderItems->sumQuantityByOrderId($orderId);
        $snapshot = DispatchCostSnapshot::forOrder($order, $lineCost, $lineQty);
        if ($dispatchItem !== null) {
            $costSnapshot = round((float) ($dispatchItem['product_cost_snapshot'] ?? 0), 2);
            $itemCount = max(1, (int) ($dispatchItem['item_count'] ?? $snapshot['item_count']));
            $dispatchReference = (string) ($dispatchItem['dispatch_reference'] ?? '');
        } else {
            $costSnapshot = (float) $snapshot['product_cost_snapshot'];
            $itemCount = (int) $snapshot['item_count'];
            $dispatchReferences = (new DispatchReportRepository())->findIncludedOrderReferences(50);
            $dispatchReference = $dispatchReferences[$orderId] ?? null;
        }

        $createReturnReport = OrderFulfillmentPolicy::shouldCreateReturnReport($returnType, $orderId);
        $adjustmentAmount = $createReturnReport ? round($costSnapshot, 2) : 0.0;

        $reference = ReturnReceiveReference::forOrder($orderId, $returnType);
        $receivedBy = $this->resolveChangedById();
        $receivedAt = date('Y-m-d H:i:s');
        $historyNote = ReturnReceiveNote::build([
            'return_type' => $returnType,
            'return_reason' => $returnReason,
            'order_id' => $orderId,
            'order_reference' => (string) ($order['order_reference'] ?? ''),
            'consignment_id' => (string) ($order['tracking_number'] ?? ''),
            'dispatch_report_reference' => $dispatchReference,
            'verification_note' => $verificationNote,
            'received_confirmation' => $receivedConfirmation,
            'supplier_condition' => $supplierCondition,
            'supplier_note' => $supplierNote,
            'owner_note' => $ownerNote,
        ]);

        $supplierId = ($order['supplier_id'] ?? null) !== null && $order['supplier_id'] !== ''
            ? (int) $order['supplier_id']
            : null;
        $businessSourceId = ($order['business_source_id'] ?? null) !== null && $order['business_source_id'] !== ''
            ? (int) $order['business_source_id']
            : null;

        $pdo = Connection::pdo();
        $pdo->beginTransaction();

        try {
            $receiveId = $this->receives->create([
                'return_reference' => $reference,
                'supplier_id' => $supplierId,
                'business_source_id' => $businessSourceId,
                'return_type' => $returnType,
                'order_id' => $orderId,
                'return_reason' => $returnReason,
                'total_items' => max(1, $itemCount),
                'total_cost_snapshot' => $costSnapshot,
                'status' => 'received',
                'received_by' => $receivedBy,
                'received_at' => $receivedAt,
            ]);

            if ($createReturnReport) {
                $batchId = $this->batches->create([
                    'return_batch_reference' => $reference,
                    'supplier_id' => $supplierId,
                    'total_returns' => 1,
                    'total_adjustment_amount' => $adjustmentAmount,
                    'status' => 'received',
                ]);

                $this->batchItems->create([
                    'return_batch_id' => $batchId,
                    'return_receive_id' => $receiveId,
                    'order_id' => $orderId,
                    'manual_order_id' => null,
                    'product_id' => null,
                    'product_variant_id' => null,
                    'quantity' => max(1, $itemCount),
                    'cost_snapshot' => $costSnapshot,
                    'adjustment_amount' => $adjustmentAmount,
                    'status' => 'received',
                ]);
            }
            $workflowResult = $this->workflow->recordReturnReceived($orderId, $historyNote);
            if (!$workflowResult->success) {
                throw new \RuntimeException($workflowResult->message);
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            return WriteResult::fail('Return receive failed: ' . $e->getMessage());
        }

        ActivityLog::record('return_receive_confirmed', 'Return receive confirmed', [
            'return_receive_id' => $receiveId,
            'return_reference' => $reference,
            'order_id' => $orderId,
            'return_type' => $returnType,
            'return_reason' => $returnReason,
            'received_confirmation' => $receivedConfirmation,
            'supplier_condition' => $supplierCondition,
            'verification_note' => $verificationNote,
            'dispatch_report_reference' => $dispatchReference,
            'supplier_note' => $supplierNote,
            'owner_note' => $ownerNote,
            'action_note' => $historyNote,
            'user' => Auth::user(),
        ]);

        $reportNote = $createReturnReport
            ? ' Return report batch created for payable adjustment.'
            : ' Workflow closure only — no return report or payable impact.';

        return WriteResult::ok(
            'Return received for order ' . (string) ($order['order_reference'] ?? $orderId) . ' (' . ReturnReceiveType::label($returnType) . ').' . $reportNote,
            $receiveId
        );
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
