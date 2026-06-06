<?php

namespace App\Models;

class SyncImport extends BaseModel
{
    const TABLE = 'sync_imports';

    const PRIMARY_KEY = 'sync_import_id';

    public static array $columns = [
        'sync_import_id',
        'sync_preview_id',
        'business_source_id',
        'import_reference',
        'total_selected',
        'total_imported',
        'total_failed',
        'status',
        'approved_by',
        'approved_at',
        'started_at',
        'finished_at',
        'red_issues_summary',
        'created_at',
    ];
}
