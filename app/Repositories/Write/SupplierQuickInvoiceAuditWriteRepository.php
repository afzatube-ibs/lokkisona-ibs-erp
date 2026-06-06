<?php

namespace App\Repositories\Write;

use App\Models\SupplierQuickInvoiceAudit;

class SupplierQuickInvoiceAuditWriteRepository extends BaseWriteRepository
{
    public function modelClass(): string
    {
        return SupplierQuickInvoiceAudit::class;
    }

    public function append(int $invoiceId, string $action, ?string $message = null, ?array $context = null): int
    {
        $sql = 'INSERT INTO `' . $this->escapeIdentifier($this->table()) . '` '
            . '(supplier_quick_invoice_id, action, user_id, message, context_json, created_at) '
            . 'VALUES (:supplier_quick_invoice_id, :action, :user_id, :message, :context_json, NOW())';
        $statement = $this->pdo->prepare($sql);
        $statement->execute([
            'supplier_quick_invoice_id' => $invoiceId,
            'action' => $action,
            'user_id' => null,
            'message' => $message,
            'context_json' => $context !== null ? json_encode($context, JSON_UNESCAPED_UNICODE) : null,
        ]);

        return (int) $this->pdo->lastInsertId();
    }
}
