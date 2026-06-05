<?php

namespace App\Repositories;

use App\Models\ProductCostHistory;

class ProductCostHistoryRepository extends BaseReadOnlyRepository
{
    public function modelClass(): string
    {
        return ProductCostHistory::class;
    }
}