<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository extends BaseReadOnlyRepository
{
    public function modelClass(): string
    {
        return User::class;
    }

    public function findByUsername(string $username): ?array
    {
        if (!$this->tableExists() || $username === '') {
            return null;
        }

        try {
            $table = $this->escapeIdentifier(\App\Database\TableName::forModel(User::class));
            $sql = 'SELECT * FROM `' . $table . '` WHERE username = :username LIMIT 1';
            \App\Database\QueryGuard::assertReadOnly($sql);
            $statement = $this->pdo->prepare($sql);
            $statement->execute(['username' => $username]);
            $row = $statement->fetch(\PDO::FETCH_ASSOC);

            return $row === false ? null : $row;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
