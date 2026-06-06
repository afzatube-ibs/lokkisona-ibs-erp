<?php

namespace App\Models;

class ReturnBatch extends BaseModel
{
    const TABLE = 'return_batches';

    const PRIMARY_KEY = 'return_batch_id';

    public static array $columns = [
        'return_batch_id',
        'return_batch_reference',
        'supplier_id',
        'total_returns',
        'total_adjustment_amount',
        'status',
        'reviewed_by',
        'reviewed_at',
        'created_at',
        'updated_at',
    ];
}
