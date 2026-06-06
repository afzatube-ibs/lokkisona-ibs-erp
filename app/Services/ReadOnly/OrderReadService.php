<?php

namespace App\Services\ReadOnly;

use App\Repositories\OrderRepository;

class OrderReadService
{
    private OrderRepository $repository;

    public function __construct(?OrderRepository $repository = null)
    {
        $this->repository = $repository ?? new OrderRepository();
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

    public function findByStatus(string $status, int $limit = 50): array
    {
        return $this->repository->findByStatus($status, $limit);
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
