<?php

namespace App\Services\Read;

use App\Database\QueryGuard;
use PDO;

/**
 * Read-only OpenCart extension table probe (v1.7.1). No schema changes.
 */
class OpenCartSchemaProbe
{
    public const BRIDGE_WARNING = 'Dispatch Location bridge not found. Product sync cannot safely identify IBS supplier products.';

    public function probeExtensions(): array
    {
        if (!(bool) config('opencart.db_readonly_allowed', false)) {
            return $this->emptyProbe('db_readonly_not_allowed');
        }

        $host = trim((string) config('opencart.db.host', ''));
        $database = trim((string) config('opencart.db.database', ''));
        $username = trim((string) config('opencart.db.username', ''));
        if ($host === '' || $database === '' || $username === '') {
            return $this->emptyProbe('db_not_configured');
        }

        try {
            $pdo = $this->connectReadOnly();
            $prefix = (string) config('opencart.db.prefix', 'oc_');
            $bridgeTable = $prefix . (string) config('opencart.dispatch_location_bridge_table', 'dispatch_location_product');
            $bridgeAvailable = $this->tableExists($pdo, $database, $bridgeTable);

            $detected = [];
            foreach ((array) config('opencart.extension_table_probe', []) as $tableSuffix) {
                $tableSuffix = trim((string) $tableSuffix);
                if ($tableSuffix === '') {
                    continue;
                }
                $physical = $prefix . $tableSuffix;
                if ($this->tableExists($pdo, $database, $physical)) {
                    $detected[] = $physical;
                }
            }

            return [
                'mode' => 'db_readonly',
                'bridge_available' => $bridgeAvailable,
                'bridge_table' => $bridgeTable,
                'bridge_warning' => $bridgeAvailable ? null : self::BRIDGE_WARNING,
                'poip_detected' => $this->matchesAny($detected, ['poip', 'option_value_image']),
                'improved_options_detected' => $this->matchesAny($detected, ['improved_option']),
                'related_options_detected' => $this->matchesAny($detected, ['related_option', 'relatedoptions']),
                'detected_tables' => $detected,
                'related_options_note' => 'Related Options exact combinations are detected only — not imported in v1.7.1.',
            ];
        } catch (\Throwable $e) {
            return $this->emptyProbe('probe_failed');
        }
    }

    private function emptyProbe(string $reason): array
    {
        return [
            'mode' => 'off',
            'reason' => $reason,
            'bridge_available' => null,
            'bridge_table' => null,
            'bridge_warning' => null,
            'poip_detected' => false,
            'improved_options_detected' => false,
            'related_options_detected' => false,
            'detected_tables' => [],
            'related_options_note' => 'Related Options exact combinations are detected only — not imported in v1.7.1.',
        ];
    }

    private function connectReadOnly(): PDO
    {
        $host = (string) config('opencart.db.host', '');
        $database = (string) config('opencart.db.database', '');
        $username = (string) config('opencart.db.username', '');
        $password = (string) config('opencart.db.password', '');

        return new PDO(
            'mysql:host=' . $host . ';dbname=' . $database . ';charset=utf8mb4',
            $username,
            $password,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
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

    private function matchesAny(array $tables, array $needles): bool
    {
        foreach ($tables as $table) {
            $lower = strtolower((string) $table);
            foreach ($needles as $needle) {
                if (str_contains($lower, strtolower($needle))) {
                    return true;
                }
            }
        }

        return false;
    }
}
