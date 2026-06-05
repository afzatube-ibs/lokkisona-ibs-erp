<?php

namespace App\Repositories;

use App\Database\QueryGuard;
use App\Database\ReadOnlyQueryException;
use App\Database\TableName;
use App\Models\ProductCostHistory;
use PDO;

class ProductCostHistoryRepository extends BaseReadOnlyRepository
{
    public function modelClass(): string
    {
        return ProductCostHistory::class;
    }

    public function latest(int $limit = 50): array
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