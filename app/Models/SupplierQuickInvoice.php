<?php

namespace App\Models;

class SupplierQuickInvoice extends BaseModel
{
    const TABLE = 'supplier_quick_invoices';

    const PRIMARY_KEY = 'supplier_quick_invoice_id';

    public static array $columns = [
        'supplier_quick_invoice_id',
        'supplier_id',
        'quick_invoice_reference',
        'supplier_name',
        'customer_name',
        'customer_phone',
        'customer_address',
        'invoice_total',
        'subtotal',
        'discount_amount',
        'advance_amount',
        'balance_due',
        'notes',
        'output_status',
        'created_by',
        'generated_at',
        'downloaded_at',
        'supplier_access_closed_at',
        'created_at',
        'updated_at',
    ];
}
