<?php

namespace App\Repositories;

use App\Models\Business;

class BusinessRepository extends BaseReadOnlyRepository
{
    public function modelClass(): string
    {
        return Business::class;
    }
}
