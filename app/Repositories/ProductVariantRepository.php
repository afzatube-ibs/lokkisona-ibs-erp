<?php

namespace App\Repositories;

use App\Models\ProductVariant;

class ProductVariantRepository extends BaseReadOnlyRepository
{
    public function modelClass(): string
    {
        return ProductVariant::class;
    }
}
