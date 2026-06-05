<?php

namespace App\Repositories;

use App\Models\Supplier;

class SupplierRepository extends BaseReadOnlyRepository
{
    public function modelClass(): string
    {
        return Supplier::class;
    }
}
