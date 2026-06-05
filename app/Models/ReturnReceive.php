<?php

namespace App\Models;

class ReturnReceive extends BaseModel
{
    const TABLE = 'return_receives';

    const PRIMARY_KEY = 'return_receive_id';

    public static array $columns = [
        'return_receive_id',
        'return_reference',
        'supplier_id',
        'business_source_id',
        'return_type',
        'total_items',
        'total_cost_snapshot',
        'status',
        'received_by',
        'received_at',
        'created_at',
        'updated_at',
    ];
}
