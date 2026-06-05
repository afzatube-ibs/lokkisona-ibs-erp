<?php

namespace App\Repositories;

use App\Models\LaunchCutover;

class LaunchCutoverRepository extends BaseReadOnlyRepository
{
    public function modelClass(): string
    {
        return LaunchCutover::class;
    }
}
