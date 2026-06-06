<?php

namespace App\Repositories\Write;

use App\Models\DispatchReportItem;

class DispatchReportItemWriteRepository extends BaseWriteRepository
{
    public function modelClass(): string
    {
        return DispatchReportItem::class;
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO `' . $this->escapeIdentifier($this->table()) . '` '
            . '(dispatch_report_id, order_id, manual_order_id, order_reference, product_cost_snapshot, item_count, status, created_at) '
            . 'VALUES (:dispatch_report_id, :order_id, :manual_order_id, :order_reference, :product_cost_snapshot, :item_count, :status, NOW())';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($data);

        return (int) $this->pdo->lastInsertId();
    }

    public function existsForOrderId(int $orderId): bool
    {
        if (!$this->tableExists() || $orderId <= 0) {
            return false;
        }

        $sql = 'SELECT COUNT(*) AS row_count FROM `' . $this->escapeIdentifier($this->table()) . '` '
            . 'WHERE order_id = :order_id AND status = :status';
        $statement = $this->pdo->prepare($sql);
        $statement->execute([
            'order_id' => $orderId,
            'status' => 'included',
        ]);
        $row = $statement->fetch(\PDO::FETCH_ASSOC);

        return ((int) ($row['row_count'] ?? 0)) > 0;
    }
}
