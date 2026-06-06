<?php

namespace App\Models;

class InvoiceItem extends BaseModel
{
    const TABLE = 'invoice_items';

    const PRIMARY_KEY = 'invoice_item_id';

    public static array $columns = [
        'invoice_item_id',
        'invoice_id',
        'product_id',
        'product_variant_id',
        'product_name',
        'variant_label',
        'quantity',
        'unit_price',
        'line_total',
        'created_at',
    ];
}
