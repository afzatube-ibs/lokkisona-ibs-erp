<?php

namespace App\Services\ReadOnly;

use App\Repositories\ManualOrderRepository;

class ManualOrderReadService
{
    private ManualOrderRepository $repository;

    public function __construct(?ManualOrderRepository $repository = null)
    {
        $this->repository = $repository ?? new ManualOrderRepository();
    }

    public function tableExists(): bool
    {
        return $this->repository->tableExists();
    }

    public function findById(int $id): ?array
    {
        return $this->repository->findById($id);
    }

    public function all(int $limit = 100, int $offset = 0): array
    {
        return $this->repository->all($limit, $offset);
    }

    public function latest(int $limit = 20): array
    {
        return $this->repository->latest($limit);
    }

    public function count(): int
    {
        return $this->repository->count();
    }
}
