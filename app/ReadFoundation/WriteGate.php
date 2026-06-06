<?php

namespace App\ReadFoundation;

use App\Database;
use App\Database\Connection;
use App\Migration\DevDatabaseActivation;
use PDO;

/**
 * Table-gated write form readiness (v0.4.2.3).
 * INFORMATION_SCHEMA SELECT only — no migration apply, no schema changes.
 */
class WriteGate
{
    public const WARNING_MESSAGE = 'Required tables are not applied yet. Apply migrations manually from Dev DB Activation before testing this form.';

    public static function status(array $physicalTables): array
    {
        $connected = (bool) (Database::check()['connected'] ?? false);
        $missing = [];
        $tableStatus = [];

        if (!$connected) {
            foreach ($physicalTables as $table) {
                $tableStatus[] = ['table' => $table, 'ready' => false, 'status' => 'Unavailable'];
                $missing[] = $table;
            }

            return [
                'ready' => false,
                'connected' => false,
                'tables' => $tableStatus,
                'missing_tables' => $missing,
                'message' => self::WARNING_MESSAGE,
            ];
        }

        $pdo = null;
        try {
            $pdo = Connection::pdo();
        } catch (\Throwable $e) {
            foreach ($physicalTables as $table) {
                $tableStatus[] = ['table' => $table, 'ready' => false, 'status' => 'Unavailable'];
                $missing[] = $table;
            }

            return [
                'ready' => false,
                'connected' => false,
                'tables' => $tableStatus,
                'missing_tables' => $missing,
                'message' => self::WARNING_MESSAGE,
            ];
        }

        foreach ($physicalTables as $table) {
            $exists = DevDatabaseActivation::physicalTableExists($pdo, $table);
            $tableStatus[] = [
                'table' => $table,
                'ready' => $exists,
                'status' => $exists ? 'Ready' : 'Not applied',
            ];
            if (!$exists) {
                $missing[] = $table;
            }
        }

        return [
            'ready' => $missing === [],
            'connected' => true,
            'tables' => $tableStatus,
            'missing_tables' => $missing,
            'message' => self::WARNING_MESSAGE,
        ];
    }

    public static function suppliers(): array
    {
        return self::status(['ibs_suppliers']);
    }

    public static function businessSources(): array
    {
        return self::status(['ibs_business_sources', 'ibs_businesses']);
    }

    public static function productControl(): array
    {
        return self::status([
            'ibs_products',
            'ibs_product_variants',
            'ibs_supplier_product_costs',
            'ibs_product_cost_histories',
            'ibs_product_stock_histories',
        ]);
    }

    public static function productCreateForm(): array
    {
        return self::status(['ibs_products']);
    }

    public static function productVariantForm(): array
    {
        return self::status(['ibs_products', 'ibs_product_variants']);
    }

    public static function productCostStockForm(): array
    {
        return self::status([
            'ibs_products',
            'ibs_product_cost_histories',
            'ibs_product_stock_histories',
        ]);
    }

    public static function supplierOpeningBalances(): array
    {
        return self::status([
            'ibs_supplier_opening_balances',
            'ibs_supplier_opening_balance_audits',
            'ibs_payable_ledgers',
            'ibs_launch_cutovers',
        ]);
    }

    public static function manualOrders(): array
    {
        return self::manualOrderCreateForm();
    }

    public static function manualOrderCreateForm(): array
    {
        return self::status([
            'ibs_manual_orders',
            'ibs_manual_order_items',
            'ibs_orders',
            'ibs_order_items',
            'ibs_order_workflow_histories',
            'ibs_products',
            'ibs_product_variants',
            'ibs_business_sources',
            'ibs_suppliers',
        ]);
    }

    public static function manualOrderBridge(): array
    {
        return self::status([
            'ibs_orders',
            'ibs_order_items',
        ]);
    }

    public static function orderWorkflow(): array
    {
        return self::status([
            'ibs_orders',
            'ibs_order_items',
            'ibs_order_workflow_histories',
        ]);
    }

    public static function dispatchReports(): array
    {
        return self::status([
            'ibs_dispatch_reports',
            'ibs_dispatch_report_items',
            'ibs_orders',
            'ibs_order_items',
        ]);
    }
}
