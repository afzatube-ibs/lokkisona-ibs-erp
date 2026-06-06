<?php

namespace App\Repositories\Write;

use App\Models\ReturnBatch;

class ReturnBatchWriteRepository extends BaseWriteRepository
{
    public function modelClass(): string
    {
        return ReturnBatch::class;
    }

    public function find(int $id): ?array
    {
        return $this->findById($id);
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO `' . $this->escapeIdentifier($this->table()) . '` '
            . '(return_batch_reference, supplier_id, total_returns, total_adjustment_amount, status, created_at) '
            . 'VALUES (:return_batch_reference, :supplier_id, :total_returns, :total_adjustment_amount, :status, NOW())';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($data);

        return (int) $this->pdo->lastInsertId();
    }

    public function updateStatus(int $id, string $status): bool
    {
        $sql = 'UPDATE `' . $this->escapeIdentifier($this->table()) . '` '
            . 'SET status = :status, reviewed_at = NOW(), updated_at = NOW() WHERE return_batch_id = :id';
        $statement = $this->pdo->prepare($sql);

        return $statement->execute(['status' => $status, 'id' => $id]);
    }

    public function listLatest(int $limit = 20): array
    {
        if (!$this->tableExists()) {
            return [];
        }

        $limit = max(1, min(50, $limit));
        $sql = 'SELECT * FROM `' . $this->escapeIdentifier($this->table()) . '` '
            . 'ORDER BY return_batch_id DESC LIMIT ' . $limit;
        $statement = $this->pdo->query($sql);

        return $statement ? ($statement->fetchAll(\PDO::FETCH_ASSOC) ?: []) : [];
    }

    /**
     * References already used today (for DDMMYYYY / -P# suffix generation).
     *
     * @return array<int, string>
     */
    public function findReferencesLike(string $prefix): array
    {
        if (!$this->tableExists() || $prefix === '') {
            return [];
        }

        $sql = 'SELECT return_batch_reference FROM `' . $this->escapeIdentifier($this->table()) . '` '
            . 'WHERE return_batch_reference LIKE :prefix';
        $statement = $this->pdo->prepare($sql);
        $statement->execute(['prefix' => $prefix . '%']);

        $refs = [];
        foreach ($statement->fetchAll(\PDO::FETCH_ASSOC) ?: [] as $row) {
            $refs[] = (string) ($row['return_batch_reference'] ?? '');
        }

        return $refs;
    }
}
