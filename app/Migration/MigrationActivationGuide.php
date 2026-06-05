<?php

namespace App\Migration;

/**
 * Manual migration apply guide and database activation checklist (v0.3.0).
 * Documentation only — no SQL execution.
 */
class MigrationActivationGuide
{
    public static function applyOrder(): array
    {
        return [
            ['file' => '0002_core_users_roles_activity.sql', 'tables' => 4, 'group' => 'Core users, roles, activity'],
            ['file' => '0003_business_sources_suppliers_products.sql', 'tables' => 8, 'group' => 'Business, suppliers, products'],
            ['file' => '0004_status_mapping_sync_preview.sql', 'tables' => 6, 'group' => 'Status mapping, sync preview'],
            ['file' => '0005_orders_manual_orders_workflow.sql', 'tables' => 5, 'group' => 'Orders, manual orders, workflow'],
            ['file' => '0006_dispatch_returns_payables.sql', 'tables' => 9, 'group' => 'Dispatch, returns, payables'],
            ['file' => '0007_invoices_printing_supplier_tools.sql', 'tables' => 8, 'group' => 'Invoices, printing, supplier tools'],
            ['file' => '0008_supplier_opening_balances_launch_cutovers.sql', 'tables' => 4, 'group' => 'Opening balances, launch cutover'],
        ];
    }

    public static function minimumActivationSet(): array
    {
        return [
            '0002_core_users_roles_activity.sql',
            '0003_business_sources_suppliers_products.sql',
        ];
    }

    public static function activationSteps(): array
    {
        return [
            'Confirm tools/check-local.ps1 shows [OK] ALL GREEN on the target environment.',
            'Complete Migration Dry Run checklist (file scan, header warnings, apply order).',
            'Complete Migration Approval Gate (backup, environment, owner approval).',
            'Confirm Migration Execution Lock readiness (no dirty Git, no failed dry-run).',
            'Back up the database before any manual SQL apply.',
            'Apply migration files in order 0002 through 0008 using a trusted SQL client.',
            'Verify read inventory pages show table_exists = Yes for applied tables.',
            'Record apply date, actor, and files applied in release notes.',
            'Only then enable write service versions (v0.3.1+) on that environment.',
        ];
    }

    public static function postApplyVerification(): array
    {
        return [
            ['check' => 'SHOW TABLES LIKE \'ibs_suppliers\'', 'expect' => 'Table exists after 0003'],
            ['check' => 'SELECT COUNT(*) FROM ibs_suppliers', 'expect' => 'Query succeeds (may be 0 rows)'],
            ['check' => '/suppliers read inventory', 'expect' => 'Not applied badge becomes Yes'],
            ['check' => '/database-safety repository summary', 'expect' => 'Row counts visible when data exists'],
        ];
    }

    public static function writePhaseGates(): array
    {
        return [
            'v0.3.1 supplier create requires 0003 applied',
            'v0.3.5 opening balance requires 0008 applied',
            'v0.4.0 manual orders requires 0005 applied and launch lock tested',
            'v0.4.2 dispatch create requires 0006 applied',
        ];
    }
}
