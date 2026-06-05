<?php

namespace App\Models;

class DispatchReportItem extends BaseModel
{
    const TABLE = 'dispatch_report_items';

    const PRIMARY_KEY = 'dispatch_report_item_id';

    public static array $columns = [
        'dispatch_report_item_id',
        'dispatch_report_id',
        'order_id',
        'manual_order_id',
        'order_reference',
        'product_cost_snapshot',
        'item_count',
        'status',
        'created_at',
    ];
}
