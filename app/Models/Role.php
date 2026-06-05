<?php
namespace App\Models;

class Role
{
    const TABLE = 'roles';

    public static array $columns = [
        'role_id',
        'role_name',
        'description',
        'is_active',
        'created_at',
        'updated_at',
    ];
}
