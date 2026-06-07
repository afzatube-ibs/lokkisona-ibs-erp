<?php

namespace App\Repositories;

use App\Database\QueryGuard;
use App\Database\ReadOnlyQueryException;
use App\Database\TableName;
use App\Models\OrderItem;
use PDO;

class OrderItemRepository extends BaseReadOnlyRepository
{
    public function modelClass(): string
    {
        return OrderItem::class;
    }

    /**
     * @param array<int, int> $orderIds
     * @return array<int, array<int, array<string, mixed>>>
     */
    public function groupedByOrderIds(array $orderIds): array
    {
        $orderIds = array_values(array_filter(array_map('intval', $orderIds), static fn (int $id): bool => $id > 0));
        if (!$this->tableExists() || $orderIds === []) {
            return [];
        }

        try {
            $table = TableName::forModel($this->modelClass());
            $placeholders = implode(', ', array_fill(0, count($orderIds), '?'));
            $sql = 'SELECT * FROM `' . $this->escapeIdentifier($table) . '` WHERE order_id IN (' . $placeholders . ') ORDER BY order_id ASC, order_item_id ASC';
            QueryGuard::assertReadOnly($sql);
            $statement = $this->pdo->prepare($sql);
            $statement->execute($orderIds);
            $grouped = [];
            foreach ($statement->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
                $oid = (int) ($row['order_id'] ?? 0);
                if ($oid > 0) {
                    $grouped[$oid][] = $row;
                }
            }

            return $grouped;
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function findByOrderId(int $orderId): array
    {
        if (!$this->tableExists() || $orderId <= 0) {
            return [];
        }

        try {
            $table = TableName::forModel($this->modelClass());
            $sql = 'SELECT * FROM `' . $this->escapeIdentifier($table) . '` WHERE order_id = :order_id ORDER BY order_item_id ASC';
            QueryGuard::assertReadOnly($sql);
            $statement = $this->pdo->prepare($sql);
            $statement->execute(['order_id' => $orderId]);

            return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function sumSupplierCostByOrderId(int $orderId): float
    {
        $total = 0.0;
        foreach ($this->findByOrderId($orderId) as $row) {
            $qty = (int) ($row['quantity'] ?? 0);
            if ($qty < 1) {
                $qty = 1;
            }
            $total += (float) ($row['supplier_cost_snapshot'] ?? 0) * $qty;
        }

        return round($total, 2);
    }

    public function sumQuantityByOrderId(int $orderId): int
    {
        $total = 0;
        foreach ($this->findByOrderId($orderId) as $row) {
            $qty = (int) ($row['quantity'] ?? 0);
            $total += $qty > 0 ? $qty : 1;
        }

        return $total;
    }

    public function latest(int $limit = 20): array
    {
        if (!$this->tableExists()) {
            return [];
        }

        try {
            $limit = max(1, min($limit, 100));
            $modelClass = $this->modelClass();
            $table = TableName::forModel($modelClass);
            $primaryKey = $modelClass::primaryKey();

            $sql = 'SELECT * FROM `' . $this->escapeIdentifier($table) . '` ORDER BY created_at DESC, `' . $this->escapeIdentifier($primaryKey) . '` DESC LIMIT ' . $limit;
            QueryGuard::assertReadOnly($sql);

            $statement = $this->pdo->query($sql);

            return $statement ? $statement->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (ReadOnlyQueryException $e) {
            return [];
        } catch (\Throwable $e) {
            return [];
        }
    }
}
