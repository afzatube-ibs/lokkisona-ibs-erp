<?php

namespace App\Services\ReadOnly;

use App\Repositories\ReturnReceiveRepository;

class ReturnReceiveReadService
{
    private ReturnReceiveRepository $repository;

    public function __construct(?ReturnReceiveRepository $repository = null)
    {
        $this->repository = $repository ?? new ReturnReceiveRepository();
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

    public function count(): int
    {
        return $this->repository->count();
    }
}
