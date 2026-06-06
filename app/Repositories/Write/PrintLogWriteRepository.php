<?php

namespace App\Repositories\Write;

use App\Models\PrintLog;

class PrintLogWriteRepository extends BaseWriteRepository
{
    public function modelClass(): string
    {
        return PrintLog::class;
    }

    public function append(array $data): int
    {
        $sql = 'INSERT INTO `' . $this->escapeIdentifier($this->table()) . '` '
            . '(print_reference, printable_type, printable_id, action, user_id, route_path, context_json, created_at) '
            . 'VALUES (:print_reference, :printable_type, :printable_id, :action, :user_id, :route_path, :context_json, NOW())';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($data);

        return (int) $this->pdo->lastInsertId();
    }
}
