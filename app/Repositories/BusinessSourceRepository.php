<?php

namespace App\Repositories;

use App\Models\BusinessSource;

class BusinessSourceRepository extends BaseReadOnlyRepository
{
    public function modelClass(): string
    {
        return BusinessSource::class;
    }
}
