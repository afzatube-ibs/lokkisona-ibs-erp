<?php

namespace App\Models;

class DispatchReport extends BaseModel
{
    const TABLE = 'dispatch_reports';

    const PRIMARY_KEY = 'dispatch_report_id';

    public static array $columns = [
        'dispatch_report_id',
        'dispatch_reference',
        'supplier_id',
        'business_source_id',
        'dispatch_date',
        'total_orders',
        'total_product_cost',
        'status',
        'locked_by',
        'locked_at',
        'created_by',
        'created_at',
        'updated_at',
    ];
}
