<?php

namespace App\Repositories\Write;

use App\Models\StatusMapping;

class StatusMappingWriteRepository extends BaseWriteRepository
{
    public function modelClass(): string
    {
        return StatusMapping::class;
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO `' . $this->escapeIdentifier($this->table()) . '` '
            . '(business_source_id, source_status, ibs_status, workflow_group, return_type, courier_status, is_active, created_by, created_at) '
            . 'VALUES (:business_source_id, :source_status, :ibs_status, :workflow_group, :return_type, :courier_status, :is_active, :created_by, NOW())';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($data);

        return (int) $this->pdo->lastInsertId();
    }

    public function findActiveForSource(int $businessSourceId): array
    {
        if (!$this->tableExists()) {
            return [];
        }

        $sql = 'SELECT * FROM `' . $this->escapeIdentifier($this->table()) . '` '
            . 'WHERE business_source_id = :source_id AND is_active = 1 ORDER BY status_mapping_id ASC';
        $statement = $this->pdo->prepare($sql);
        $statement->execute(['source_id' => $businessSourceId]);

        return $statement->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    public function findBySourceStatus(int $businessSourceId, string $sourceStatus): ?array
    {
        if (!$this->tableExists() || $sourceStatus === '') {
            return null;
        }

        $sql = 'SELECT * FROM `' . $this->escapeIdentifier($this->table()) . '` '
            . 'WHERE business_source_id = :source_id AND source_status = :source_status AND is_active = 1 LIMIT 1';
        $statement = $this->pdo->prepare($sql);
        $statement->execute(['source_id' => $businessSourceId, 'source_status' => $sourceStatus]);
        $row = $statement->fetch(\PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }

    public function countActiveForSource(int $businessSourceId): int
    {
        if (!$this->tableExists()) {
            return 0;
        }

        $sql = 'SELECT COUNT(*) AS row_count FROM `' . $this->escapeIdentifier($this->table()) . '` '
            . 'WHERE business_source_id = :source_id AND is_active = 1';
        $statement = $this->pdo->prepare($sql);
        $statement->execute(['source_id' => $businessSourceId]);
        $row = $statement->fetch(\PDO::FETCH_ASSOC);

        return (int) ($row['row_count'] ?? 0);
    }

    public function listRecent(int $limit = 50): array
    {
        if (!$this->tableExists()) {
            return [];
        }

        $limit = max(1, min($limit, 100));
        $sql = 'SELECT * FROM `' . $this->escapeIdentifier($this->table()) . '` ORDER BY status_mapping_id DESC LIMIT ' . $limit;
        $statement = $this->pdo->query($sql);

        return $statement ? ($statement->fetchAll(\PDO::FETCH_ASSOC) ?: []) : [];
    }

    public function setActive(int $id, bool $active): bool
    {
        if (!$this->tableExists()) {
            return false;
        }

        $sql = 'UPDATE `' . $this->escapeIdentifier($this->table()) . '` SET is_active = :active, updated_at = NOW() '
            . 'WHERE status_mapping_id = :id LIMIT 1';
        $statement = $this->pdo->prepare($sql);

        return $statement->execute(['active' => $active ? 1 : 0, 'id' => $id]);
    }
}
