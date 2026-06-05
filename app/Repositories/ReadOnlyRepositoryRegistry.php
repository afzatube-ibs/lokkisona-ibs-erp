<?php

namespace App\Repositories;

/**
 * Read-only inventory of repository classes for planning and database safety display.
 * Performs no writes and opens no database connection by itself.
 */
class ReadOnlyRepositoryRegistry
{
    private static array $map = [
        'users' => UserRepository::class,
        'roles' => RoleRepository::class,
        'activity_logs' => ActivityLogRepository::class,
        'businesses' => BusinessRepository::class,
        'business_sources' => BusinessSourceRepository::class,
        'suppliers' => SupplierRepository::class,
        'products' => ProductRepository::class,
        'product_variants' => ProductVariantRepository::class,
        'supplier_opening_balances' => SupplierOpeningBalanceRepository::class,
        'launch_cutovers' => LaunchCutoverRepository::class,
        'orders' => OrderRepository::class,
        'order_items' => OrderItemRepository::class,
        'order_workflow_histories' => OrderWorkflowHistoryRepository::class,
        'dispatch_reports' => DispatchReportRepository::class,
        'return_receives' => ReturnReceiveRepository::class,
        'payable_ledgers' => PayableLedgerRepository::class,
        'invoices' => InvoiceRepository::class,
    ];

    public static function all(): array
    {
        return self::$map;
    }

    public static function tables(): array
    {
        return array_keys(self::$map);
    }

    public static function find(string $table): ?string
    {
        return self::$map[$table] ?? null;
    }

    public static function has(string $table): bool
    {
        return isset(self::$map[$table]);
    }

    public static function instances(): array
    {
        $instances = [];

        foreach (self::$map as $table => $class) {
            $instances[$table] = new $class();
        }

        return $instances;
    }

    public static function statusSummary(): array
    {
        $summary = [];

        foreach (self::instances() as $table => $repository) {
            $exists = false;
            $rowCount = 0;

            try {
                $exists = $repository->tableExists();
                if ($exists) {
                    $rowCount = $repository->count();
                }
            } catch (\Throwable $e) {
                $exists = false;
                $rowCount = 0;
            }

            $summary[] = [
                'logical_table' => $table,
                'prefixed_table' => \App\Database\TableName::forTable($table),
                'repository_class' => get_class($repository),
                'model_class' => $repository->modelClass(),
                'table_exists' => $exists,
                'row_count' => $rowCount,
            ];
        }

        return $summary;
    }
}
