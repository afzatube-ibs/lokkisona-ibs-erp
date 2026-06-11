<?php

namespace App\Models;

class ReturnReport extends BaseModel
{
    const TABLE = 'return_reports';

    const PRIMARY_KEY = 'return_report_id';

    public static array $columns = [
        'return_report_id',
        'return_report_reference',
        'supplier_id',
        'business_source_id',
        'return_date',
        'total_returns',
        'total_quantity',
        'total_adjustment_amount',
        'status',
        'locked_by',
        'locked_at',
        'created_by',
        'created_at',
        'updated_at',
    ];
}
