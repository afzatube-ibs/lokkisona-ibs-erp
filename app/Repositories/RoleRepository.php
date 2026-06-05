<?php

namespace App\Repositories;

use App\Models\Role;

class RoleRepository extends BaseReadOnlyRepository
{
    public function modelClass(): string
    {
        return Role::class;
    }
}
