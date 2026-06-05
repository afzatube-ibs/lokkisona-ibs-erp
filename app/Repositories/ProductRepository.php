<?php

namespace App\Repositories;

use App\Models\Product;

class ProductRepository extends BaseReadOnlyRepository
{
    public function modelClass(): string
    {
        return Product::class;
    }
}
