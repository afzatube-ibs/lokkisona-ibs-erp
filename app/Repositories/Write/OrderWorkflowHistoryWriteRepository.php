<?php

namespace App\Repositories\Write;

use App\Models\OrderWorkflowHistory;

class OrderWorkflowHistoryWriteRepository extends BaseWriteRepository
{
    public function modelClass(): string
    {
        return OrderWorkflowHistory::class;
    }

    public function insert(
        ?int $orderId,
        ?int $manualOrderId,
        ?string $fromStatus,
        string $toStatus,
        ?string $note = null,
        ?int $changedBy = null
    ): int {
        $sql = 'INSERT INTO `' . $this->escapeIdentifier($this->table()) . '` '
            . '(order_id, manual_order_id, from_status, to_status, action_note, changed_by, changed_at) '
            . 'VALUES (:order_id, :manual_order_id, :from_status, :to_status, :action_note, :changed_by, NOW())';
        $statement = $this->pdo->prepare($sql);
        $statement->execute([
            'order_id' => $orderId,
            'manual_order_id' => $manualOrderId,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'action_note' => $note,
            'changed_by' => $changedBy,
        ]);

        return (int) $this->pdo->lastInsertId();
    }
}
