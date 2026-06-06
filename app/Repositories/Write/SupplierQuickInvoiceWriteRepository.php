<?php

namespace App\Repositories\Write;

use App\Models\SupplierQuickInvoice;

class SupplierQuickInvoiceWriteRepository extends BaseWriteRepository
{
    public function modelClass(): string
    {
        return SupplierQuickInvoice::class;
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO `' . $this->escapeIdentifier($this->table()) . '` '
            . '(supplier_id, quick_invoice_reference, supplier_name, customer_name, customer_phone, customer_address, '
            . 'invoice_total, subtotal, discount_amount, advance_amount, balance_due, notes, output_status, created_by, generated_at, created_at) '
            . 'VALUES (:supplier_id, :quick_invoice_reference, :supplier_name, :customer_name, :customer_phone, :customer_address, '
            . ':invoice_total, :subtotal, :discount_amount, :advance_amount, :balance_due, :notes, :output_status, :created_by, NOW(), NOW())';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($data);

        return (int) $this->pdo->lastInsertId();
    }

    public function find(int $id): ?array
    {
        return $this->findById($id);
    }

    public function markDownloaded(int $id): bool
    {
        if (!$this->tableExists()) {
            return false;
        }

        $sql = 'UPDATE `' . $this->escapeIdentifier($this->table()) . '` SET downloaded_at = NOW(), '
            . 'supplier_access_closed_at = NOW(), output_status = :status, updated_at = NOW() '
            . 'WHERE supplier_quick_invoice_id = :id LIMIT 1';
        $statement = $this->pdo->prepare($sql);

        return $statement->execute(['status' => 'downloaded', 'id' => $id]);
    }

    public function listRecent(int $limit = 50): array
    {
        if (!$this->tableExists()) {
            return [];
        }

        $limit = max(1, min($limit, 100));
        $sql = 'SELECT * FROM `' . $this->escapeIdentifier($this->table()) . '` ORDER BY supplier_quick_invoice_id DESC LIMIT ' . $limit;
        $statement = $this->pdo->query($sql);

        return $statement ? ($statement->fetchAll(\PDO::FETCH_ASSOC) ?: []) : [];
    }

    public function hasExtendedColumns(): bool
    {
        if (!$this->tableExists()) {
            return false;
        }

        $database = config('database.database', '');
        $table = $this->table();
        $sql = 'SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = :table AND COLUMN_NAME = :column';
        $statement = $this->pdo->prepare($sql);
        $statement->execute(['schema' => $database, 'table' => $table, 'column' => 'customer_name']);
        $row = $statement->fetch(\PDO::FETCH_ASSOC);

        return ((int) ($row['c'] ?? 0)) > 0;
    }
}
