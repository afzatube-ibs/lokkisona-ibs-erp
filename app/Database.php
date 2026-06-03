<?php

namespace App;

use PDO;

class Database
{
    public static function connection()
    {
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

        return new PDO(
            $dsn,
            config('database.username', ''),
            config('database.password', ''),
            config('database.options', [])
        );
    }

    public static function check()
    {
        try {
            $pdo = self::connection();
            $pdo->query('SELECT 1');

            return [
                'connected' => true,
                'message' => 'Connected',
                'detail' => config('database.database') . '@' . config('database.host') . ':' . config('database.port'),
            ];
        } catch (\Throwable $e) {
            return [
                'connected' => false,
                'message' => 'Not connected',
                'detail' => 'Configure config/database.php and ensure MySQL is running. ' . $e->getMessage(),
            ];
        }
    }
}
