<?php

namespace App\Repositories\Write;

use App\Models\InvoiceItem;

class InvoiceItemWriteRepository extends BaseWriteRepository
{
    public function modelClass(): string
    {
        return InvoiceItem::class;
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO `' . $this->escapeIdentifier($this->table()) . '` '
            . '(invoice_id, product_id, product_variant_id, product_name, variant_label, quantity, unit_price, line_total, created_at) '
            . 'VALUES (:invoice_id, :product_id, :product_variant_id, :product_name, :variant_label, :quantity, :unit_price, :line_total, NOW())';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($data);

        return (int) $this->pdo->lastInsertId();
    }

    public function forInvoice(int $invoiceId): array
    {
        if (!$this->tableExists()) {
            return [];
        }

        $sql = 'SELECT * FROM `' . $this->escapeIdentifier($this->table()) . '` WHERE invoice_id = :invoice_id ORDER BY invoice_item_id ASC';
        $statement = $this->pdo->prepare($sql);
        $statement->execute(['invoice_id' => $invoiceId]);

        return $statement->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }
}
