<?php

namespace App\Repositories\Write;

use App\Models\LaunchCutover;

class LaunchCutoverWriteRepository extends BaseWriteRepository
{
    public function modelClass(): string
    {
        return LaunchCutover::class;
    }

    public function createLock(array $data): int
    {
        $sql = 'INSERT INTO `' . $this->escapeIdentifier($this->table()) . '` '
            . '(go_live_date, cutoff_date, supplier_id, confirmed_at, status, notes, created_at) '
            . 'VALUES (:go_live_date, :cutoff_date, :supplier_id, NOW(), :status, :notes, NOW())';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($data);

        return (int) $this->pdo->lastInsertId();
    }

    public function latestLocked(): ?array
    {
        if (!$this->tableExists()) {
            return null;
        }

        $sql = 'SELECT * FROM `' . $this->escapeIdentifier($this->table()) . '` WHERE status = :status ORDER BY cutover_id DESC LIMIT 1';
        $statement = $this->pdo->prepare($sql);
        $statement->execute(['status' => 'locked']);
        $row = $statement->fetch(\PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }
}
