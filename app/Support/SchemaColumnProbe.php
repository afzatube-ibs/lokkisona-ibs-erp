<?php

namespace App\Support;

use App\Database\Connection;
use PDO;

/**
 * Read-only INFORMATION_SCHEMA column probe (no schema changes).
 */
class SchemaColumnProbe
{
    /** @var array<string, bool> */
    private static array $cache = [];

    public static function tableHasColumn(string $table, string $column, ?PDO $pdo = null): bool
    {
        $table = trim($table);
        $column = trim($column);
        if ($table === '' || $column === '') {
            return false;
        }

        $key = $table . '.' . $column;
        if (array_key_exists($key, self::$cache)) {
            return self::$cache[$key];
        }

        try {
            $pdo = $pdo ?? Connection::pdo();
            $sql = 'SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.COLUMNS '
                . 'WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = :table AND COLUMN_NAME = :column';
            $statement = $pdo->prepare($sql);
            $statement->execute([
                'schema' => config('database.database', ''),
                'table' => $table,
                'column' => $column,
            ]);
            $row = $statement->fetch(PDO::FETCH_ASSOC);
            self::$cache[$key] = ((int) ($row['c'] ?? 0)) > 0;
        } catch (\Throwable $e) {
            self::$cache[$key] = false;
        }

        return self::$cache[$key];
    }
}
