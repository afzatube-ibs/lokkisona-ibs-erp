<?php
namespace App\Models;

class OrderItem
{
    const TABLE = 'order_items';

    public static array $columns = [
        'order_item_id',
        'order_id',
        'product_id',
        'variant_id',
        'quantity',
        'unit_price',
        'supplier_cost',
        'created_at',
        'updated_at',
    ];
}
