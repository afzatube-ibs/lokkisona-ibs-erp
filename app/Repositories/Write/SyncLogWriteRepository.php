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
}
