<?php

namespace App\Repositories\Write;

use App\Models\ReturnReportItem;

class ReturnReportItemWriteRepository extends BaseWriteRepository
{
    public function modelClass(): string
    {
        return ReturnReportItem::class;
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO `' . $this->escapeIdentifier($this->table()) . '` '
            . '(return_report_id, return_receive_id, order_id, manual_order_id, order_reference, product_cost_snapshot, item_count, return_type, return_reason, status, created_at) '
            . 'VALUES (:return_report_id, :return_receive_id, :order_id, :manual_order_id, :order_reference, :product_cost_snapshot, :item_count, :return_type, :return_reason, :status, NOW())';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($data);

        return (int) $this->pdo->lastInsertId();
    }

    public function existsForReturnReceiveId(int $returnReceiveId): bool
    {
        if (!$this->tableExists() || $returnReceiveId <= 0) {
            return false;
        }

        $sql = 'SELECT COUNT(*) AS row_count FROM `' . $this->escapeIdentifier($this->table()) . '` '
            . 'WHERE return_receive_id = :return_receive_id AND status = :status';
        $statement = $this->pdo->prepare($sql);
        $statement->execute([
            'return_receive_id' => $returnReceiveId,
            'status' => 'included',
        ]);
        $row = $statement->fetch(\PDO::FETCH_ASSOC);

        return ((int) ($row['row_count'] ?? 0)) > 0;
    }
}
