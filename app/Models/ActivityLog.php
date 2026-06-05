<?php
namespace App\Models;

class ActivityLog
{
    const TABLE = 'activity_logs';

    public static array $columns = [
        'log_id',
        'user_id',
        'action',
        'module',
        'reference_type',
        'reference_id',
        'description',
        'ip_address',
        'created_at',
    ];
}
