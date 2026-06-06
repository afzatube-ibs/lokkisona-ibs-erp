<?php

namespace App\Repositories\Write;

use App\Models\SyncPreviewItem;

class SyncPreviewItemWriteRepository extends BaseWriteRepository
{
    public function modelClass(): string
    {
        return SyncPreviewItem::class;
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO `' . $this->escapeIdentifier($this->table()) . '` '
            . '(sync_preview_id, source_order_id, source_order_reference, source_invoice_reference, source_status, mapped_status, '
            . 'customer_name, order_total, item_count, preview_status, issue_summary, created_at) '
            . 'VALUES (:sync_preview_id, :source_order_id, :source_order_reference, :source_invoice_reference, :source_status, :mapped_status, '
            . ':customer_name, :order_total, :item_count, :preview_status, :issue_summary, NOW())';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($data);

        return (int) $this->pdo->lastInsertId();
    }

    public function forPreview(int $previewId, ?string $previewStatus = null): array
    {
        if (!$this->tableExists()) {
            return [];
        }

        $sql = 'SELECT * FROM `' . $this->escapeIdentifier($this->table()) . '` WHERE sync_preview_id = :preview_id';
        $params = ['preview_id' => $previewId];
        if ($previewStatus !== null && $previewStatus !== '') {
            $sql .= ' AND preview_status = :preview_status';
            $params['preview_status'] = $previewStatus;
        }
        $sql .= ' ORDER BY sync_preview_item_id ASC';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }
}
