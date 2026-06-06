<?php

namespace App\Repositories;

use App\Models\OrderWorkflowHistory;

class OrderWorkflowHistoryRepository extends BaseReadOnlyRepository
{
    public function modelClass(): string
    {
        return OrderWorkflowHistory::class;
    }

    public function findByOrderId(int $orderId, int $limit = 20): array
    {
        if (!$this->tableExists() || $orderId <= 0) {
            return [];
        }

        try {
            $limit = max(1, min($limit, 50));
            $table = \App\Database\TableName::forModel(OrderWorkflowHistory::class);
            $sql = 'SELECT * FROM `' . $this->escapeIdentifier($table) . '` '
                . 'WHERE order_id = :order_id ORDER BY changed_at DESC, order_workflow_history_id DESC LIMIT ' . $limit;
            \App\Database\QueryGuard::assertReadOnly($sql);
            $statement = $this->pdo->prepare($sql);
            $statement->execute(['order_id' => $orderId]);

            return $statement->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }
}
