<?php

namespace App;

use App\Database\Connection;
use PDO;

class Database
{
    public static function connection(): PDO
    {
        return Connection::pdo();
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
