<?php

namespace App\Services\Write;

use App\ActivityLog;
use App\Auth;
use App\Database\Connection;
use App\Domain\PayableLedgerType;
use App\Repositories\UserRepository;
use App\Repositories\Write\DispatchReportWriteRepository;
use App\Repositories\Write\PayableLedgerWriteRepository;
use App\Repositories\Write\ReturnBatchWriteRepository;
use App\Repositories\Write\ReturnReceiveWriteRepository;

class PayableLedgerWriteService
{
    private PayableLedgerWriteRepository $ledgers;
    private DispatchReportWriteRepository $dispatchReports;
    private ReturnReceiveWriteRepository $returnReceives;
    private ReturnBatchWriteRepository $returnBatches;
    private UserRepository $users;

    public function __construct(
        ?PayableLedgerWriteRepository $ledgers = null,
        ?DispatchReportWriteRepository $dispatchReports = null,
        ?ReturnReceiveWriteRepository $returnReceives = null,
        ?ReturnBatchWriteRepository $returnBatches = null,
        ?UserRepository $users = null
    ) {
        $this->ledgers = $ledgers ?? new PayableLedgerWriteRepository();
        $this->dispatchReports = $dispatchReports ?? new DispatchReportWriteRepository();
        $this->returnReceives = $returnReceives ?? new ReturnReceiveWriteRepository();
        $this->returnBatches = $returnBatches ?? new ReturnBatchWriteRepository();
        $this->users = $users ?? new UserRepository();
    }

    public function createDraftFromDispatch(int $dispatchReportId): WriteResult
    {
        if (!$this->ledgers->tableExists()) {
            return WriteResult::fail('Payable ledger table not available. Apply migration 0006 first.');
        }

        if (!$this->dispatchReports->tableExists()) {
            return WriteResult::fail('Dispatch report table not available.');
        }

        $report = $this->dispatchReports->find($dispatchReportId);
        if ($report === null) {
            return WriteResult::fail('Dispatch report not found.');
        }

        $dispatchReference = (string) ($report['dispatch_reference'] ?? '');
        $existing = $this->ledgers->findBySourceAndType($dispatchReference, PayableLedgerType::PRODUCT_COST_PAYABLE);
        if ($existing !== null) {
            return WriteResult::ok('Payable draft already exists for dispatch ' . $dispatchReference, (int) $existing['payable_ledger_id']);
        }

        $supplierId = (int) ($report['supplier_id'] ?? 0);
        if ($supplierId <= 0) {
            return WriteResult::fail('Dispatch report has no supplier. Cannot create payable entry.');
        }

        $totalCost = round((float) ($report['total_product_cost'] ?? 0), 2);
        if ($totalCost <= 0) {
            return WriteResult::fail('Dispatch report has zero product cost. Cannot create payable entry.');
        }

        $ledgerReference = 'PCP-' . $dispatchReference;
        $id = $this->ledgers->createEntry([
            'supplier_id' => $supplierId,
            'ledger_reference' => $ledgerReference,
            'ledger_type' => PayableLedgerType::PRODUCT_COST_PAYABLE,
            'source_reference' => $dispatchReference,
            'debit_amount' => $totalCost,
            'credit_amount' => 0.0,
            'balance_after' => null,
            'status' => 'draft',
            'created_by' => $this->resolveCreatedById(),
        ]);

        ActivityLog::record('payable_ledger_draft_created', 'Product Cost Payable draft from dispatch snapshot', [
            'payable_ledger_id' => $id,
            'dispatch_reference' => $dispatchReference,
            'amount' => $totalCost,
        ]);

        return WriteResult::ok('Product Cost Payable draft created from dispatch ' . $dispatchReference . '. Awaiting owner approval.', $id);
    }

    /**
     * Create a single Return / Damage Deduction draft from an owner-approved return batch.
     * Gated: batch must be owner_approved. Never auto-runs — owner triggers this explicitly,
     * and the resulting draft still requires separate owner approval before it posts to balance.
     */
    public function createDraftFromReturnBatch(int $returnBatchId): WriteResult
    {
        if (!$this->ledgers->tableExists()) {
            return WriteResult::fail('Payable ledger table not available. Apply migration 0006 first.');
        }

        if (!$this->returnBatches->tableExists()) {
            return WriteResult::fail('Return batch table not available.');
        }

        $batch = $this->returnBatches->find($returnBatchId);
        if ($batch === null) {
            return WriteResult::fail('Return batch not found.');
        }

        if (($batch['status'] ?? '') !== 'owner_approved') {
            return WriteResult::fail('Return batch must be owner-approved before a deduction draft can be created.');
        }

        $supplierId = (int) ($batch['supplier_id'] ?? 0);
        if ($supplierId <= 0) {
            return WriteResult::fail('Return batch has no supplier. Cannot create deduction entry.');
        }

        $amount = round((float) ($batch['total_adjustment_amount'] ?? 0), 2);
        if ($amount <= 0) {
            return WriteResult::fail('Return batch has zero adjustment amount. Cannot create deduction entry.');
        }

        $batchReference = (string) ($batch['return_batch_reference'] ?? '');
        $existing = $this->ledgers->findBySourceAndType($batchReference, PayableLedgerType::RETURN_DEDUCTION);
        if ($existing !== null) {
            return WriteResult::ok('Return deduction draft already exists for batch ' . $batchReference, (int) $existing['payable_ledger_id']);
        }

        $ledgerReference = 'RDB-' . $batchReference;
        $id = $this->ledgers->createEntry([
            'supplier_id' => $supplierId,
            'ledger_reference' => $ledgerReference,
            'ledger_type' => PayableLedgerType::RETURN_DEDUCTION,
            'source_reference' => $batchReference,
            'debit_amount' => 0.0,
            'credit_amount' => $amount,
            'balance_after' => null,
            'status' => 'draft',
            'created_by' => $this->resolveCreatedById(),
        ]);

        ActivityLog::record('payable_return_batch_deduction_draft', 'Return / Damage Deduction draft created from owner-approved return batch', [
            'payable_ledger_id' => $id,
            'return_batch_id' => $returnBatchId,
            'return_batch_reference' => $batchReference,
            'amount' => $amount,
        ]);

        return WriteResult::ok('Return / Damage Deduction draft created from batch ' . $batchReference . '. Awaiting owner approval on Supplier Payables.', $id);
    }

    public function createManualEntry(array $input): WriteResult
    {
        if (!$this->ledgers->tableExists()) {
            return WriteResult::fail('Payable ledger table not available. Apply migration 0006 first.');
        }

        $supplierId = (int) ($input['supplier_id'] ?? 0);
        if ($supplierId <= 0) {
            return WriteResult::fail('Supplier is required.');
        }

        $ledgerType = trim((string) ($input['ledger_type'] ?? ''));
        if (!in_array($ledgerType, PayableLedgerType::manualEntryTypes(), true)) {
            return WriteResult::fail('Invalid ledger entry type.');
        }

        $amount = round((float) ($input['amount'] ?? 0), 2);
        if ($amount <= 0) {
            return WriteResult::fail('Amount must be greater than zero.');
        }

        $note = trim((string) ($input['note'] ?? ''));
        $sourceReference = trim((string) ($input['source_reference'] ?? ''));

        if ($ledgerType === PayableLedgerType::RETURN_DEDUCTION) {
            return $this->createReturnDeductionDraft($supplierId, $amount, $input, $note);
        }

        if ($sourceReference === '') {
            $sourceReference = strtoupper(substr($ledgerType, 0, 3)) . '-' . date('YmdHis');
        }

        $debit = PayableLedgerType::isDebitType($ledgerType) ? $amount : 0.0;
        $credit = PayableLedgerType::isCreditType($ledgerType) ? $amount : 0.0;

        $ledgerReference = $this->uniqueLedgerReference($sourceReference, $ledgerType);
        $id = $this->ledgers->createEntry([
            'supplier_id' => $supplierId,
            'ledger_reference' => $ledgerReference,
            'ledger_type' => $ledgerType,
            'source_reference' => $sourceReference,
            'debit_amount' => $debit,
            'credit_amount' => $credit,
            'balance_after' => null,
            'status' => 'draft',
            'created_by' => $this->resolveCreatedById(),
        ]);

        ActivityLog::record('payable_ledger_manual_draft', PayableLedgerType::label($ledgerType) . ' draft created', [
            'payable_ledger_id' => $id,
            'ledger_type' => $ledgerType,
            'amount' => $amount,
            'note' => $note !== '' ? $note : null,
        ]);

        return WriteResult::ok(PayableLedgerType::label($ledgerType) . ' draft created. Awaiting owner approval.', $id);
    }

    public function approve(int $ledgerId): WriteResult
    {
        if (!$this->ledgers->tableExists()) {
            return WriteResult::fail('Payable ledger table not available.');
        }

        $entry = $this->ledgers->find($ledgerId);
        if ($entry === null) {
            return WriteResult::fail('Ledger entry not found.');
        }

        if (($entry['status'] ?? '') === 'posted') {
            return WriteResult::fail('Ledger entry is already posted.');
        }

        if (($entry['status'] ?? '') === 'rejected') {
            return WriteResult::fail('Rejected entries cannot be approved.');
        }

        $supplierId = (int) ($entry['supplier_id'] ?? 0);
        $previousBalance = $this->ledgers->getPostedBalanceForSupplier($supplierId);
        $debit = round((float) ($entry['debit_amount'] ?? 0), 2);
        $credit = round((float) ($entry['credit_amount'] ?? 0), 2);
        $newBalance = round($previousBalance + $debit - $credit, 2);

        $pdo = Connection::pdo();
        $pdo->beginTransaction();
        try {
            $this->ledgers->updateStatus($ledgerId, 'posted', $newBalance);
            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            return WriteResult::fail('Approval failed: ' . $e->getMessage());
        }

        ActivityLog::record('payable_ledger_approved', 'Payable ledger entry posted', [
            'payable_ledger_id' => $ledgerId,
            'ledger_type' => $entry['ledger_type'] ?? '',
            'balance_after' => $newBalance,
            'user' => Auth::user(),
        ]);

        return WriteResult::ok('Ledger entry posted. Running balance: ' . number_format($newBalance, 2) . ' BDT.', $ledgerId);
    }

    public function reject(int $ledgerId): WriteResult
    {
        if (!$this->ledgers->tableExists()) {
            return WriteResult::fail('Payable ledger table not available.');
        }

        $entry = $this->ledgers->find($ledgerId);
        if ($entry === null) {
            return WriteResult::fail('Ledger entry not found.');
        }

        if (($entry['status'] ?? '') !== 'draft') {
            return WriteResult::fail('Only draft entries can be rejected.');
        }

        $this->ledgers->updateStatus($ledgerId, 'rejected');

        ActivityLog::record('payable_ledger_rejected', 'Payable ledger draft rejected', [
            'payable_ledger_id' => $ledgerId,
            'user' => Auth::user(),
        ]);

        return WriteResult::ok('Ledger draft rejected.', $ledgerId);
    }

    public function payableStatusForDispatch(string $dispatchReference): ?array
    {
        if ($dispatchReference === '') {
            return null;
        }

        return $this->ledgers->findBySourceAndType($dispatchReference, PayableLedgerType::PRODUCT_COST_PAYABLE);
    }

    private function createReturnDeductionDraft(int $supplierId, float $amount, array $input, string $note): WriteResult
    {
        $returnReceiveId = (int) ($input['return_receive_id'] ?? 0);
        if ($returnReceiveId <= 0) {
            return WriteResult::fail('Return receive record is required for return deduction.');
        }

        if (!$this->returnReceives->tableExists()) {
            return WriteResult::fail('Return receive table not available.');
        }

        $returnReceive = $this->returnReceives->find($returnReceiveId);
        if ($returnReceive === null) {
            return WriteResult::fail('Return receive record not found.');
        }

        $returnType = (string) ($returnReceive['return_type'] ?? '');
        if (!in_array($returnType, ['hub_courier_return', 'customer_return_to_supplier'], true)) {
            return WriteResult::fail('Return deduction applies only to supplier-side returns (Hub Return or Customer Return to Supplier).');
        }

        if ((int) ($returnReceive['supplier_id'] ?? 0) !== $supplierId) {
            return WriteResult::fail('Return receive does not belong to the selected supplier.');
        }

        if (($returnReceive['status'] ?? '') !== 'received') {
            return WriteResult::fail('Return must be confirmed received before a deduction draft can be created.');
        }

        $returnReference = (string) ($returnReceive['return_reference'] ?? '');
        $existing = $this->ledgers->findBySourceAndType($returnReference, PayableLedgerType::RETURN_DEDUCTION);
        if ($existing !== null) {
            return WriteResult::fail('A return deduction draft already exists for ' . $returnReference . '.');
        }

        $ledgerReference = 'RD-' . $returnReference;
        $id = $this->ledgers->createEntry([
            'supplier_id' => $supplierId,
            'ledger_reference' => $ledgerReference,
            'ledger_type' => PayableLedgerType::RETURN_DEDUCTION,
            'source_reference' => $returnReference,
            'debit_amount' => 0.0,
            'credit_amount' => $amount,
            'balance_after' => null,
            'status' => 'draft',
            'created_by' => $this->resolveCreatedById(),
        ]);

        ActivityLog::record('payable_return_deduction_draft', 'Return deduction draft created after receive confirmation', [
            'payable_ledger_id' => $id,
            'return_reference' => $returnReference,
            'amount' => $amount,
            'note' => $note !== '' ? $note : null,
        ]);

        return WriteResult::ok('Return deduction draft created for ' . $returnReference . '. Awaiting owner approval.', $id);
    }

    private function uniqueLedgerReference(string $base, string $type): string
    {
        $prefix = match ($type) {
            PayableLedgerType::SUPPLIER_INVOICE => 'SINV',
            PayableLedgerType::ADDITIONAL_PAYABLE => 'AP',
            PayableLedgerType::PAYMENT_MADE => 'PAY',
            PayableLedgerType::ADVANCE_RECEIVED => 'ADV',
            PayableLedgerType::DEBIT_ADJUSTMENT => 'DADJ',
            PayableLedgerType::CREDIT_ADJUSTMENT => 'CADJ',
            default => 'LED',
        };

        $candidate = $prefix . '-' . preg_replace('/[^A-Za-z0-9\-]/', '', $base);
        if ($this->ledgers->findByLedgerReference($candidate) === null) {
            return $candidate;
        }

        return $candidate . '-' . time();
    }

    private function resolveCreatedById(): ?int
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
