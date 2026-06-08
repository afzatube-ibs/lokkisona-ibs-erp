<?php

namespace App\Models;

class StatusMapping extends BaseModel
{
    const TABLE = 'status_mappings';

    const PRIMARY_KEY = 'status_mapping_id';

    public static array $columns = [
        'status_mapping_id',
        'business_source_id',
        'source_status',
        'ibs_status',
        'workflow_group',
        'return_type',
        'courier_status',
        'notes',
        'last_matched_count',
        'last_synced_at',
        'is_active',
        'created_by',
        'created_at',
        'updated_at',
    ];
}
