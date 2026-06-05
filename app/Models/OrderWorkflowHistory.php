<?php

namespace App\Models;

class OrderWorkflowHistory extends BaseModel
{
    const TABLE = 'order_workflow_histories';

    const PRIMARY_KEY = 'order_workflow_history_id';

    public static array $columns = [
        'order_workflow_history_id',
        'order_id',
        'manual_order_id',
        'from_status',
        'to_status',
        'action_note',
        'changed_by',
        'changed_at',
    ];
}
