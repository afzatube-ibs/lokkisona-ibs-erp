<?php

namespace App\Repositories\Write;

use App\Models\SyncImport;

class SyncImportWriteRepository extends BaseWriteRepository
{
    public function modelClass(): string
    {
        return SyncImport::class;
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO `' . $this->escapeIdentifier($this->table()) . '` '
            . '(sync_preview_id, business_source_id, import_reference, total_selected, total_imported, total_failed, status, approved_by, approved_at, started_at, created_at) '
            . 'VALUES (:sync_preview_id, :business_source_id, :import_reference, :total_selected, :total_imported, :total_failed, :status, :approved_by, NOW(), NOW(), NOW())';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($data);

        return (int) $this->pdo->lastInsertId();
    }

    public function finish(int $id, int $imported, int $failed, string $status): bool
    {
        $sql = 'UPDATE `' . $this->escapeIdentifier($this->table()) . '` SET total_imported = :imported, total_failed = :failed, status = :status, finished_at = NOW() '
            . 'WHERE sync_import_id = :id LIMIT 1';
        $statement = $this->pdo->prepare($sql);

        return $statement->execute([
            'imported' => $imported,
            'failed' => $failed,
            'status' => $status,
            'id' => $id,
        ]);
    }
}
