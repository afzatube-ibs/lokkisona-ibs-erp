<?php

namespace App\Repositories;

use App\Models\ReturnReceive;

class ReturnReceiveRepository extends BaseReadOnlyRepository
{
    public function modelClass(): string
    {
        return ReturnReceive::class;
    }
}
