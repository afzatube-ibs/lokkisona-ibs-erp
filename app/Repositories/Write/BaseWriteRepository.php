<?php

namespace App\Repositories\Write;

use App\Database\Connection;
use App\Database\TableName;
use PDO;

abstract class BaseWriteRepository
{
    protected PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Connection::pdo();
    }

    abstract public function modelClass(): string;

    public function tableExists(): bool
    {
        try {
            $database = config('database.database', '');
            $table = TableName::forModel($this->modelClass());
            $sql = 'SELECT COUNT(*) AS table_count FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = :table';
            $statement = $this->pdo->prepare($sql);
            $statement->execute(['schema' => $database, 'table' => $table]);
            $row = $statement->fetch(PDO::FETCH_ASSOC);

            return ((int) ($row['table_count'] ?? 0)) > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function table(): string
    {
        return TableName::forModel($this->modelClass());
    }

    protected function escapeIdentifier(string $identifier): string
    {
        return str_replace('`', '``', $identifier);
    }

    protected function findById(int $id): ?array
    {
        if (!$this->tableExists()) {
            return null;
        }

        $modelClass = $this->modelClass();
        $primaryKey = $modelClass::primaryKey();
        $sql = 'SELECT * FROM `' . $this->escapeIdentifier($this->table()) . '` WHERE `' . $this->escapeIdentifier($primaryKey) . '` = :id LIMIT 1';
        $statement = $this->pdo->prepare($sql);
        $statement->execute(['id' => $id]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }
}
