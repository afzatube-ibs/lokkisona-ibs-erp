<?php

namespace App\Repositories;

use App\Models\OrderWorkflowHistory;

class OrderWorkflowHistoryRepository extends BaseReadOnlyRepository
{
    public function modelClass(): string
    {
        return OrderWorkflowHistory::class;
    }
}
