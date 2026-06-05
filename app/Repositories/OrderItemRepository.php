<?php

namespace App\Repositories;

use App\Models\OrderItem;

class OrderItemRepository extends BaseReadOnlyRepository
{
    public function modelClass(): string
    {
        return OrderItem::class;
    }
}
