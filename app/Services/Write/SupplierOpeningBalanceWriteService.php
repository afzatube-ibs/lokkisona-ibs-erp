<?php

namespace App\Services\Write;

use App\ActivityLog;
use App\Repositories\Write\PayableLedgerWriteRepository;
use App\Repositories\Write\SupplierOpeningBalanceAuditWriteRepository;
use App\Repositories\Write\SupplierOpeningBalanceWriteRepository;

class SupplierOpeningBalanceWriteService
{
    private SupplierOpeningBalanceWriteRepository $balances;
    private SupplierOpeningBalanceAuditWriteRepository $audits;
    private PayableLedgerWriteRepository $ledgers;

    public function __construct(
        ?SupplierOpeningBalanceWriteRepository $balances = null,
        ?SupplierOpeningBalanceAuditWriteRepository $audits = null,
        ?PayableLedgerWriteRepository $ledgers = null
    ) {
        $this->balances = $balances ?? new SupplierOpeningBalanceWriteRepository();
        $this->audits = $audits ?? new SupplierOpeningBalanceAuditWriteRepository();
        $this->ledgers = $ledgers ?? new PayableLedgerWriteRepository();
    }

    public function create(array $input): WriteResult
    {
        if (!$this->balances->tableExists()) {
            return WriteResult::fail('Opening balance table not available. Apply migration 0008 first.');
        }

        $supplierId = (int) ($input['supplier_id'] ?? 0);
        if ($supplierId <= 0) {
            return WriteResult::fail('Supplier is required.');
        }

        $amount = round((float) ($input['amount'] ?? 0), 2);
        $balanceType = trim((string) ($input['balance_type'] ?? 'payable_to_supplier'));
        if (!in_array($balanceType, ['payable_to_supplier', 'advance_from_supplier', 'neutral_zero_start'], true)) {
            $balanceType = 'payable_to_supplier';
        }

        $id = $this->balances->create([
            'supplier_id' => $supplierId,
            'business_source_id' => ($input['business_source_id'] ?? '') !== '' ? (int) $input['business_source_id'] : null,
            'applies_to_all_sources' => !empty($input['applies_to_all_sources']) ? 1 : 0,
            'balance_type' => $balanceType,
            'amount' => $amount,
            'cutoff_date' => ($input['cutoff_date'] ?? '') !== '' ? $input['cutoff_date'] : null,
            'reference_note' => trim((string) ($input['reference_note'] ?? '')) ?: null,
            'status' => 'draft',
            'owner_approval_status' => 'pending',
        ]);

        if ($this->audits->tableExists()) {
            $this->audits->insert($id, 'created', 'Opening balance draft created');
        }

        ActivityLog::record('opening_balance_created', 'Opening balance draft created', ['supplier_opening_balance_id' => $id]);

        return WriteResult::ok('Opening balance draft created. Awaiting owner approval.', $id);
    }

    public function approve(int $id): WriteResult
    {
        if (!$this->balances->tableExists()) {
            return WriteResult::fail('Opening balance table not available.');
        }

        $balance = $this->balances->find($id);
        if ($balance === null) {
            return WriteResult::fail('Opening balance not found.');
        }

        if (($balance['owner_approval_status'] ?? '') === 'approved') {
            return WriteResult::fail('Opening balance already approved.');
        }

        $this->balances->approve($id);

        if ($this->audits->tableExists()) {
            $this->audits->insert($id, 'approved', 'Owner approved opening balance');
        }

        if ($this->ledgers->tableExists()) {
            $supplierId = (int) $balance['supplier_id'];
            $amount = (float) $balance['amount'];
            $type = $balance['balance_type'] ?? 'payable_to_supplier';
            $ref = 'OB-' . $id;
            $debit = $type === 'payable_to_supplier' ? $amount : 0.0;
            $credit = $type === 'advance_from_supplier' ? $amount : 0.0;
            if ($type !== 'neutral_zero_start' && $amount > 0) {
                $this->ledgers->createOpeningEntry($supplierId, $ref, $debit, $credit);
            }
        }

        ActivityLog::record('opening_balance_approved', 'Opening balance approved', ['supplier_opening_balance_id' => $id]);

        return WriteResult::ok('Opening balance approved. Payable ledger entry created when applicable.', $id);
    }
}
