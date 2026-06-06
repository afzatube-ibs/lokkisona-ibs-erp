<?php

namespace App\Models;

class SupplierQuickInvoiceItem extends BaseModel
{
    const TABLE = 'supplier_quick_invoice_items';

    const PRIMARY_KEY = 'supplier_quick_invoice_item_id';

    public static array $columns = [
        'supplier_quick_invoice_item_id',
        'supplier_quick_invoice_id',
        'item_name',
        'quantity',
        'unit_price',
        'line_total',
        'created_at',
    ];
}
