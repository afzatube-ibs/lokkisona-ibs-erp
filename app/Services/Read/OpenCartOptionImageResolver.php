<?php

namespace App\Services\Read;

use App\Database\QueryGuard;
use PDO;

/**
 * Resolve OpenCart option/variant images from API payloads and read-only OpenCart DB (POIP / Improved Options).
 */
class OpenCartOptionImageResolver
{
    private const PAYLOAD_KEYS = [
        'option_image_path',
        'option_image',
        'image',
        'optionimage',
        'optionImage',
        'thumb',
    ];

    private const EXTENSION_TABLES = [
        'poip_product_option_value',
        'product_option_value_image',
        'improved_option_value',
    ];

    private const IMAGE_COLUMNS = ['image', 'optionimage', 'option_image'];

    private ?PDO $pdo = null;

    private bool $dbUnavailable = false;

    /** @var array<int, string> */
    private array $cache = [];

    /** @var array<string, mixed>|null|null */
    private $queryParts = null;

    public static function extractFromPayload(array $option): ?string
    {
        foreach (self::PAYLOAD_KEYS as $key) {
            if (!array_key_exists($key, $option)) {
                continue;
            }

            $value = trim((string) $option[$key]);
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    /**
     * @param array<int, array<string, mixed>> $options
     * @return array<int, array<string, mixed>>
     */
    public function enrichOptions(array $options): array
    {
        if ($options === []) {
            return $options;
        }

        $missingIds = [];
        foreach ($options as $option) {
            if (!is_array($option) || self::extractFromPayload($option) !== null) {
                continue;
            }

            $valueId = $this->normalizeValueId($option['source_option_value_id'] ?? $option['product_option_value_id'] ?? '');
            if ($valueId > 0) {
                $missingIds[] = $valueId;
            }
        }

        $resolved = $missingIds !== [] ? $this->resolveForValueIds($missingIds) : [];
        if ($resolved === []) {
            return $options;
        }

        foreach ($options as $index => $option) {
            if (!is_array($option) || self::extractFromPayload($option) !== null) {
                continue;
            }

            $valueId = $this->normalizeValueId($option['source_option_value_id'] ?? $option['product_option_value_id'] ?? '');
            if ($valueId > 0 && isset($resolved[$valueId])) {
                $options[$index]['option_image_path'] = $resolved[$valueId];
            }
        }

        return $options;
    }

    /**
     * @param array<int, int|string> $valueIds
     * @return array<int, string> product_option_value_id => image path
     */
    public function resolveForValueIds(array $valueIds): array
    {
        $ids = [];
        foreach ($valueIds as $valueId) {
            $normalized = $this->normalizeValueId($valueId);
            if ($normalized > 0) {
                $ids[$normalized] = $normalized;
            }
        }

        if ($ids === []) {
            return [];
        }

        $resolved = [];
        $missing = [];
        foreach ($ids as $id) {
            if (array_key_exists($id, $this->cache)) {
                if ($this->cache[$id] !== '') {
                    $resolved[$id] = $this->cache[$id];
                }
                continue;
            }
            $missing[] = $id;
        }

        if ($missing !== []) {
            $this->resolveFromDatabase($missing);
        }

        foreach ($ids as $id) {
            if (isset($this->cache[$id]) && $this->cache[$id] !== '') {
                $resolved[$id] = $this->cache[$id];
            }
        }

        return $resolved;
    }

    public function databaseEnrichmentEnabled(): bool
    {
        if (!(bool) config('opencart.option_image_db_enrichment', true)) {
            return false;
        }

        return trim((string) config('opencart.db.host', '')) !== ''
            && trim((string) config('opencart.db.database', '')) !== ''
            && trim((string) config('opencart.db.username', '')) !== '';
    }

    private function normalizeValueId($valueId): int
    {
        $valueId = trim((string) $valueId);

        return ($valueId !== '' && ctype_digit($valueId)) ? (int) $valueId : 0;
    }

  /**
     * @param array<int, int> $valueIds
     */
    private function resolveFromDatabase(array $valueIds): void
    {
        $pdo = $this->pdo();
        if ($pdo === null) {
            foreach ($valueIds as $id) {
                $this->cache[$id] = '';
            }

            return;
        }

        $parts = $this->detectQueryParts($pdo);
        if ($parts === null) {
            foreach ($valueIds as $id) {
                $this->cache[$id] = '';
            }

            return;
        }

        $idList = implode(',', array_map('intval', $valueIds));
        $sql = 'SELECT pov.product_option_value_id, ' . $parts['select'] . ' AS option_image '
            . ' FROM ' . $parts['from'] . ' ' . $parts['joins']
            . ' WHERE pov.product_option_value_id IN (' . $idList . ')';

        try {
            QueryGuard::assertReadOnly($sql);
            $statement = $pdo->query($sql);
            $found = [];
            while ($row = $statement->fetch()) {
                $id = (int) ($row['product_option_value_id'] ?? 0);
                $path = trim((string) ($row['option_image'] ?? ''));
                if ($id > 0) {
                    $found[$id] = $path;
                }
            }

            foreach ($valueIds as $id) {
                $this->cache[$id] = trim((string) ($found[$id] ?? ''));
            }
        } catch (\Throwable $e) {
            foreach ($valueIds as $id) {
                $this->cache[$id] = '';
            }
        }
    }

    private function pdo(): ?PDO
    {
        if ($this->dbUnavailable) {
            return null;
        }

        if ($this->pdo !== null) {
            return $this->pdo;
        }

        if (!$this->databaseEnrichmentEnabled()) {
            $this->dbUnavailable = true;

            return null;
        }

        try {
            $this->pdo = new PDO(
                'mysql:host=' . (string) config('opencart.db.host', '')
                    . ';dbname=' . (string) config('opencart.db.database', '')
                    . ';charset=utf8mb4',
                (string) config('opencart.db.username', ''),
                (string) config('opencart.db.password', ''),
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
            );
        } catch (\Throwable $e) {
            $this->dbUnavailable = true;
            $this->pdo = null;
        }

        return $this->pdo;
    }

    /**
     * @return array{select: string, joins: string, from: string}|null
     */
    private function detectQueryParts(PDO $pdo): ?array
    {
        if ($this->queryParts !== null) {
            return $this->queryParts === [] ? null : $this->queryParts;
        }

        $prefix = (string) config('opencart.db.prefix', 'oc_');
        $povTable = $prefix . 'product_option_value';
        $selectParts = [];

        foreach (self::IMAGE_COLUMNS as $column) {
            if ($this->columnExists($pdo, (string) config('opencart.db.database', ''), $povTable, $column)) {
                $selectParts[] = "NULLIF(pov.`{$column}`, '')";
                break;
            }
        }

        $joins = '';
        $joinIndex = 0;
        foreach (self::EXTENSION_TABLES as $tableSuffix) {
            $table = $prefix . $tableSuffix;
            if (!$this->tableExists($pdo, (string) config('opencart.db.database', ''), $table)) {
                continue;
            }
            if (!$this->columnExists($pdo, (string) config('opencart.db.database', ''), $table, 'product_option_value_id')) {
                continue;
            }

            $imageColumn = '';
            foreach (self::IMAGE_COLUMNS as $column) {
                if ($this->columnExists($pdo, (string) config('opencart.db.database', ''), $table, $column)) {
                    $imageColumn = $column;
                    break;
                }
            }
            if ($imageColumn === '') {
                continue;
            }

            $alias = 'oimg' . $joinIndex;
            $joinIndex++;
            $joins .= ' LEFT JOIN `' . $table . '` ' . $alias
                . ' ON ' . $alias . '.product_option_value_id = pov.product_option_value_id ';
            $selectParts[] = 'NULLIF(' . $alias . '.`' . $imageColumn . '`, \'\')';
        }

        if ($selectParts === []) {
            $this->queryParts = [];

            return null;
        }

        $this->queryParts = [
            'select' => 'COALESCE(' . implode(', ', $selectParts) . ')',
            'joins' => $joins,
            'from' => '`' . $povTable . '` pov',
        ];

        return $this->queryParts;
    }

    private function tableExists(PDO $pdo, string $schema, string $table): bool
    {
        $sql = 'SELECT COUNT(*) AS table_count FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = :table';
        QueryGuard::assertReadOnly($sql);
        $statement = $pdo->prepare($sql);
        $statement->execute(['schema' => $schema, 'table' => $table]);
        $row = $statement->fetch();

        return ((int) ($row['table_count'] ?? 0)) > 0;
    }

    private function columnExists(PDO $pdo, string $schema, string $table, string $column): bool
    {
        $sql = 'SELECT COUNT(*) AS column_count FROM INFORMATION_SCHEMA.COLUMNS '
            . 'WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = :table AND COLUMN_NAME = :column';
        QueryGuard::assertReadOnly($sql);
        $statement = $pdo->prepare($sql);
        $statement->execute(['schema' => $schema, 'table' => $table, 'column' => $column]);
        $row = $statement->fetch();

        return ((int) ($row['column_count'] ?? 0)) > 0;
    }
}
