<?php

namespace App\Models;

class ProductStockHistory extends BaseModel
{
    const TABLE = 'product_stock_histories';

    const PRIMARY_KEY = 'product_stock_history_id';

    public static array $columns = [
        'product_stock_history_id',
        'product_id',
        'product_variant_id',
        'supplier_id',
        'old_stock',
        'new_stock',
        'change_type',
        'reference_type',
        'reference_id',
        'changed_by',
        'note',
        'created_at',
    ];
}
