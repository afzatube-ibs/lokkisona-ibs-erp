<?php

namespace App\Repositories\Write;

use App\Models\Supplier;

class SupplierWriteRepository extends BaseWriteRepository
{
    public function modelClass(): string
    {
        return Supplier::class;
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO `' . $this->escapeIdentifier($this->table()) . '` '
            . '(supplier_name, contact_person, phone, email, address, payment_terms, payable_balance, status, linked_business_source_id, created_at) '
            . 'VALUES (:supplier_name, :contact_person, :phone, :email, :address, :payment_terms, 0.00, :status, :linked_business_source_id, NOW())';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($data);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $sql = 'UPDATE `' . $this->escapeIdentifier($this->table()) . '` SET '
            . 'supplier_name = :supplier_name, contact_person = :contact_person, phone = :phone, email = :email, '
            . 'address = :address, payment_terms = :payment_terms, status = :status, '
            . 'linked_business_source_id = :linked_business_source_id, updated_at = NOW() '
            . 'WHERE supplier_id = :id';
        $data['id'] = $id;
        $statement = $this->pdo->prepare($sql);

        return $statement->execute($data);
    }

    public function find(int $id): ?array
    {
        return $this->findById($id);
    }
}
