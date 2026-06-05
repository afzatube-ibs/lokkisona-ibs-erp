<?php
namespace App\Models;

class Product
{
    const TABLE = 'products';

    public static array $columns = [
        'product_id',
        'product_name',
        'product_code',
        'description',
        'is_active',
        'created_at',
        'updated_at',
    ];
}
