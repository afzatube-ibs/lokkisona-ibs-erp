<?php

namespace App\Repositories\Write;

use App\Models\ManualOrder;

class ManualOrderWriteRepository extends BaseWriteRepository
{
    public function modelClass(): string
    {
        return ManualOrder::class;
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO `' . $this->escapeIdentifier($this->table()) . '` '
            . '(business_source_id, supplier_id, manual_order_reference, external_order_reference, external_invoice_reference, '
            . 'customer_name, customer_phone, customer_address, order_total, ibs_status, entry_status, created_at) '
            . 'VALUES (:business_source_id, :supplier_id, :manual_order_reference, :external_order_reference, :external_invoice_reference, '
            . ':customer_name, :customer_phone, :customer_address, :order_total, :ibs_status, :entry_status, NOW())';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($data);

        return (int) $this->pdo->lastInsertId();
    }

    public function findByExternalReference(string $ref): ?array
    {
        if (!$this->tableExists() || $ref === '') {
            return null;
        }

        $sql = 'SELECT * FROM `' . $this->escapeIdentifier($this->table()) . '` WHERE external_order_reference = :ref LIMIT 1';
        $statement = $this->pdo->prepare($sql);
        $statement->execute(['ref' => $ref]);
        $row = $statement->fetch(\PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }
}
