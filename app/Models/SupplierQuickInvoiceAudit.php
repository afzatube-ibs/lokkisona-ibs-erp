<?php

namespace App\Models;

class SupplierQuickInvoiceAudit extends BaseModel
{
    const TABLE = 'supplier_quick_invoice_audits';

    const PRIMARY_KEY = 'supplier_quick_invoice_audit_id';

    public static array $columns = [
        'supplier_quick_invoice_audit_id',
        'supplier_quick_invoice_id',
        'action',
        'user_id',
        'message',
        'context_json',
        'created_at',
    ];
}
