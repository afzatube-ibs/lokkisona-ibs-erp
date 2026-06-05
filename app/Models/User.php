<?php

namespace App\Models;

class User extends BaseModel
{
    const TABLE = 'users';

    const PRIMARY_KEY = 'user_id';

    public static array $columns = [
        'user_id',
        'username',
        'display_name',
        'email',
        'password_hash',
        'role_key',
        'status',
        'last_login_at',
        'created_at',
        'updated_at',
    ];
}
