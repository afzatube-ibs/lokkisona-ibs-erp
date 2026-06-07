<?php

namespace App\Repositories;

use App\Models\OrderWorkflowHistory;

class OrderWorkflowHistoryRepository extends BaseReadOnlyRepository
{
    public function modelClass(): string
    {
        return OrderWorkflowHistory::class;
    }

    public function latest(int $limit = 30): array
    {
        if (!$this->tableExists()) {
            return [];
        }

        try {
            $limit = max(1, min($limit, 50));
            $table = \App\Database\TableName::forModel(OrderWorkflowHistory::class);
            $sql = 'SELECT * FROM `' . $this->escapeIdentifier($table) . '` '
                . 'ORDER BY changed_at DESC, order_workflow_history_id DESC LIMIT ' . $limit;
            \App\Database\QueryGuard::assertReadOnly($sql);
            $statement = $this->pdo->query($sql);

            return $statement->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * @param array<int, int> $orderIds
     * @return array<int, array<string, mixed>>
     */
    public function findImportNotesByOrderIds(array $orderIds): array
    {
        $orderIds = array_values(array_filter(array_map('intval', $orderIds), static fn (int $id): bool => $id > 0));
        if (!$this->tableExists() || $orderIds === []) {
            return [];
        }

        try {
            $table = \App\Database\TableName::forModel(OrderWorkflowHistory::class);
            $placeholders = implode(', ', array_fill(0, count($orderIds), '?'));
            $sql = 'SELECT order_id, action_note, from_status, to_status, changed_at FROM `' . $this->escapeIdentifier($table) . '` '
                . 'WHERE order_id IN (' . $placeholders . ') AND action_note LIKE ? '
                . 'ORDER BY order_id ASC, order_workflow_history_id ASC';
            \App\Database\QueryGuard::assertReadOnly($sql);
            $statement = $this->pdo->prepare($sql);
            $statement->execute(array_merge($orderIds, ['Imported from%']));
            $indexed = [];
            foreach ($statement->fetchAll(\PDO::FETCH_ASSOC) ?: [] as $row) {
                $oid = (int) ($row['order_id'] ?? 0);
                if ($oid > 0 && !isset($indexed[$oid])) {
                    $indexed[$oid] = $row;
                }
            }

            return $indexed;
        } catch (\Throwable $e) {
            return [];
        }
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
