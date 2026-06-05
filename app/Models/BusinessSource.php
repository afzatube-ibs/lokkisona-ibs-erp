<?php

namespace App\Models;

class BusinessSource extends BaseModel
{
    const TABLE = 'business_sources';

    const PRIMARY_KEY = 'business_source_id';

    public static array $columns = [
        'business_source_id',
        'business_id',
        'source_name',
        'source_type',
        'website_domain',
        'order_source_label',
        'default_supplier_id',
        'default_workflow',
        'status',
        'created_at',
        'updated_at',
    ];
}
