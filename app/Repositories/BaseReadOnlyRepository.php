<?php

namespace App\Repositories;

use App\Database\Connection;
use App\Database\QueryGuard;
use App\Database\ReadOnlyQueryException;
use App\Database\TableName;
use PDO;

abstract class BaseReadOnlyRepository implements ReadOnlyRepository
{
    protected PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Connection::pdo();
    }

    abstract public function modelClass(): string;

    public function logicalTable(): string
    {
        return $this->modelClass()::table();
    }

    public function tableExists(): bool
    {
        try {
            $database = config('database.database', '');
            $table = TableName::forModel($this->modelClass());

            $sql = 'SELECT COUNT(*) AS table_count FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = :table';
            QueryGuard::assertReadOnly($sql);

            $statement = $this->pdo->prepare($sql);
            $statement->execute([
                'schema' => $database,
                'table' => $table,
            ]);
            $row = $statement->fetch(PDO::FETCH_ASSOC);

            return ((int) ($row['table_count'] ?? 0)) > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function findById(int $id): ?array
    {
        if (!$this->tableExists()) {
            return null;
        }

        try {
            $modelClass = $this->modelClass();
            $table = TableName::forModel($modelClass);
            $primaryKey = $modelClass::primaryKey();

            $sql = 'SELECT * FROM `' . $this->escapeIdentifier($table) . '` WHERE `' . $this->escapeIdentifier($primaryKey) . '` = :id LIMIT 1';
            QueryGuard::assertReadOnly($sql);

            $statement = $this->pdo->prepare($sql);
            $statement->execute(['id' => $id]);
            $row = $statement->fetch(PDO::FETCH_ASSOC);

            return $row === false ? null : $row;
        } catch (ReadOnlyQueryException $e) {
            return null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function all(int $limit = 100, int $offset = 0): array
    {
        if (!$this->tableExists()) {
            return [];
        }

        try {
            $modelClass = $this->modelClass();
            $table = TableName::forModel($modelClass);
            $primaryKey = $modelClass::primaryKey();
            $limit = max(1, min($limit, 500));
            $offset = max(0, $offset);

            $sql = 'SELECT * FROM `' . $this->escapeIdentifier($table) . '` ORDER BY `' . $this->escapeIdentifier($primaryKey) . '` ASC LIMIT ' . $limit . ' OFFSET ' . $offset;
            QueryGuard::assertReadOnly($sql);

            $statement = $this->pdo->query($sql);

            return $statement ? $statement->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (ReadOnlyQueryException $e) {
            return [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function count(): int
    {
        if (!$this->tableExists()) {
            return 0;
        }

        try {
            $table = TableName::forModel($this->modelClass());
            $sql = 'SELECT COUNT(*) AS row_count FROM `' . $this->escapeIdentifier($table) . '`';
            QueryGuard::assertReadOnly($sql);

            $statement = $this->pdo->query($sql);
            $row = $statement ? $statement->fetch(PDO::FETCH_ASSOC) : null;

            return (int) ($row['row_count'] ?? 0);
        } catch (ReadOnlyQueryException $e) {
            return 0;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    protected function escapeIdentifier(string $identifier): string
    {
        return str_replace('`', '``', $identifier);
    }
}
