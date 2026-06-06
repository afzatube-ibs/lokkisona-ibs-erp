<?php

namespace App\Repositories\Write;

use App\Models\Invoice;

class InvoiceWriteRepository extends BaseWriteRepository
{
    public function modelClass(): string
    {
        return Invoice::class;
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO `' . $this->escapeIdentifier($this->table()) . '` '
            . '(invoice_reference, order_id, manual_order_id, business_source_id, invoice_type, customer_name, invoice_total, invoice_status, issued_by, issued_at, created_at) '
            . 'VALUES (:invoice_reference, :order_id, :manual_order_id, :business_source_id, :invoice_type, :customer_name, :invoice_total, :invoice_status, :issued_by, NOW(), NOW())';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($data);

        return (int) $this->pdo->lastInsertId();
    }

    public function findByOrderAndType(int $orderId, string $invoiceType): ?array
    {
        if (!$this->tableExists()) {
            return null;
        }

        $sql = 'SELECT * FROM `' . $this->escapeIdentifier($this->table()) . '` '
            . 'WHERE order_id = :order_id AND invoice_type = :invoice_type ORDER BY invoice_id DESC LIMIT 1';
        $statement = $this->pdo->prepare($sql);
        $statement->execute(['order_id' => $orderId, 'invoice_type' => $invoiceType]);
        $row = $statement->fetch(\PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }

    public function find(int $id): ?array
    {
        return $this->findById($id);
    }
}
