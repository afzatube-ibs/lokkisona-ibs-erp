<?php
namespace App\Models;

class ReturnReceive
{
    const TABLE = 'return_receives';

    public static array $columns = [
        'return_receive_id',
        'order_id',
        'source_id',
        'return_type',
        'return_reason',
        'return_status',
        'received_by',
        'received_at',
        'notes',
        'created_at',
        'updated_at',
    ];
}
