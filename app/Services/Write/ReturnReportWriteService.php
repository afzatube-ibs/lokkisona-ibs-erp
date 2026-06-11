<?php

namespace App\Services\Write;

use App\ActivityLog;
use App\Auth;
use App\Database\Connection;
use App\Domain\DispatchCostSnapshot;
use App\Domain\ReturnReceiveReason;
use App\Domain\ReturnReceiveType;
use App\Domain\ReturnReportReference;
use App\Repositories\OrderItemRepository;
use App\Repositories\UserRepository;
use App\Repositories\Write\ReturnReceiveWriteRepository;
use App\Repositories\Write\ReturnReportItemWriteRepository;
use App\Repositories\Write\ReturnReportWriteRepository;
use App\Repositories\Write\OrderWriteRepository;

class ReturnReportWriteService
{
    private ReturnReportWriteRepository $reports;
    private ReturnReportItemWriteRepository $items;
    private ReturnReceiveWriteRepository $receives;
    private OrderWriteRepository $orders;
    private OrderItemRepository $orderItems;
    private UserRepository $users;

    public function __construct(
        ?ReturnReportWriteRepository $reports = null,
        ?ReturnReportItemWriteRepository $items = null,
        ?ReturnReceiveWriteRepository $receives = null,
        ?OrderWriteRepository $orders = null,
        ?OrderItemRepository $orderItems = null,
        ?UserRepository $users = null
    ) {
        $this->reports = $reports ?? new ReturnReportWriteRepository();
        $this->items = $items ?? new ReturnReportItemWriteRepository();
        $this->receives = $receives ?? new ReturnReceiveWriteRepository();
        $this->orders = $orders ?? new OrderWriteRepository();
        $this->orderItems = $orderItems ?? new OrderItemRepository();
        $this->users = $users ?? new UserRepository();
    }

    public function createStatement(array $input): WriteResult
    {
        if (!$this->reports->tableExists() || !$this->items->tableExists()) {
            return WriteResult::fail('Return report tables not available. Apply migration 0007_return_reports_foundation.sql manually first.');
        }

        if (!$this->receives->tableExists()) {
            return WriteResult::fail('Return receive tables not available.');
        }

        if (empty($input['batch_confirmed'])) {
            return WriteResult::fail('Confirmation is required before creating a Supplier Return Statement.');
        }

        $returnReceiveIds = $this->normalizeIds($input['return_receive_ids'] ?? []);
        if ($returnReceiveIds === []) {
            return WriteResult::fail('Select at least one confirmed return for the Return Report.');
        }

        if (count($returnReceiveIds) > 50) {
            return WriteResult::fail('Maximum 50 returns per Return Report.');
        }

        $validated = [];
        $supplierId = null;
        $businessSourceId = null;
        $totalMissingLines = 0;
        $totalQuantity = 0;

        foreach ($returnReceiveIds as $returnReceiveId) {
            $return = $this->receives->find($returnReceiveId);
            if ($return === null) {
                return WriteResult::fail('Return #' . $returnReceiveId . ' not found.');
            }

            $returnType = ReturnReceiveType::normalize((string) ($return['return_type'] ?? ''));
            if (!ReturnReceiveType::isSupplierReturn($returnType)) {
                return WriteResult::fail(
                    'Return ' . (string) ($return['return_reference'] ?? ('#' . $returnReceiveId))
                    . ' is not eligible — only Hub Return and Customer Return can be included in a Return Report.'
                );
            }

            $status = (string) ($return['status'] ?? '');
            if ($status !== 'received') {
                return WriteResult::fail(
                    'Return ' . (string) ($return['return_reference'] ?? ('#' . $returnReceiveId))
                    . ' is not in Received status (already reported or not supplier-confirmed).'
                );
            }

            if ($this->items->existsForReturnReceiveId($returnReceiveId)) {
                return WriteResult::fail(
                    'Return ' . (string) ($return['return_reference'] ?? ('#' . $returnReceiveId))
                    . ' is already included in a Return Report.'
                );
            }

            $returnReason = $this->resolveReturnReason($return);
            if ($returnReason === null || !ReturnReceiveReason::isKnown($returnReason)) {
                return WriteResult::fail(
                    'Return ' . (string) ($return['return_reference'] ?? ('#' . $returnReceiveId))
                    . ' is missing return reason.'
                );
            }

            $orderId = $this->receives->resolveOrderId($returnReceiveId, $return);
            if ($orderId <= 0) {
                return WriteResult::fail(
                    'Return ' . (string) ($return['return_reference'] ?? ('#' . $returnReceiveId))
                    . ' has no linked order — cannot build product lines.'
                );
            }

            $order = $this->orders->find($orderId);
            if ($order === null) {
                return WriteResult::fail('Order #' . $orderId . ' for return #' . $returnReceiveId . ' not found.');
            }

            if (!DispatchCostSnapshot::hasDispatchOrderNo($order)) {
                return WriteResult::fail(
                    'Cannot create Return Report: return '
                    . (string) ($return['return_reference'] ?? ('#' . $returnReceiveId))
                    . ' order #' . $orderId . ' is missing order number.'
                );
            }

            $orderLines = $this->orderItems->findByOrderId($orderId);
            $missingLines = DispatchCostSnapshot::countMissingLineItems($orderLines);
            $totalMissingLines += $missingLines;

            $lineCost = $this->orderItems->sumSupplierCostByOrderId($orderId);
            $lineQty = $this->orderItems->sumQuantityByOrderId($orderId);
            $snapshot = DispatchCostSnapshot::forOrder($order, $lineCost, $lineQty);
            $costSnapshot = (float) $snapshot['product_cost_snapshot'];

            if ($costSnapshot <= 0) {
                return WriteResult::fail(
                    'Return ' . (string) ($return['return_reference'] ?? ('#' . $returnReceiveId))
                    . ' has missing cost/rate — update Products first.'
                );
            }

            $returnSupplierId = ($return['supplier_id'] ?? null) !== null && $return['supplier_id'] !== ''
                ? (int) $return['supplier_id']
                : null;
            $returnSourceId = ($return['business_source_id'] ?? null) !== null && $return['business_source_id'] !== ''
                ? (int) $return['business_source_id']
                : null;

            if ($supplierId === null) {
                $supplierId = $returnSupplierId;
                $businessSourceId = $returnSourceId;
            } elseif ($returnSupplierId !== $supplierId) {
                return WriteResult::fail('All selected returns must belong to the same supplier.');
            }

            if ($businessSourceId === null && $returnSourceId !== null) {
                $businessSourceId = $returnSourceId;
            } elseif ($returnSourceId !== null && $returnSourceId !== $businessSourceId) {
                return WriteResult::fail('All selected returns must belong to the same business source.');
            }

            $totalQuantity += max(0, (int) $snapshot['item_count']);

            $validated[] = [
                'return' => $return,
                'return_receive_id' => $returnReceiveId,
                'return_type' => $returnType,
                'return_reason' => $returnReason,
                'order' => $order,
                'order_id' => $orderId,
                'snapshot' => $snapshot,
            ];
        }

        if ($totalMissingLines > 0) {
            $itemWord = $totalMissingLines === 1 ? 'item' : 'items';

            return WriteResult::fail(
                'Cannot create Return Report: missing cost — update Products first ('
                . $totalMissingLines
                . ' '
                . $itemWord
                . ').'
            );
        }

        $returnDate = ReturnReportReference::returnDate();
        $reference = ReturnReportReference::nextForToday(
            $this->reports->findReferencesByReturnDate($returnDate)
        );

        $totalCost = 0.0;
        foreach ($validated as $entry) {
            $totalCost += (float) $entry['snapshot']['product_cost_snapshot'];
        }
        $totalCost = round($totalCost, 2);

        $createdBy = $this->resolveChangedById();
        $pdo = Connection::pdo();
        $pdo->beginTransaction();

        try {
            $reportId = $this->reports->create([
                'return_report_reference' => $reference,
                'supplier_id' => $supplierId,
                'business_source_id' => $businessSourceId,
                'return_date' => $returnDate,
                'total_returns' => count($validated),
                'total_quantity' => $totalQuantity,
                'total_adjustment_amount' => $totalCost,
                'status' => ReturnReportReference::STATUS_LOCKED,
                'locked_by' => $createdBy,
                'locked_at' => date('Y-m-d H:i:s'),
                'created_by' => $createdBy,
            ]);

            foreach ($validated as $entry) {
                $returnReceiveId = (int) $entry['return_receive_id'];
                if ($this->items->existsForReturnReceiveId($returnReceiveId)) {
                    throw new \RuntimeException('Return #' . $returnReceiveId . ' is already included in a Return Report.');
                }

                $order = $entry['order'];
                $snapshot = $entry['snapshot'];

                $this->items->create([
                    'return_report_id' => $reportId,
                    'return_receive_id' => $returnReceiveId,
                    'order_id' => (int) $entry['order_id'],
                    'manual_order_id' => null,
                    'order_reference' => (string) ($order['order_reference'] ?? ''),
                    'product_cost_snapshot' => $snapshot['product_cost_snapshot'],
                    'item_count' => $snapshot['item_count'],
                    'return_type' => $entry['return_type'],
                    'return_reason' => $entry['return_reason'],
                    'status' => 'included',
                ]);

                if (!$this->receives->markReported($returnReceiveId)) {
                    throw new \RuntimeException('Could not mark return #' . $returnReceiveId . ' as reported.');
                }
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            return WriteResult::fail('Return Report create failed: ' . $e->getMessage());
        }

        ActivityLog::record('return_report_created', 'Supplier Return Statement created (locked snapshot)', [
            'return_report_id' => $reportId,
            'return_report_reference' => $reference,
            'total_returns' => count($validated),
            'total_adjustment_amount' => $totalCost,
            'user' => Auth::user(),
        ]);

        return WriteResult::ok(
            'Supplier Return Statement ' . $reference . ' created and locked with ' . count($validated) . ' return(s). No ledger posting in v2.4.0.',
            $reportId
        );
    }

    /**
     * @param array<string, mixed> $return
     */
    private function resolveReturnReason(array $return): ?string
    {
        $stored = trim((string) ($return['return_reason'] ?? ''));
        if ($stored !== '' && ReturnReceiveReason::isKnown($stored)) {
            return ReturnReceiveReason::normalize($stored);
        }

        return null;
    }

    /**
     * @param mixed $rawIds
     * @return array<int, int>
     */
    private function normalizeIds($rawIds): array
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
