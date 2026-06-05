<?php

namespace App\Models;

/**
 * Metadata-only base for the model contract layer.
 *
 * Models in this namespace are pure database contracts: they declare the target
 * table name, the ordered column list that mirrors the manual migration drafts in
 * database/migrations, and an explicit primary key. They intentionally contain no
 * PDO/connection, no query building, and no save/find/create/update/delete logic.
 * Writes are owned by a future service layer, never by these classes.
 */
abstract class BaseModel
{
    const TABLE = '';

    const PRIMARY_KEY = null;

    public static array $columns = [];

    public static function table(): string
    {
        return static::TABLE;
    }

    public static function columns(): array
    {
        return static::$columns;
    }

    public static function primaryKey(): string
    {
        if (static::PRIMARY_KEY !== null) {
            return static::PRIMARY_KEY;
        }

        return static::$columns[0] ?? '';
    }

    public static function hasColumn(string $column): bool
    {
        return in_array($column, static::$columns, true);
    }
}
