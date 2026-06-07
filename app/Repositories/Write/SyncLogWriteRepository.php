<?php

namespace App\Repositories\Write;

use App\Models\SyncLog;

class SyncLogWriteRepository extends BaseWriteRepository
{
    public function modelClass(): string
    {
        return SyncLog::class;
    }

    public function append(
        ?int $businessSourceId,
        ?int $previewId,
        ?int $importId,
        string $logType,
        string $status,
        string $message,
        ?array $context = null
    ): int {
        $sql = 'INSERT INTO `' . $this->escapeIdentifier($this->table()) . '` '
            . '(business_source_id, sync_preview_id, sync_import_id, log_type, status, message, context_json, created_at) '
            . 'VALUES (:business_source_id, :sync_preview_id, :sync_import_id, :log_type, :status, :message, :context_json, NOW())';
        $statement = $this->pdo->prepare($sql);
        $statement->execute([
            'business_source_id' => $businessSourceId,
            'sync_preview_id' => $previewId,
            'sync_import_id' => $importId,
            'log_type' => $logType,
            'status' => $status,
            'message' => $message,
            'context_json' => $context !== null ? json_encode($context, JSON_UNESCAPED_UNICODE) : null,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function findContextByPreviewId(int $previewId, string $logType = 'test_sync'): ?array
    {
        if (!$this->tableExists() || $previewId <= 0) {
            return null;
        }

        $sql = 'SELECT context_json FROM `' . $this->escapeIdentifier($this->table()) . '` '
            . 'WHERE sync_preview_id = :preview_id AND log_type = :log_type ORDER BY sync_log_id DESC LIMIT 1';
        $statement = $this->pdo->prepare($sql);
        $statement->execute(['preview_id' => $previewId, 'log_type' => $logType]);
        $row = $statement->fetch(\PDO::FETCH_ASSOC);
        if ($row === false) {
            return null;
        }

        $decoded = json_decode((string) ($row['context_json'] ?? ''), true);

        return is_array($decoded) ? $decoded : null;
    }
}
