<?php

namespace App\Database;

use PDO;

class Connection
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $driver = config('database.driver', 'mysql');
        $host = config('database.host', '127.0.0.1');
        $port = config('database.port', 3306);
        $database = config('database.database', '');
        $charset = config('database.charset', 'utf8mb4');

        $dsn = sprintf(
            '%s:host=%s;port=%d;dbname=%s;charset=%s',
            $driver,
            $host,
            (int) $port,
            $database,
            $charset
        );

        self::$pdo = new PDO(
            $dsn,
            config('database.username', ''),
            config('database.password', ''),
            config('database.options', [])
        );

        return self::$pdo;
    }

    public static function reset(): void
    {
        self::$pdo = null;
    }
}
