<?php

namespace App\Services\Write;

use App\ActivityLog;
use App\Auth;
use App\Database\Connection;
use App\Domain\ReturnBatchReference;
use App\Repositories\ReturnReceiveRepository;
use App\Repositories\Write\ReturnBatchItemWriteRepository;
use App\Repositories\Write\ReturnBatchWriteRepository;
use App\Repositories\Write\ReturnReceiveWriteRepository;

class ReturnBatchWriteService
{
    private ReturnBatchWriteRepository $batches;
    private ReturnBatchItemWriteRepository $batchItems;
    private ReturnReceiveWriteRepository $returnReceives;
    private ReturnReceiveRepository $returnReceiveReader;

    public function __construct(
        ?ReturnBatchWriteRepository $batches = null,
        ?ReturnBatchItemWriteRepository $batchItems = null,
        ?ReturnReceiveWriteRepository $returnReceives = null,
        ?ReturnReceiveRepository $returnReceiveReader = null
    ) {
        $this->batches = $batches ?? new ReturnBatchWriteRepository();
        $this->batchItems = $batchItems ?? new ReturnBatchItemWriteRepository();
        $this->returnReceives = $returnReceives ?? new ReturnReceiveWriteRepository();
        $this->returnReceiveReader = $returnReceiveReader ?? new ReturnReceiveRepository();
    }

    /**
     * Confirmed (received) returns not yet attached to a batch.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listEligibleForBatch(int $limit = 50): array
    {
        return $this->returnReceiveReader->findEligibleForBatch($limit);
    }

    /**
     * Create a return batch grouping confirmed returns.
     * Mirrors dispatch DDMMYYYY reference. Does NOT create any payable deduction —
     * deduction remains a separate owner-approved action on Supplier Payables.
     *
     * @param array<string, mixed> $input
     */
    public function createBatch(array $input): WriteResult
    {
        if (!$this->batches->tableExists() || !$this->batchItems->tableExists()) {
            return WriteResult::fail('Return batch tables not available. Apply migration 0006_dispatch_returns_payables.sql manually first.');
        }

        if (empty($input['batch_confirmed'])) {
            return WriteResult::fail('Batch confirmation is required before creating a return batch.');
        }

        $returnReceiveIds = $this->normalizeIds($input['return_receive_ids'] ?? []);
        if ($returnReceiveIds === []) {
            return WriteResult::fail('Select at least one confirmed return for the batch.');
        }

        if (count($returnReceiveIds) > 50) {
            return WriteResult::fail('Maximum 50 returns per batch.');
        }

        $validated = [];
        $supplierId = null;

        foreach ($returnReceiveIds as $returnReceiveId) {
            $return = $this->returnReceives->find($returnReceiveId);
            if ($return === null) {
                return WriteResult::fail('Return #' . $returnReceiveId . ' not found.');
            }

            if (($return['status'] ?? '') !== 'received') {
                return WriteResult::fail('Return ' . (string) ($return['return_reference'] ?? ('#' . $returnReceiveId)) . ' is not in Received status (already batched or not confirmed).');
            }

            $returnSupplierId = ($return['supplier_id'] ?? null) !== null && $return['supplier_id'] !== ''
                ? (int) $return['supplier_id']
                : null;

            if ($supplierId === null) {
                $supplierId = $returnSupplierId;
            } elseif ($returnSupplierId !== $supplierId) {
                return WriteResult::fail('All selected returns must belong to the same supplier.');
            }

            $validated[] = $return;
        }

        $base = ReturnBatchReference::baseForDate();
        $reference = ReturnBatchReference::nextForToday(
            $this->batches->findReferencesLike(substr($base, 0, 9))
        );

        $totalCost = 0.0;
        foreach ($validated as $return) {
            $totalCost += (float) ($return['total_cost_snapshot'] ?? 0);
        }
        $totalCost = round($totalCost, 2);

        $pdo = Connection::pdo();
        $pdo->beginTransaction();

        try {
            $batchId = $this->batches->create([
                'return_batch_reference' => $reference,
                'supplier_id' => $supplierId,
                'total_returns' => count($validated),
                'total_adjustment_amount' => $totalCost,
                'status' => ReturnBatchReference::STATUS_DRAFT,
            ]);

            foreach ($validated as $return) {
                $returnReceiveId = (int) ($return['return_receive_id'] ?? 0);
                $costSnapshot = (float) ($return['total_cost_snapshot'] ?? 0);

                $this->batchItems->create([
                    'return_batch_id' => $batchId,
                    'return_receive_id' => $returnReceiveId,
                    'order_id' => null,
                    'manual_order_id' => null,
                    'product_id' => null,
                    'product_variant_id' => null,
                    'quantity' => (int) ($return['total_items'] ?? 0),
                    'cost_snapshot' => $costSnapshot,
                    'adjustment_amount' => $costSnapshot,
                    'status' => 'pending',
                ]);

                if (!$this->returnReceives->markBatched($returnReceiveId)) {
                    throw new \RuntimeException('Could not mark return #' . $returnReceiveId . ' as batched.');
                }
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            return WriteResult::fail('Return batch create failed: ' . $e->getMessage());
        }

        ActivityLog::record('return_batch_created', 'Return batch created — owner approval required before any deduction', [
            'return_batch_id' => $batchId,
            'return_batch_reference' => $reference,
            'total_returns' => count($validated),
            'total_adjustment_amount' => $totalCost,
            'user' => Auth::user(),
        ]);

        return WriteResult::ok('Return batch ' . $reference . ' created with ' . count($validated) . ' return(s). Owner approve it, then create a deduction draft on Supplier Payables.', $batchId);
    }

    public function approveBatch(int $batchId): WriteResult
    {
        if (!$this->batches->tableExists()) {
            return WriteResult::fail('Return batch table not available. Apply migration 0006 first.');
        }

        $batch = $this->batches->find($batchId);
        if ($batch === null) {
            return WriteResult::fail('Return batch not found.');
        }

        if (($batch['status'] ?? '') === 'owner_approved') {
            return WriteResult::fail('Return batch already approved.');
        }

        $this->batches->updateStatus($batchId, 'owner_approved');

        ActivityLog::record('return_batch_approved', 'Return batch approved by owner — deduction still requires separate payable draft', [
            'return_batch_id' => $batchId,
            'return_batch_reference' => $batch['return_batch_reference'] ?? '',
            'user' => Auth::user(),
        ]);

        return WriteResult::ok('Return batch approved. Create return deduction payable draft separately when ready.', $batchId);
    }

    public function listLatest(int $limit = 20): array
    {
        return $this->batches->listLatest($limit);
    }

    /**
     * Items (return lines) for a given batch, with return-receive header info.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listBatchItems(int $returnBatchId): array
    {
        return $this->batchItems->listForBatch($returnBatchId);
    }

    /**
     * @param mixed $ids
     * @return array<int, int>
     */
    private function normalizeIds($ids): array
    {
        if (!is_array($ids)) {
            return [];
        }

        $normalized = [];
        foreach ($ids as $id) {
            $intId = (int) $id;
            if ($intId > 0 && !in_array($intId, $normalized, true)) {
                $normalized[] = $intId;
            }
        }

        return $normalized;
    }
}
