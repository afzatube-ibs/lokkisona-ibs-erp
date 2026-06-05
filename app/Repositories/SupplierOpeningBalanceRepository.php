<?php

namespace App\Repositories;

use App\Models\SupplierOpeningBalance;

class SupplierOpeningBalanceRepository extends BaseReadOnlyRepository
{
    public function modelClass(): string
    {
        return SupplierOpeningBalance::class;
    }
}
