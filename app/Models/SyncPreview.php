<?php

namespace App\Models;

class SyncPreview extends BaseModel
{
    const TABLE = 'sync_previews';

    const PRIMARY_KEY = 'sync_preview_id';

    public static array $columns = [
        'sync_preview_id',
        'business_source_id',
        'preview_reference',
        'preview_type',
        'total_found',
        'total_new',
        'total_existing',
        'total_blocked',
        'status',
        'requested_by',
        'requested_at',
        'finished_at',
        'red_issues_summary',
    ];
}
