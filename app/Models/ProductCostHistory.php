<?php

namespace App\Models;

class ProductCostHistory extends BaseModel
{
    const TABLE = 'product_cost_histories';

    const PRIMARY_KEY = 'product_cost_history_id';

    public static array $columns = [
        'product_cost_history_id',
        'product_id',
        'product_variant_id',
        'supplier_id',
        'old_cost',
        'new_cost',
        'changed_by',
        'note',
        'created_at',
    ];
}
