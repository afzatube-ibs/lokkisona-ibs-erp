<?php

namespace App\Models;

class LaunchCutover extends BaseModel
{
    const TABLE = 'launch_cutovers';

    const PRIMARY_KEY = 'cutover_id';

    public static array $columns = [
        'cutover_id',
        'go_live_date',
        'cutoff_date',
        'supplier_id',
        'confirmed_by',
        'confirmed_at',
        'status',
        'notes',
        'created_at',
        'updated_at',
    ];
}
