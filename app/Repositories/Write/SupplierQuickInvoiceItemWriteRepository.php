<?php

namespace App\Repositories\Write;

use App\Models\SupplierQuickInvoiceItem;

class SupplierQuickInvoiceItemWriteRepository extends BaseWriteRepository
{
    public function modelClass(): string
    {
        return SupplierQuickInvoiceItem::class;
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO `' . $this->escapeIdentifier($this->table()) . '` '
            . '(supplier_quick_invoice_id, item_name, quantity, unit_price, line_total, created_at) '
            . 'VALUES (:supplier_quick_invoice_id, :item_name, :quantity, :unit_price, :line_total, NOW())';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($data);

        return (int) $this->pdo->lastInsertId();
    }

    public function forInvoice(int $invoiceId): array
    {
        if (!$this->tableExists()) {
            return [];
        }

        $sql = 'SELECT * FROM `' . $this->escapeIdentifier($this->table()) . '` '
            . 'WHERE supplier_quick_invoice_id = :invoice_id ORDER BY supplier_quick_invoice_item_id ASC';
        $statement = $this->pdo->prepare($sql);
        $statement->execute(['invoice_id' => $invoiceId]);

        return $statement->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }
}
