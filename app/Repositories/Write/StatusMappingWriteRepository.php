<?php

namespace App\Repositories\Write;

use App\Models\StatusMapping;
use App\Support\SchemaColumnProbe;

class StatusMappingWriteRepository extends BaseWriteRepository
{
    public function modelClass(): string
    {
        return StatusMapping::class;
    }

    public function create(array $data): int
    {
        $table = $this->table();
        $columns = [
            'business_source_id' => $data['business_source_id'],
            'source_status' => $data['source_status'],
            'ibs_status' => $data['ibs_status'],
            'workflow_group' => $data['workflow_group'] ?? null,
            'return_type' => $data['return_type'] ?? null,
            'courier_status' => $data['courier_status'] ?? null,
            'is_active' => $data['is_active'] ?? 1,
            'created_by' => $data['created_by'] ?? null,
        ];
        if (!empty($data['notes']) && SchemaColumnProbe::tableHasColumn($table, 'notes', $this->pdo)) {
            $columns['notes'] = $data['notes'];
        }

        $fieldNames = array_keys($columns);
        $placeholders = array_map(static fn (string $name): string => ':' . $name, $fieldNames);
        $sql = 'INSERT INTO `' . $this->escapeIdentifier($table) . '` ('
            . implode(', ', array_map(fn (string $c): string => '`' . $this->escapeIdentifier($c) . '`', $fieldNames))
            . ', created_at) VALUES (' . implode(', ', $placeholders) . ', NOW())';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($columns);

        return (int) $this->pdo->lastInsertId();
    }

    public function recordMatch(int $mappingId): void
    {
        if ($mappingId <= 0 || !$this->tableExists()) {
            return;
        }

        $table = $this->table();
        $set = ['updated_at = NOW()'];
        if (SchemaColumnProbe::tableHasColumn($table, 'last_matched_count', $this->pdo)) {
            $set[] = 'last_matched_count = last_matched_count + 1';
        }
        if (SchemaColumnProbe::tableHasColumn($table, 'last_synced_at', $this->pdo)) {
            $set[] = 'last_synced_at = NOW()';
        }

        $sql = 'UPDATE `' . $this->escapeIdentifier($table) . '` SET ' . implode(', ', $set)
            . ' WHERE status_mapping_id = :id LIMIT 1';
        $statement = $this->pdo->prepare($sql);
        $statement->execute(['id' => $mappingId]);
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
        return $this->resolveActiveMapping($businessSourceId, '', $sourceStatus);
    }

    /**
     * Resolve active mapping by origin status name and/or OpenCart status id.
     * Tries exact name, exact id, then case-insensitive name/id (trimmed).
     */
    public function resolveActiveMapping(int $businessSourceId, string $statusId, string $statusName): ?array
    {
        if (!$this->tableExists() || $businessSourceId <= 0) {
            return null;
        }

        $statusId = trim($statusId);
        $statusName = trim($statusName);
        $keys = [];
        foreach ([$statusName, $statusId] as $key) {
            if ($key !== '' && !in_array($key, $keys, true)) {
                $keys[] = $key;
            }
        }

        if ($keys === []) {
            return null;
        }

        foreach ($keys as $key) {
            $row = $this->findActiveBySourceStatusExact($businessSourceId, $key);
            if ($row !== null) {
                return $row;
            }
        }

        foreach ($keys as $key) {
            $row = $this->findActiveBySourceStatusCaseInsensitive($businessSourceId, $key);
            if ($row !== null) {
                return $row;
            }
        }

        return null;
    }

    /**
     * @return array{matched: bool, matched_key: ?string, match_mode: ?string, ibs_status: ?string, mapping_id: ?int}
     */
    public function probeActiveMapping(int $businessSourceId, string $statusId, string $statusName): array
    {
        $statusId = trim($statusId);
        $statusName = trim($statusName);
        $keys = [];
        foreach ([$statusName, $statusId] as $key) {
            if ($key !== '' && !in_array($key, $keys, true)) {
                $keys[] = $key;
            }
        }

        foreach ($keys as $key) {
            $row = $this->findActiveBySourceStatusExact($businessSourceId, $key);
            if ($row !== null) {
                return [
                    'matched' => true,
                    'matched_key' => $key,
                    'match_mode' => 'exact',
                    'ibs_status' => (string) ($row['ibs_status'] ?? ''),
                    'mapping_id' => (int) ($row['status_mapping_id'] ?? 0),
                ];
            }
        }

        foreach ($keys as $key) {
            $row = $this->findActiveBySourceStatusCaseInsensitive($businessSourceId, $key);
            if ($row !== null) {
                return [
                    'matched' => true,
                    'matched_key' => $key,
                    'match_mode' => 'case_insensitive',
                    'ibs_status' => (string) ($row['ibs_status'] ?? ''),
                    'mapping_id' => (int) ($row['status_mapping_id'] ?? 0),
                ];
            }
        }

        return [
            'matched' => false,
            'matched_key' => null,
            'match_mode' => null,
            'ibs_status' => null,
            'mapping_id' => null,
        ];
    }

    private function findActiveBySourceStatusExact(int $businessSourceId, string $sourceStatus): ?array
    {
        $sourceStatus = trim($sourceStatus);
        if ($sourceStatus === '') {
            return null;
        }

        $sql = 'SELECT * FROM `' . $this->escapeIdentifier($this->table()) . '` '
            . 'WHERE business_source_id = :source_id AND source_status = :source_status AND is_active = 1 LIMIT 1';
        $statement = $this->pdo->prepare($sql);
        $statement->execute(['source_id' => $businessSourceId, 'source_status' => $sourceStatus]);
        $row = $statement->fetch(\PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }

    private function findActiveBySourceStatusCaseInsensitive(int $businessSourceId, string $sourceStatus): ?array
    {
        $sourceStatus = trim($sourceStatus);
        if ($sourceStatus === '') {
            return null;
        }

        $sql = 'SELECT * FROM `' . $this->escapeIdentifier($this->table()) . '` '
            . 'WHERE business_source_id = :source_id AND LOWER(TRIM(source_status)) = LOWER(:source_status) AND is_active = 1 LIMIT 1';
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
