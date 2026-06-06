<?php

namespace App\Models;

class SyncLog extends BaseModel
{
    const TABLE = 'sync_logs';

    const PRIMARY_KEY = 'sync_log_id';

    public static array $columns = [
        'sync_log_id',
        'business_source_id',
        'sync_preview_id',
        'sync_import_id',
        'log_type',
        'status',
        'message',
        'context_json',
        'created_at',
    ];
}
