<?php
namespace App\Models;

class Supplier
{
    const TABLE = 'suppliers';

    public static array $columns = [
        'supplier_id',
        'supplier_name',
        'contact_person',
        'phone',
        'email',
        'address',
        'is_active',
        'created_at',
        'updated_at',
    ];
}
