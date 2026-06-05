<?php

namespace App\Models;

class SupplierProductCost extends BaseModel
{
    const TABLE = 'supplier_product_costs';

    const PRIMARY_KEY = 'supplier_product_cost_id';

    public static array $columns = [
        'supplier_product_cost_id',
        'supplier_id',
        'product_id',
        'product_variant_id',
        'product_cost',
        'effective_from',
        'changed_by',
        'note',
        'created_at',
    ];
}
