<?php

namespace App\Models;

class PrintLog extends BaseModel
{
    const TABLE = 'print_logs';

    const PRIMARY_KEY = 'print_log_id';

    public static array $columns = [
        'print_log_id',
        'print_reference',
        'printable_type',
        'printable_id',
        'action',
        'user_id',
        'route_path',
        'context_json',
        'created_at',
    ];
}
