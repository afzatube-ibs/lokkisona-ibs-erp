<?php

namespace App\Models;

class Business extends BaseModel
{
    const TABLE = 'businesses';

    const PRIMARY_KEY = 'business_id';

    public static array $columns = [
        'business_id',
        'business_name',
        'business_code',
        'status',
        'created_at',
        'updated_at',
    ];
}
