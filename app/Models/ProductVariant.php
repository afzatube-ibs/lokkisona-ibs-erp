<?php
namespace App\Models;

class ProductVariant
{
    const TABLE = 'product_variants';

    public static array $columns = [
        'variant_id',
        'product_id',
        'variant_name',
        'sku',
        'is_active',
        'created_at',
        'updated_at',
    ];
}
