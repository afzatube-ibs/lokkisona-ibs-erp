<?php

namespace App\Repositories\Write;

use App\Models\StatusMapping;
use App\Support\SchemaColumnProbe;

class StatusMappingWriteRepository extends BaseWriteRepository
{
    public const TYPE_CONNECTOR_QUEUE = 'connector_queue';
    public const TYPE_LEGACY_OPENCART = 'legacy_opencart_status';

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
        if (SchemaColumnProbe::tableHasColumn($table, 'mapping_type', $this->pdo)) {
            $columns['mapping_type'] = $data['mapping_type'] ?? self::TYPE_LEGACY_OPENCART;
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

    public function countActiveQueueMappings(int $businessSourceId): int
    {
        if (!$this->tableExists() || !$this->hasMappingTypeColumn()) {
            return 0;
        }

        $sql = 'SELECT COUNT(*) AS row_count FROM `' . $this->escapeIdentifier($this->table()) . '` '
            . 'WHERE business_source_id = :source_id AND is_active = 1 AND mapping_type = :mapping_type';
        $statement = $this->pdo->prepare($sql);
        $statement->execute([
            'source_id' => $businessSourceId,
            'mapping_type' => self::TYPE_CONNECTOR_QUEUE,
        ]);
        $row = $statement->fetch(\PDO::FETCH_ASSOC);

        return (int) ($row['row_count'] ?? 0);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findActiveQueueMappings(int $businessSourceId): array
    {
        if (!$this->tableExists() || !$this->hasMappingTypeColumn()) {
            return [];
        }

        $sql = 'SELECT * FROM `' . $this->escapeIdentifier($this->table()) . '` '
            . 'WHERE business_source_id = :source_id AND is_active = 1 AND mapping_type = :mapping_type '
            . 'ORDER BY status_mapping_id ASC';
        $statement = $this->pdo->prepare($sql);
        $statement->execute([
            'source_id' => $businessSourceId,
            'mapping_type' => self::TYPE_CONNECTOR_QUEUE,
        ]);

        return $statement->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    public function resolveQueueMapping(int $businessSourceId, string $queueStatusId): ?array
    {
        $queueStatusId = trim($queueStatusId);
        if (!$this->tableExists() || $businessSourceId <= 0 || $queueStatusId === '') {
            return null;
        }

        if ($this->hasMappingTypeColumn()) {
            $row = $this->findActiveQueueBySourceStatusExact($businessSourceId, $queueStatusId);
            if ($row !== null) {
                return $row;
            }

            return $this->findActiveQueueBySourceStatusCaseInsensitive($businessSourceId, $queueStatusId);
        }

        return $this->resolveActiveMapping($businessSourceId, $queueStatusId, '');
    }

    /**
     * @return array{matched: bool, matched_key: ?string, match_mode: ?string, ibs_status: ?string, mapping_id: ?int}
     */
    public function probeQueueMapping(int $businessSourceId, string $queueStatusId): array
    {
        $queueStatusId = trim($queueStatusId);
        if ($queueStatusId === '') {
            return [
                'matched' => false,
                'matched_key' => null,
                'match_mode' => null,
                'ibs_status' => null,
                'mapping_id' => null,
            ];
        }

        $row = $this->findActiveQueueBySourceStatusExact($businessSourceId, $queueStatusId);
        if ($row !== null) {
            return [
                'matched' => true,
                'matched_key' => $queueStatusId,
                'match_mode' => 'exact',
                'ibs_status' => (string) ($row['ibs_status'] ?? ''),
                'mapping_id' => (int) ($row['status_mapping_id'] ?? 0),
            ];
        }

        $row = $this->findActiveQueueBySourceStatusCaseInsensitive($businessSourceId, $queueStatusId);
        if ($row !== null) {
            return [
                'matched' => true,
                'matched_key' => $queueStatusId,
                'match_mode' => 'case_insensitive',
                'ibs_status' => (string) ($row['ibs_status'] ?? ''),
                'mapping_id' => (int) ($row['status_mapping_id'] ?? 0),
            ];
        }

        return [
            'matched' => false,
            'matched_key' => null,
            'match_mode' => null,
            'ibs_status' => null,
            'mapping_id' => null,
        ];
    }

    public function upsertQueueMapping(int $businessSourceId, string $queueStatusId, string $ibsStatus, ?string $notes = null): int
    {
        $queueStatusId = trim($queueStatusId);
        $ibsStatus = trim($ibsStatus);
        if ($businessSourceId <= 0 || $queueStatusId === '' || $ibsStatus === '') {
            return 0;
        }

        $existing = $this->resolveQueueMapping($businessSourceId, $queueStatusId);
        if ($existing !== null) {
            $this->updateQueueMapping((int) $existing['status_mapping_id'], $ibsStatus, $notes);

            return (int) $existing['status_mapping_id'];
        }

        return $this->create([
            'business_source_id' => $businessSourceId,
            'source_status' => $queueStatusId,
            'ibs_status' => $ibsStatus,
            'workflow_group' => 'workflow',
            'mapping_type' => self::TYPE_CONNECTOR_QUEUE,
            'notes' => $notes,
            'is_active' => 1,
        ]);
    }

    public function deactivateQueueMappingsNotIn(int $businessSourceId, array $queueStatusIds): void
    {
        if (!$this->tableExists() || !$this->hasMappingTypeColumn()) {
            return;
        }

        $normalized = [];
        foreach ($queueStatusIds as $id) {
            $id = trim((string) $id);
            if ($id !== '') {
                $normalized[] = $id;
            }
        }

        $rows = $this->findActiveQueueMappings($businessSourceId);
        foreach ($rows as $row) {
            $sourceStatus = trim((string) ($row['source_status'] ?? ''));
            if ($sourceStatus !== '' && !in_array($sourceStatus, $normalized, true)) {
                $this->setActive((int) ($row['status_mapping_id'] ?? 0), false);
            }
        }
    }

    private function updateQueueMapping(int $mappingId, string $ibsStatus, ?string $notes): void
    {
        if ($mappingId <= 0 || !$this->tableExists()) {
            return;
        }

        $set = ['ibs_status = :ibs_status', 'updated_at = NOW()', 'is_active = 1'];
        $params = ['ibs_status' => $ibsStatus, 'id' => $mappingId];
        $table = $this->table();
        if ($notes !== null && SchemaColumnProbe::tableHasColumn($table, 'notes', $this->pdo)) {
            $set[] = 'notes = :notes';
            $params['notes'] = $notes;
        }

        $sql = 'UPDATE `' . $this->escapeIdentifier($table) . '` SET ' . implode(', ', $set)
            . ' WHERE status_mapping_id = :id LIMIT 1';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
    }

    private function findActiveQueueBySourceStatusExact(int $businessSourceId, string $sourceStatus): ?array
    {
        if (!$this->hasMappingTypeColumn()) {
            return null;
        }

        $sourceStatus = trim($sourceStatus);
        if ($sourceStatus === '') {
            return null;
        }

        $sql = 'SELECT * FROM `' . $this->escapeIdentifier($this->table()) . '` '
            . 'WHERE business_source_id = :source_id AND source_status = :source_status '
            . 'AND is_active = 1 AND mapping_type = :mapping_type LIMIT 1';
        $statement = $this->pdo->prepare($sql);
        $statement->execute([
            'source_id' => $businessSourceId,
            'source_status' => $sourceStatus,
            'mapping_type' => self::TYPE_CONNECTOR_QUEUE,
        ]);
        $row = $statement->fetch(\PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }

    private function findActiveQueueBySourceStatusCaseInsensitive(int $businessSourceId, string $sourceStatus): ?array
    {
        if (!$this->hasMappingTypeColumn()) {
            return null;
        }

        $sourceStatus = trim($sourceStatus);
        if ($sourceStatus === '') {
            return null;
        }

        $sql = 'SELECT * FROM `' . $this->escapeIdentifier($this->table()) . '` '
            . 'WHERE business_source_id = :source_id AND LOWER(TRIM(source_status)) = LOWER(:source_status) '
            . 'AND is_active = 1 AND mapping_type = :mapping_type LIMIT 1';
        $statement = $this->pdo->prepare($sql);
        $statement->execute([
            'source_id' => $businessSourceId,
            'source_status' => $sourceStatus,
            'mapping_type' => self::TYPE_CONNECTOR_QUEUE,
        ]);
        $row = $statement->fetch(\PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }

    private function hasMappingTypeColumn(): bool
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }

        if (!$this->tableExists()) {
            $cached = false;

            return false;
        }

        $cached = SchemaColumnProbe::tableHasColumn($this->table(), 'mapping_type', $this->pdo);

        return $cached;
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
