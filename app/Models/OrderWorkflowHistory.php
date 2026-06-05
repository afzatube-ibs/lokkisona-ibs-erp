<?php
namespace App\Models;

class OrderWorkflowHistory
{
    const TABLE = 'order_workflow_histories';

    public static array $columns = [
        'history_id',
        'order_id',
        'from_status',
        'to_status',
        'changed_by',
        'changed_at',
        'notes',
        'created_at',
    ];
}
