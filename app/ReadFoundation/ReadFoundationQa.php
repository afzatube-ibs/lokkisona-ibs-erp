<?php

namespace App\ReadFoundation;

use App\Models\ModelRegistry;
use App\Repositories\ReadOnlyRepositoryRegistry;

/**
 * Read foundation QA matrix and real-data readiness gate (v0.2.9).
 * Performs no writes and no migration apply.
 */
class ReadFoundationQa
{
    public static function modulePages(): array
    {
        return [
            ['route' => '/suppliers', 'tables' => ['suppliers'], 'migration' => '0003'],
            ['route' => '/business-sources', 'tables' => ['business_sources', 'businesses'], 'migration' => '0003'],
            ['route' => '/product-control', 'tables' => ['products', 'product_variants'], 'migration' => '0003'],
            ['route' => '/supplier-opening-balances', 'tables' => ['supplier_opening_balances', 'launch_cutovers'], 'migration' => '0008'],
            ['route' => '/order-workflow', 'tables' => ['orders', 'order_items', 'order_workflow_histories'], 'migration' => '0005'],
            ['route' => '/dispatch-reports', 'tables' => ['dispatch_reports'], 'migration' => '0006'],
            ['route' => '/return-receive', 'tables' => ['return_receives'], 'migration' => '0006'],
            ['route' => '/supplier-payables', 'tables' => ['payable_ledgers'], 'migration' => '0006'],
            ['route' => '/invoice-printing', 'tables' => ['invoices'], 'migration' => '0007'],
            ['route' => '/activity-log', 'tables' => ['activity_logs'], 'migration' => '0002'],
            ['route' => '/users', 'tables' => ['users'], 'migration' => '0002'],
            ['route' => '/roles-permissions', 'tables' => ['roles'], 'migration' => '0002'],
        ];
    }

    public static function modelPendingTables(): array
    {
        $planned = [
            'businesses',
            'supplier_product_costs',
            'product_stock_histories',
            'product_cost_histories',
            'manual_orders',
            'manual_order_items',
            'manual_order_audits',
            'dispatch_report_items',
            'return_batches',
            'return_batch_items',
            'payable_adjustments',
            'supplier_payments',
            'invoice_items',
            'supplier_opening_balance_adjustments',
            'supplier_opening_balance_audits',
        ];

        $withModel = ModelRegistry::tables();
        $withRepo = ReadOnlyRepositoryRegistry::tables();

        $pending = [];
        foreach ($planned as $table) {
            $pending[] = [
                'table' => $table,
                'has_model' => in_array($table, $withModel, true),
                'has_read_repo' => in_array($table, $withRepo, true),
                'planned_version' => self::plannedVersionForTable($table),
            ];
        }

        return $pending;
    }

    public static function readinessChecklist(): array
    {
        return [
            'Run tools/check-local.ps1 and confirm [OK] ALL GREEN before any manual migration apply.',
            'Review migration drafts 0002 through 0008 under database/migrations/ with owner approval.',
            'Back up the database before applying any migration SQL manually.',
            'Apply minimum activation set 0002 + 0003 on dev/staging before v0.3.1 supplier create tests.',
            'Verify read inventory on /suppliers and /business-sources shows tables after 0003 apply.',
            'Do not start v0.3.1 write services until read QA gate (v0.2.9) is green and Git is synced.',
            'Write-path whitelist: mutation SQL allowed only in app/Services/Write/ and app/Repositories/Write/ from v0.3.1 onward.',
        ];
    }

    public static function coverageSummary(): array
    {
        $registryCount = count(ReadOnlyRepositoryRegistry::all());
        $modelCount = count(ModelRegistry::all());

        return [
            'read_repositories' => $registryCount,
            'model_contracts' => $modelCount,
            'module_pages_wired' => count(self::modulePages()),
            'model_pending_count' => count(array_filter(self::modelPendingTables(), fn ($row) => !$row['has_model'])),
            'gate_status' => 'read_foundation_qa_v0_2_9',
        ];
    }

    private static function plannedVersionForTable(string $table): string
    {
        $map = [
            'businesses' => 'v0.2.9',
            'supplier_product_costs' => 'v0.3.4',
            'product_stock_histories' => 'v0.3.4',
            'product_cost_histories' => 'v0.3.4',
            'manual_orders' => 'v0.3.7–v0.4.0',
            'manual_order_items' => 'v0.4.0',
            'manual_order_audits' => 'v0.4.0',
            'dispatch_report_items' => 'v0.4.2',
            'return_batches' => 'v0.4.3+',
            'return_batch_items' => 'v0.4.3+',
            'payable_adjustments' => 'v0.3.5+',
            'supplier_payments' => 'v0.4.3+',
            'invoice_items' => 'v0.4.3+',
            'supplier_opening_balance_adjustments' => 'v0.3.5',
            'supplier_opening_balance_audits' => 'v0.3.5',
        ];

        return $map[$table] ?? 'post-v0.4.2';
    }
}
