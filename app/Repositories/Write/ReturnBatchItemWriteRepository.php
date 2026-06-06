<?php

namespace App\Repositories\Write;

use App\Models\ReturnBatchItem;

class ReturnBatchItemWriteRepository extends BaseWriteRepository
{
    public function modelClass(): string
    {
        return ReturnBatchItem::class;
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO `' . $this->escapeIdentifier($this->table()) . '` '
            . '(return_batch_id, return_receive_id, order_id, manual_order_id, product_id, product_variant_id, quantity, cost_snapshot, adjustment_amount, status, created_at) '
            . 'VALUES (:return_batch_id, :return_receive_id, :order_id, :manual_order_id, :product_id, :product_variant_id, :quantity, :cost_snapshot, :adjustment_amount, :status, NOW())';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($data);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Batch items joined to their return-receive header (for batch detail display).
     *
     * @return array<int, array<string, mixed>>
     */
    public function listForBatch(int $returnBatchId): array
    {
        if (!$this->tableExists() || $returnBatchId <= 0) {
            return [];
        }

        $receivesTable = config('database.prefix', 'ibs_') . 'return_receives';
        $sql = 'SELECT bi.*, r.return_reference, r.return_type, r.total_items AS receive_total_items, '
            . 'r.total_cost_snapshot AS receive_cost_snapshot '
            . 'FROM `' . $this->escapeIdentifier($this->table()) . '` bi '
            . 'LEFT JOIN `' . $this->escapeIdentifier($receivesTable) . '` r ON r.return_receive_id = bi.return_receive_id '
            . 'WHERE bi.return_batch_id = :batch_id ORDER BY bi.return_batch_item_id ASC';
        $statement = $this->pdo->prepare($sql);
        $statement->execute(['batch_id' => $returnBatchId]);

        return $statement->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }
}
