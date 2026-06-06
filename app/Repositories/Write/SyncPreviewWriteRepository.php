<?php

namespace App\Repositories\Write;

use App\Models\SyncPreview;

class SyncPreviewWriteRepository extends BaseWriteRepository
{
    public function modelClass(): string
    {
        return SyncPreview::class;
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO `' . $this->escapeIdentifier($this->table()) . '` '
            . '(business_source_id, preview_reference, preview_type, total_found, total_new, total_existing, total_blocked, status, requested_by, requested_at) '
            . 'VALUES (:business_source_id, :preview_reference, :preview_type, :total_found, :total_new, :total_existing, :total_blocked, :status, :requested_by, NOW())';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($data);

        return (int) $this->pdo->lastInsertId();
    }

    public function finish(int $id, array $totals, string $status = 'completed'): bool
    {
        $sql = 'UPDATE `' . $this->escapeIdentifier($this->table()) . '` SET '
            . 'total_found = :total_found, total_new = :total_new, total_existing = :total_existing, '
            . 'total_blocked = :total_blocked, status = :status, finished_at = NOW() '
            . 'WHERE sync_preview_id = :id LIMIT 1';
        $statement = $this->pdo->prepare($sql);

        return $statement->execute([
            'total_found' => (int) ($totals['total_found'] ?? 0),
            'total_new' => (int) ($totals['total_new'] ?? 0),
            'total_existing' => (int) ($totals['total_existing'] ?? 0),
            'total_blocked' => (int) ($totals['total_blocked'] ?? 0),
            'status' => $status,
            'id' => $id,
        ]);
    }

    public function findLatestForSource(int $businessSourceId): ?array
    {
        if (!$this->tableExists()) {
            return null;
        }

        $sql = 'SELECT * FROM `' . $this->escapeIdentifier($this->table()) . '` '
            . 'WHERE business_source_id = :source_id ORDER BY sync_preview_id DESC LIMIT 1';
        $statement = $this->pdo->prepare($sql);
        $statement->execute(['source_id' => $businessSourceId]);
        $row = $statement->fetch(\PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }

    public function find(int $id): ?array
    {
        return $this->findById($id);
    }
}
