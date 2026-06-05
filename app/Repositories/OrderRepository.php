<?php

namespace App\Repositories;

use App\Models\Order;

class OrderRepository extends BaseReadOnlyRepository
{
    public function modelClass(): string
    {
        return Order::class;
    }
}
