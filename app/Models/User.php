<?php
namespace App\Models;

class User
{
    const TABLE = 'users';

    public static array $columns = [
        'user_id',
        'username',
        'password_hash',
        'full_name',
        'email',
        'role_id',
        'is_active',
        'last_login_at',
        'created_at',
        'updated_at',
    ];
}
