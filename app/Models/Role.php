<?php

namespace App\Models;

class Role extends BaseModel
{
    const TABLE = 'roles';

    const PRIMARY_KEY = 'role_id';

    public static array $columns = [
        'role_id',
        'role_key',
        'role_name',
        'description',
        'status',
        'created_at',
        'updated_at',
    ];
}
