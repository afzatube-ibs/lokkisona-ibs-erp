<?php

namespace App\Repositories;

use App\Database\QueryGuard;
use App\Database\ReadOnlyQueryException;
use App\Database\TableName;
use App\Models\Order;
use PDO;

class OrderRepository extends BaseReadOnlyRepository
{
    public function modelClass(): string
    {
        return Order::class;
    }

    public function findByStatus(string $status, int $limit = 50): array
    {
        if (!$this->tableExists()) {
            return [];
        }

        try {
            $limit = max(1, min($limit, 50));
            $table = TableName::forModel($this->modelClass());
            $sql = 'SELECT * FROM `' . $this->escapeIdentifier($table) . '` '
                . 'WHERE ibs_status = :status ORDER BY order_id ASC LIMIT ' . $limit;
            QueryGuard::assertReadOnly($sql);
            $statement = $this->pdo->prepare($sql);
            $statement->execute(['status' => $status]);

            return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (ReadOnlyQueryException $e) {
            return [];
        } catch (\Throwable $e) {
            return [];
        }
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
