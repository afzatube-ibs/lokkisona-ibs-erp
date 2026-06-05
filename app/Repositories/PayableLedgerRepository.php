<?php

namespace App\Repositories;

use App\Models\PayableLedger;

class PayableLedgerRepository extends BaseReadOnlyRepository
{
    public function modelClass(): string
    {
        return PayableLedger::class;
    }
}
