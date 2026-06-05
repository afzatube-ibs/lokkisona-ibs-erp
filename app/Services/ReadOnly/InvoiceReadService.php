<?php

namespace App\Services\ReadOnly;

use App\Repositories\InvoiceRepository;

class InvoiceReadService
{
    private InvoiceRepository $repository;

    public function __construct(?InvoiceRepository $repository = null)
    {
        $this->repository = $repository ?? new InvoiceRepository();
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
