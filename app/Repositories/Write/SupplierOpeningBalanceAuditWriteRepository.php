<?php

namespace App\Repositories\Write;

use App\Models\SupplierOpeningBalanceAudit;

class SupplierOpeningBalanceAuditWriteRepository extends BaseWriteRepository
{
    public function modelClass(): string
    {
        return SupplierOpeningBalanceAudit::class;
    }

    public function insert(int $balanceId, string $action, ?string $notes = null): int
    {
        $sql = 'INSERT INTO `' . $this->escapeIdentifier($this->table()) . '` '
            . '(supplier_opening_balance_id, action, changed_at, notes, created_at) '
            . 'VALUES (:balance_id, :action, NOW(), :notes, NOW())';
        $statement = $this->pdo->prepare($sql);
        $statement->execute([
            'balance_id' => $balanceId,
            'action' => $action,
            'notes' => $notes,
        ]);

        return (int) $this->pdo->lastInsertId();
    }
}
