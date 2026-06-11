<?php

namespace App\Models;

class ReturnReportItem extends BaseModel
{
    const TABLE = 'return_report_items';

    const PRIMARY_KEY = 'return_report_item_id';

    public static array $columns = [
        'return_report_item_id',
        'return_report_id',
        'return_receive_id',
        'order_id',
        'manual_order_id',
        'order_reference',
        'product_cost_snapshot',
        'item_count',
        'return_type',
        'return_reason',
        'status',
        'created_at',
    ];
}
