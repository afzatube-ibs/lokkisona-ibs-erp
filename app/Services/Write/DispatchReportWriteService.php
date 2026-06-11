<?php

namespace App\Services\Write;

use App\ActivityLog;
use App\Auth;
use App\Database\Connection;
use App\Domain\DispatchCostSnapshot;
use App\Domain\DispatchReportReference;
use App\Domain\OrderWorkflowStatus;
use App\Repositories\OrderItemRepository;
use App\Repositories\UserRepository;
use App\Repositories\Write\DispatchReportItemWriteRepository;
use App\Repositories\Write\DispatchReportWriteRepository;
use App\Repositories\Write\OrderWriteRepository;

class DispatchReportWriteService
{
    private DispatchReportWriteRepository $reports;
    private DispatchReportItemWriteRepository $items;
    private OrderWriteRepository $orders;
    private OrderItemRepository $orderItems;
    private OrderWorkflowWriteService $workflow;
    private UserRepository $users;

    public function __construct(
        ?DispatchReportWriteRepository $reports = null,
        ?DispatchReportItemWriteRepository $items = null,
        ?OrderWriteRepository $orders = null,
        ?OrderItemRepository $orderItems = null,
        ?OrderWorkflowWriteService $workflow = null,
        ?UserRepository $users = null
    ) {
        $this->reports = $reports ?? new DispatchReportWriteRepository();
        $this->items = $items ?? new DispatchReportItemWriteRepository();
        $this->orders = $orders ?? new OrderWriteRepository();
        $this->orderItems = $orderItems ?? new OrderItemRepository();
        $this->workflow = $workflow ?? new OrderWorkflowWriteService();
        $this->users = $users ?? new UserRepository();
    }

    public function createDailyBatch(array $input): WriteResult
    {
        if (!$this->reports->tableExists() || !$this->items->tableExists()) {
            return WriteResult::fail('Dispatch tables not available. Apply migration 0006_dispatch_returns_payables.sql manually first.');
        }

        if (!$this->orders->tableExists()) {
            return WriteResult::fail('Orders table not available.');
        }

        if (empty($input['batch_confirmed'])) {
            return WriteResult::fail('Batch confirmation is required before creating a Daily Dispatch Statement.');
        }

        $orderIds = $this->normalizeOrderIds($input['order_ids'] ?? []);
        if ($orderIds === []) {
            return WriteResult::fail('Select at least one shipped order for the Daily Dispatch Statement.');
        }

        if (count($orderIds) > 50) {
            return WriteResult::fail('Maximum 50 orders per Daily Dispatch Statement.');
        }

        $validatedOrders = [];
        $supplierId = null;
        $businessSourceId = null;
        $totalMissingLines = 0;

        foreach ($orderIds as $orderId) {
            $order = $this->orders->find($orderId);
            if ($order === null) {
                return WriteResult::fail('Order #' . $orderId . ' not found.');
            }

            $status = OrderWorkflowStatus::normalize((string) ($order['ibs_status'] ?? ''));
            if ($status !== 'shipped') {
                return WriteResult::fail('Order #' . $orderId . ' is not Shipped.');
            }

            if ($this->items->existsForOrderId($orderId)) {
                return WriteResult::fail('Order #' . $orderId . ' is already included in a Daily Dispatch Statement.');
            }

            $orderSupplierId = ($order['supplier_id'] ?? null) !== null && $order['supplier_id'] !== ''
                ? (int) $order['supplier_id']
                : null;
            $orderSourceId = ($order['business_source_id'] ?? null) !== null && $order['business_source_id'] !== ''
                ? (int) $order['business_source_id']
                : null;

            if ($supplierId === null) {
                $supplierId = $orderSupplierId;
                $businessSourceId = $orderSourceId;
            } elseif ($orderSupplierId !== $supplierId) {
                return WriteResult::fail('All selected orders must belong to the same supplier.');
            }

            if ($businessSourceId === null && $orderSourceId !== null) {
                $businessSourceId = $orderSourceId;
            } elseif ($orderSourceId !== $businessSourceId) {
                return WriteResult::fail('All selected orders must belong to the same business source.');
            }

            if (!DispatchCostSnapshot::hasDispatchOrderNo($order)) {
                return WriteResult::fail(
                    'Cannot create Daily Dispatch Statement: order #' . $orderId . ' is missing order number. '
                    . 'Synced orders need Lokkisona source order no; manual/sales orders need invoice/order no.'
                );
            }

            $orderLines = $this->orderItems->findByOrderId($orderId);
            $missingLines = DispatchCostSnapshot::countMissingLineItems($orderLines);
            $totalMissingLines += $missingLines;

            $lineCost = $this->orderItems->sumSupplierCostByOrderId($orderId);
            $lineQty = $this->orderItems->sumQuantityByOrderId($orderId);
            $snapshot = DispatchCostSnapshot::forOrder($order, $lineCost, $lineQty);

            $validatedOrders[] = [
                'order' => $order,
                'snapshot' => $snapshot,
            ];
        }

        if ($totalMissingLines > 0) {
            $itemWord = $totalMissingLines === 1 ? 'item' : 'items';

            return WriteResult::fail(
                'Cannot create Daily Dispatch Statement: missing cost — update Products first ('
                . $totalMissingLines
                . ' '
                . $itemWord
                . ').'
            );
        }

        $dispatchDate = DispatchReportReference::dispatchDate();
        $reference = DispatchReportReference::nextForToday(
            $this->reports->findReferencesByDispatchDate($dispatchDate)
        );

        $totalCost = 0.0;
        foreach ($validatedOrders as $entry) {
            $totalCost += (float) $entry['snapshot']['product_cost_snapshot'];
        }
        $totalCost = round($totalCost, 2);

        $createdBy = $this->resolveChangedById();
        $pdo = Connection::pdo();
        $pdo->beginTransaction();

        try {
            $reportId = $this->reports->create([
                'dispatch_reference' => $reference,
                'supplier_id' => $supplierId,
                'business_source_id' => $businessSourceId,
                'dispatch_date' => $dispatchDate,
                'total_orders' => count($validatedOrders),
                'total_product_cost' => $totalCost,
                'status' => 'locked',
                'locked_by' => $createdBy,
                'locked_at' => date('Y-m-d H:i:s'),
                'created_by' => $createdBy,
            ]);

            foreach ($validatedOrders as $entry) {
                $order = $entry['order'];
                $orderId = (int) ($order['order_id'] ?? 0);
                $snapshot = $entry['snapshot'];

                $status = OrderWorkflowStatus::normalize((string) ($order['ibs_status'] ?? ''));
                if ($status !== 'shipped') {
                    throw new \RuntimeException('Order #' . $orderId . ' is no longer Shipped.');
                }

                if ($this->items->existsForOrderId($orderId)) {
                    throw new \RuntimeException('Order #' . $orderId . ' is already included in a Daily Dispatch Statement.');
                }

                $this->items->create([
                    'dispatch_report_id' => $reportId,
                    'order_id' => $orderId,
                    'manual_order_id' => null,
                    'order_reference' => (string) ($order['order_reference'] ?? ''),
                    'product_cost_snapshot' => $snapshot['product_cost_snapshot'],
                    'item_count' => $snapshot['item_count'],
                    'status' => 'included',
                ]);

                $workflowResult = $this->workflow->recordDispatchInclusion($orderId, $reference);
                if (!$workflowResult->success) {
                    throw new \RuntimeException($workflowResult->message);
                }
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            return WriteResult::fail('Daily Dispatch Statement create failed: ' . $e->getMessage());
        }

        $payableNote = '';
        try {
            $payableResult = (new PayableLedgerWriteService())->createDraftFromDispatch($reportId);
            if ($payableResult->success) {
                $payableNote = ' Payable checkpoint draft PCP-' . $reference . ' created — review on Supplier Payables (no ledger posting yet).';
            } else {
                $payableNote = ' Warning: ' . $payableResult->message;
            }
        } catch (\Throwable $e) {
            $payableNote = ' Warning: Payable checkpoint unavailable — daily dispatch statement still created.';
        }

        ActivityLog::record('dispatch_report_created', 'Daily dispatch statement created (locked snapshot)', [
            'action' => 'create_dispatch_report',
            'dispatch_report_id' => $reportId,
            'batch_reference' => $reference,
            'dispatch_reference' => $reference,
            'old_status' => 'shipped',
            'new_status' => 'dispatch_report_created',
            'total_orders' => count($validatedOrders),
            'total_product_cost' => $totalCost,
            'user' => Auth::user(),
        ]);

        return WriteResult::ok(
            'Daily Dispatch Statement ' . $reference . ' created and locked with ' . count($validatedOrders) . ' order(s).' . $payableNote,
            $reportId
        );
    }

    /**
     * @param mixed $rawIds
     * @return array<int, int>
     */
    private function normalizeOrderIds($rawIds): array
    {
        if (!is_array($rawIds)) {
            return [];
        }

        $ids = [];
        foreach ($rawIds as $rawId) {
            $id = (int) $rawId;
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }

        return array_values($ids);
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
