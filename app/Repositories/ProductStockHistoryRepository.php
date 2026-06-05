<?php

namespace App\Repositories;

use App\Models\ProductStockHistory;

class ProductStockHistoryRepository extends BaseReadOnlyRepository
{
    public function modelClass(): string
    {
        return ProductStockHistory::class;
    }
}