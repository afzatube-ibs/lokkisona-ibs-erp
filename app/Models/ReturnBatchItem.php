<?php

namespace App\Models;

class ReturnBatchItem extends BaseModel
{
    const TABLE = 'return_batch_items';

    const PRIMARY_KEY = 'return_batch_item_id';

    public static array $columns = [
        'return_batch_item_id',
        'return_batch_id',
        'return_receive_id',
        'order_id',
        'manual_order_id',
        'product_id',
        'product_variant_id',
        'quantity',
        'cost_snapshot',
        'adjustment_amount',
        'status',
        'created_at',
    ];
}
