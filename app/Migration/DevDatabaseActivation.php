<?php

namespace App\Migration;

use App\Database;
use App\Database\Connection;
use App\Database\QueryGuard;
use PDO;

/**
 * Dev database activation helper and table verification (v0.4.2.2, v0.4.2.4).
 * Read-only INFORMATION_SCHEMA checks only — no migration apply, no schema changes.
 */
class DevDatabaseActivation
{
    public const PREFIX_MISMATCH_MESSAGE = 'Non-prefixed dev tables detected. ERP expects ibs_* tables. Rename/drop dev test tables or reset dev DB before continuing.';

    public static function tableGroups(): array
    {
        return [
            [
                'key' => 'A',
                'label' => 'Group A — Core/Admin',
                'migration_files' => ['0002_core_users_roles_activity.sql'],
                'required_tables' => [
                    'ibs_users',
                    'ibs_roles',
                    'ibs_user_roles',
                    'ibs_activity_logs',
                ],
                'testable_after' => 'Users, roles, and activity log read inventory on /users, /roles-permissions, and /activity-log.',
                'still_blocked' => 'No user creation, role mutation, or activity log DB writes from admin pages.',
            ],
            [
                'key' => 'B',
                'label' => 'Group B — Supplier/Product Master',
                'migration_files' => ['0003_business_sources_suppliers_products.sql'],
                'required_tables' => [
                    'ibs_businesses',
                    'ibs_business_sources',
                    'ibs_suppliers',
                    'ibs_products',
                    'ibs_product_variants',
                    'ibs_supplier_product_costs',
                    'ibs_product_cost_histories',
                    'ibs_product_stock_histories',
                ],
                'testable_after' => 'Supplier create/edit, business source create/edit, product/variant create/edit, cost/stock change on dev.',
                'still_blocked' => 'No OpenCart/WooCommerce sync, no automatic product import, no production activation.',
            ],
            [
                'key' => 'C',
                'label' => 'Group C — Orders',
                'migration_files' => ['0005_orders_manual_orders_workflow.sql'],
                'required_tables' => [
                    'ibs_orders',
                    'ibs_order_items',
                    'ibs_manual_orders',
                    'ibs_manual_order_items',
                    'ibs_order_workflow_histories',
                ],
                'testable_after' => 'Manual order create and order workflow action tests on dev/staging.',
                'still_blocked' => 'No channel sync/import, no automatic order import, apply Group F launch lock rules before go-live tests.',
            ],
            [
                'key' => 'D',
                'label' => 'Group D — Dispatch/Return/Payable',
                'migration_files' => ['0006_dispatch_returns_payables.sql'],
                'required_tables' => [
                    'ibs_dispatch_reports',
                    'ibs_dispatch_report_items',
                    'ibs_return_receives',
                    'ibs_return_batches',
                    'ibs_return_batch_items',
                    'ibs_payable_ledgers',
                    'ibs_supplier_invoices',
                    'ibs_supplier_payments',
                    'ibs_payable_adjustments',
                ],
                'testable_after' => 'Dispatch report create, dispatch/return/payable read inventory; dispatch write path on dev.',
                'still_blocked' => 'Return receive submit (v0.4.3+), payable settlement (v0.4.4+), no supplier payment writes yet.',
            ],
            [
                'key' => 'E',
                'label' => 'Group E — Invoice/Supplier Tools',
                'migration_files' => ['0007_invoices_printing_supplier_tools.sql'],
                'required_tables' => [
                    'ibs_invoices',
                    'ibs_invoice_items',
                    'ibs_invoice_templates',
                    'ibs_packing_prints',
                    'ibs_print_logs',
                    'ibs_supplier_quick_invoices',
                    'ibs_supplier_quick_invoice_items',
                    'ibs_supplier_quick_invoice_audits',
                ],
                'testable_after' => 'Invoice printing read inventory and supplier tools planning review on dev.',
                'still_blocked' => 'Invoice print persistence (v0.4.5+), no invoice generation, no print log writes from this release.',
            ],
            [
                'key' => 'F',
                'label' => 'Group F — Opening Balance/Launch',
                'migration_files' => ['0008_supplier_opening_balances_launch_cutovers.sql'],
                'required_tables' => [
                    'ibs_supplier_opening_balances',
                    'ibs_supplier_opening_balance_adjustments',
                    'ibs_supplier_opening_balance_audits',
                    'ibs_launch_cutovers',
                ],
                'testable_after' => 'Opening balance create/approve and launch cutover lock on dev/staging.',
                'still_blocked' => 'No production launch until owner completes full dev QA; no live payable seeding on production.',
            ],
        ];
    }

    public static function applyTestFlow(): array
    {
        return [
            'Step 1: Apply Group A + B on dev — owner backup first, manual SQL client only.',
            'Step 2: Run tools/check-local.ps1 and confirm [OK] ALL GREEN.',
            'Step 3: Test supplier create/edit on /suppliers.',
            'Step 4: Test business source create/edit on /business-sources.',
            'Step 5: Test product/variant create/edit and cost/stock change on /product-control.',
            'Step 6: Apply Group F before opening balance and launch cutover tests.',
            'Step 7: Apply Group C before manual order and workflow action tests.',
            'Step 8: Apply Group D before dispatch, payable, and return read/write path tests.',
            'Step 9: Apply Group E before invoice printing and supplier tools read testing.',
        ];
    }

    public static function globalBlocked(): array
    {
        return [
            'Automatic migration apply from any page, build queue, or checkpoint.',
            'CREATE TABLE, ALTER TABLE, DROP TABLE, or automatic schema modification during page load.',
            'OpenCart or WooCommerce connection, sync, or import.',
            'Live production database activation before dev/staging QA is complete.',
            'v0.4.3 Return Receive Submit, v0.4.4 Payable Settlement, v0.4.5 Invoice Print Persistence — not built yet.',
            'Any write form on environments where required tables show Not applied.',
        ];
    }

    public static function activationStatus(): array
    {
        $databaseStatus = Database::check();
        $connected = (bool) ($databaseStatus['connected'] ?? false);
        $groups = self::tableGroupsWithReadiness($connected);

        $totalTables = 0;
        $readyTables = 0;
        $readyGroups = 0;

        foreach ($groups as $group) {
            $totalTables += (int) $group['table_count'];
            $readyTables += (int) $group['ready_count'];
            if ($group['group_status'] === 'Ready') {
                $readyGroups++;
            }
        }

        $overall = 'Unavailable';
        if ($connected) {
            if ($readyGroups === count($groups)) {
                $overall = 'All groups ready';
            } elseif ($readyTables > 0) {
                $overall = 'Partial activation';
            } else {
                $overall = 'Not activated';
            }
        }

        $prefixCheck = self::prefixMismatchCheck();

        return [
            'connected' => $connected,
            'database_message' => $databaseStatus['message'] ?? 'Unknown',
            'database_detail' => $databaseStatus['detail'] ?? '',
            'table_prefix' => config('database.prefix', ''),
            'group_count' => count($groups),
            'ready_groups' => $readyGroups,
            'total_tables' => $totalTables,
            'ready_tables' => $readyTables,
            'overall_status' => $overall,
            'prefix_mismatch' => $prefixCheck['detected'],
            'prefix_mismatch_message' => self::PREFIX_MISMATCH_MESSAGE,
            'prefix_mismatch_tables' => $prefixCheck['tables'],
        ];
    }

    public static function prefixMismatchCheck(): array
    {
        $connected = (bool) (Database::check()['connected'] ?? false);
        if (!$connected) {
            return ['detected' => false, 'tables' => []];
        }

        try {
            $pdo = Connection::pdo();
        } catch (\Throwable $e) {
            return ['detected' => false, 'tables' => []];
        }

        $prefix = (string) config('database.prefix', '');
        $mismatches = [];

        foreach (self::allRequiredLogicalTableNames() as $logical) {
            $expected = $prefix . $logical;
            $prefixedExists = self::physicalTableExists($pdo, $expected);
            $unprefixedExists = $logical !== $expected && self::physicalTableExists($pdo, $logical);

            if (!$prefixedExists && $unprefixedExists) {
                $mismatches[] = [
                    'logical' => $logical,
                    'found' => $logical,
                    'expected' => $expected,
                ];
            }
        }

        return [
            'detected' => $mismatches !== [],
            'tables' => $mismatches,
        ];
    }

    public static function allRequiredLogicalTableNames(): array
    {
        $prefix = (string) config('database.prefix', '');
        $names = [];

        foreach (self::tableGroups() as $group) {
            foreach ($group['required_tables'] as $physical) {
                if ($prefix !== '' && str_starts_with($physical, $prefix)) {
                    $names[] = substr($physical, strlen($prefix));
                } else {
                    $names[] = $physical;
                }
            }
        }

        return array_values(array_unique($names));
    }

    public static function tableGroupsWithReadiness(?bool $connected = null): array
    {
        if ($connected === null) {
            $connected = (bool) (Database::check()['connected'] ?? false);
        }

        $pdo = null;
        if ($connected) {
            try {
                $pdo = Connection::pdo();
            } catch (\Throwable $e) {
                $connected = false;
            }
        }

        $result = [];
        foreach (self::tableGroups() as $group) {
            $tables = [];
            $readyCount = 0;

            foreach ($group['required_tables'] as $physicalTable) {
                $exists = false;
                $status = 'Unavailable';

                if (!$connected || !$pdo instanceof PDO) {
                    $status = 'Unavailable';
                } elseif (self::physicalTableExists($pdo, $physicalTable)) {
                    $exists = true;
                    $status = 'Ready';
                    $readyCount++;
                } else {
                    $status = 'Not applied';
                }

                $tables[] = [
                    'table' => $physicalTable,
                    'exists' => $exists,
                    'status' => $status,
                ];
            }

            $tableCount = count($group['required_tables']);
            $groupStatus = 'Unavailable';
            if ($connected) {
                if ($readyCount === $tableCount) {
                    $groupStatus = 'Ready';
                } elseif ($readyCount > 0) {
                    $groupStatus = 'Partial';
                } else {
                    $groupStatus = 'Not applied';
                }
            }

            $result[] = array_merge($group, [
                'tables' => $tables,
                'table_count' => $tableCount,
                'ready_count' => $readyCount,
                'group_status' => $groupStatus,
            ]);
        }

        return $result;
    }

    public static function physicalTableExists(PDO $pdo, string $physicalTable): bool
    {
        try {
            $database = config('database.database', '');
            $sql = 'SELECT COUNT(*) AS table_count FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = :table';
            QueryGuard::assertReadOnly($sql);

            $statement = $pdo->prepare($sql);
            $statement->execute([
                'schema' => $database,
                'table' => $physicalTable,
            ]);
            $row = $statement->fetch(PDO::FETCH_ASSOC);

            return ((int) ($row['table_count'] ?? 0)) > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
