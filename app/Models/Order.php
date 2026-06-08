<?php

namespace App\Models;

class Order extends BaseModel
{
    const TABLE = 'orders';

    const PRIMARY_KEY = 'order_id';

    public static array $columns = [
        'order_id',
        'business_source_id',
        'supplier_id',
        'source_order_id',
        'source_order_reference',
        'origin_order_status_id',
        'origin_order_status_name',
        'sync_source',
        'imported_at',
        'last_synced_at',
        'source_invoice_reference',
        'order_reference',
        'customer_name',
        'customer_phone',
        'customer_address',
        'payment_method',
        'order_total',
        'ibs_status',
        'courier_name',
        'tracking_number',
        'courier_status',
        'cost_snapshot_total',
        'status',
        'ordered_at',
        'created_at',
        'updated_at',
    ];
}
