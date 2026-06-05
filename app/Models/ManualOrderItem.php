<?php

namespace App\Models;

class ManualOrderItem extends BaseModel
{
    const TABLE = 'manual_order_items';

    const PRIMARY_KEY = 'manual_order_item_id';

    public static array $columns = [
        'manual_order_item_id',
        'manual_order_id',
        'product_id',
        'product_variant_id',
        'product_name',
        'variant_label',
        'quantity',
        'selling_price',
        'supplier_cost_snapshot',
        'line_total',
        'created_at',
    ];
}
