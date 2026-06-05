<?php

namespace App\Repositories;

use App\Models\ActivityLog as ActivityLogModel;

class ActivityLogRepository extends BaseReadOnlyRepository
{
    public function modelClass(): string
    {
        return ActivityLogModel::class;
    }
}
