<?php
namespace App\Models;

class BusinessSource
{
    const TABLE = 'business_sources';

    public static array $columns = [
        'source_id',
        'source_name',
        'source_type',
        'url',
        'is_active',
        'created_at',
        'updated_at',
    ];
}
