<?php

namespace App\Services\ReadOnly;

use App\Repositories\ProductStockHistoryRepository;

class ProductStockHistoryReadService
{
    private ProductStockHistoryRepository $repository;

    public function __construct(?ProductStockHistoryRepository $repository = null)
    {
        $this->repository = $repository ?? new ProductStockHistoryRepository();
    }

    public function tableExists(): bool
    {
        return $this->repository->tableExists();
    }

    public function all(int $limit = 100, int $offset = 0): array
    {
        return $this->repository->all($limit, $offset);
    }

    public function latest(int $limit = 50): array
    {
        return $this->repository->latest($limit);
    }

    public function count(): int
    {
        return $this->repository->count();
    }
}