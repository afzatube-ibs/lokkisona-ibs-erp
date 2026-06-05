<?php

namespace App\Repositories;

use App\Models\Invoice;

class InvoiceRepository extends BaseReadOnlyRepository
{
    public function modelClass(): string
    {
        return Invoice::class;
    }
}
