<?php

namespace App\Repositories\Write;

use App\Models\PayableLedger;

class PayableLedgerWriteRepository extends BaseWriteRepository
{
    public function modelClass(): string
    {
        return PayableLedger::class;
    }

    public function createOpeningEntry(int $supplierId, string $reference, float $debit, float $credit): int
    {
        $sql = 'INSERT INTO `' . $this->escapeIdentifier($this->table()) . '` '
            . '(supplier_id, ledger_reference, ledger_type, source_reference, debit_amount, credit_amount, balance_after, status, created_at) '
            . 'VALUES (:supplier_id, :ledger_reference, :ledger_type, :source_reference, :debit_amount, :credit_amount, :balance_after, :status, NOW())';
        $balance = $debit - $credit;
        $statement = $this->pdo->prepare($sql);
        $statement->execute([
            'supplier_id' => $supplierId,
            'ledger_reference' => $reference,
            'ledger_type' => 'opening_balance',
            'source_reference' => $reference,
            'debit_amount' => $debit,
            'credit_amount' => $credit,
            'balance_after' => $balance,
            'status' => 'posted',
        ]);

        return (int) $this->pdo->lastInsertId();
    }
}
