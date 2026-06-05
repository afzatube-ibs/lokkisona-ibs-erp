<?php

namespace App\Repositories;

use App\Models\DispatchReport;

class DispatchReportRepository extends BaseReadOnlyRepository
{
    public function modelClass(): string
    {
        return DispatchReport::class;
    }
}
