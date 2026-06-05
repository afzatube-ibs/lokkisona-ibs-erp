<?php

namespace App\Database;

use App\Models\BaseModel;

class TableName
{
    public static function prefix(): string
    {
        return (string) config('database.prefix', '');
    }

    public static function forTable(string $logicalTable): string
    {
        return self::prefix() . $logicalTable;
    }

    public static function forModel(string $modelClass): string
    {
        if (!is_subclass_of($modelClass, BaseModel::class)) {
            throw new \InvalidArgumentException('Expected a BaseModel subclass.');
        }

        return self::forTable($modelClass::table());
    }
}
