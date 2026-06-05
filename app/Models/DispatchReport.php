<?php
namespace App\Models;

class DispatchReport
{
    const TABLE = 'dispatch_reports';

    public static array $columns = [
        'dispatch_report_id',
        'supplier_id',
        'source_id',
        'report_date',
        'total_items',
        'total_cost',
        'status',
        'created_by',
        'created_at',
        'updated_at',
    ];
}
