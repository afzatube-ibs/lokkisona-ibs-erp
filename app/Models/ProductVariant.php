<?php

namespace App\Models;

class ProductVariant extends BaseModel
{
    const TABLE = 'product_variants';

    const PRIMARY_KEY = 'product_variant_id';

    public static array $columns = [
        'product_variant_id',
        'product_id',
        'option_name',
        'option_value',
        'source_option_id',
        'source_option_value_id',
        'source_model',
        'source_stock',
        'supplier_model',
        'product_cost',
        'vendor_stock',
        'option_image_path',
        'image_reference_note',
        'status',
        'created_at',
        'updated_at',
    ];
}
