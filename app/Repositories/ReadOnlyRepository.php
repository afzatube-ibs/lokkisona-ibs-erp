<?php

namespace App\Repositories;

interface ReadOnlyRepository
{
    public function logicalTable(): string;

    public function modelClass(): string;

    public function tableExists(): bool;

    public function findById(int $id): ?array;

    public function all(int $limit = 100, int $offset = 0): array;

    public function count(): int;
}
