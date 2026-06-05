<?php

namespace App\Models;

class Supplier extends BaseModel
{
    const TABLE = 'suppliers';

    const PRIMARY_KEY = 'supplier_id';

    public static array $columns = [
        'supplier_id',
        'supplier_name',
        'contact_person',
        'phone',
        'email',
        'address',
        'payment_terms',
        'payable_balance',
        'status',
        'linked_business_source_id',
        'created_at',
        'updated_at',
    ];
}
