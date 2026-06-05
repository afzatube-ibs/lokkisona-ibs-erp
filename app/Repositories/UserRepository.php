<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository extends BaseReadOnlyRepository
{
    public function modelClass(): string
    {
        return User::class;
    }
}
