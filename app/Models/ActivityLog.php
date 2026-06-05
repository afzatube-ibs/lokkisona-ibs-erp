<?php

namespace App\Models;

/**
 * Future database contract for the activity_logs table.
 *
 * This is metadata only and is separate from the current file-based runtime
 * logger App\ActivityLog, which writes JSON lines to storage/logs/activity.log.
 * This model does not change current logging behavior.
 */
class ActivityLog extends BaseModel
{
    const TABLE = 'activity_logs';

    const PRIMARY_KEY = 'activity_log_id';

    public static array $columns = [
        'activity_log_id',
        'action',
        'message',
        'user_name',
        'role_key',
        'ip_address',
        'request_method',
        'route_path',
        'context_json',
        'created_at',
    ];
}
