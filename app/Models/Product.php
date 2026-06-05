<?php

namespace App\Models;

class Product extends BaseModel
{
    const TABLE = 'products';

    const PRIMARY_KEY = 'product_id';

    public static array $columns = [
        'product_id',
        'source_product_id',
        'product_name',
        'image_path',
        'business_source_id',
        'supplier_id',
        'source_model',
        'source_stock',
        'supplier_model',
        'product_cost',
        'vendor_stock',
        'low_warning_threshold',
        'status',
        'last_synced_at',
        'created_at',
        'updated_at',
    ];
}
