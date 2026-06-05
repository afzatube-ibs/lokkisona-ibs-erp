<?php
namespace App\Models;

class Order
{
    const TABLE = 'orders';

    public static array $columns = [
        'order_id',
        'source_id',
        'source_order_id',
        'customer_name',
        'customer_phone',
        'customer_address',
        'order_status',
        'total_amount',
        'currency_code',
        'ordered_at',
        'created_at',
        'updated_at',
    ];
}
