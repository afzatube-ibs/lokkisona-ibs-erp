<?php

namespace App\Models;

class OrderItem extends BaseModel
{
    const TABLE = 'order_items';

    const PRIMARY_KEY = 'order_item_id';

    public static array $columns = [
        'order_item_id',
        'order_id',
        'product_id',
        'product_variant_id',
        'source_product_id',
        'source_line_key',
        'product_name',
        'variant_label',
        'quantity',
        'selling_price',
        'supplier_cost_snapshot',
        'line_total',
        'created_at',
    ];
}
