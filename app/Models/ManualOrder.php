<?php

namespace App\Models;

class ManualOrder extends BaseModel
{
    const TABLE = 'manual_orders';

    const PRIMARY_KEY = 'manual_order_id';

    public static array $columns = [
        'manual_order_id',
        'business_source_id',
        'supplier_id',
        'manual_order_reference',
        'external_order_reference',
        'external_invoice_reference',
        'customer_name',
        'customer_phone',
        'customer_address',
        'order_total',
        'ibs_status',
        'entry_status',
        'created_by',
        'confirmed_by',
        'confirmed_at',
        'created_at',
        'updated_at',
    ];
}
