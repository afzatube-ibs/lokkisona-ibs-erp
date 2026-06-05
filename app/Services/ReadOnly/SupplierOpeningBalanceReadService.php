<?php

namespace App\Services\ReadOnly;

use App\Repositories\SupplierOpeningBalanceRepository;

class SupplierOpeningBalanceReadService
{
    private SupplierOpeningBalanceRepository $repository;

    public function __construct(?SupplierOpeningBalanceRepository $repository = null)
    {
        $this->repository = $repository ?? new SupplierOpeningBalanceRepository();
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
