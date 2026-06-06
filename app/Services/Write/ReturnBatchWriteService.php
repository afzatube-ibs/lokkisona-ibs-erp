<?php

namespace App\Services\Write;

use App\ActivityLog;
use App\Auth;
use App\Repositories\Write\ReturnBatchWriteRepository;

class ReturnBatchWriteService
{
    private ReturnBatchWriteRepository $batches;

    public function __construct(?ReturnBatchWriteRepository $batches = null)
    {
        $this->batches = $batches ?? new ReturnBatchWriteRepository();
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
}
