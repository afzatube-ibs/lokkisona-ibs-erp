<?php

namespace App\Models;

class Invoice extends BaseModel
{
    const TABLE = 'invoices';

    const PRIMARY_KEY = 'invoice_id';

    public static array $columns = [
        'invoice_id',
        'invoice_reference',
        'order_id',
        'manual_order_id',
        'business_source_id',
        'invoice_type',
        'customer_name',
        'invoice_total',
        'invoice_status',
        'issued_by',
        'issued_at',
        'created_at',
        'updated_at',
    ];
}
