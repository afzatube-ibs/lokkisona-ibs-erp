<?php

namespace App\Repositories\Write;

use App\Models\SupplierOpeningBalance;

class SupplierOpeningBalanceWriteRepository extends BaseWriteRepository
{
    public function modelClass(): string
    {
        return SupplierOpeningBalance::class;
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO `' . $this->escapeIdentifier($this->table()) . '` '
            . '(supplier_id, business_source_id, applies_to_all_sources, balance_type, amount, cutoff_date, reference_note, status, owner_approval_status, entered_at, created_at) '
            . 'VALUES (:supplier_id, :business_source_id, :applies_to_all_sources, :balance_type, :amount, :cutoff_date, :reference_note, :status, :owner_approval_status, NOW(), NOW())';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($data);

        return (int) $this->pdo->lastInsertId();
    }

    public function approve(int $id): bool
    {
        $sql = 'UPDATE `' . $this->escapeIdentifier($this->table()) . '` SET owner_approval_status = :approval, status = :status, owner_approved_at = NOW(), updated_at = NOW() WHERE supplier_opening_balance_id = :id';
        $statement = $this->pdo->prepare($sql);

        return $statement->execute(['approval' => 'approved', 'status' => 'approved', 'id' => $id]);
    }

    public function find(int $id): ?array
    {
        return $this->findById($id);
    }
}
