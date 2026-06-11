<?php

namespace App\Models;

/**
 * Read-only, in-memory map of migration draft table => model contract class.
 *
 * This is an inventory helper only. It performs no filesystem scanning with side
 * effects, opens no database connection, and executes no SQL. It exists so future
 * owner-approved tooling and planning pages can display model-vs-draft coverage.
 */
class ModelRegistry
{
    private static array $map = [
        'users' => User::class,
        'roles' => Role::class,
        'activity_logs' => ActivityLog::class,
        'businesses' => Business::class,
        'business_sources' => BusinessSource::class,
        'suppliers' => Supplier::class,
        'products' => Product::class,
        'product_variants' => ProductVariant::class,
        'supplier_product_costs' => SupplierProductCost::class,
        'product_cost_histories' => ProductCostHistory::class,
        'product_stock_histories' => ProductStockHistory::class,
        'manual_orders' => ManualOrder::class,
        'manual_order_items' => ManualOrderItem::class,
        'dispatch_report_items' => DispatchReportItem::class,
        'supplier_opening_balance_audits' => SupplierOpeningBalanceAudit::class,
        'orders' => Order::class,
        'order_items' => OrderItem::class,
        'order_workflow_histories' => OrderWorkflowHistory::class,
        'dispatch_reports' => DispatchReport::class,
        'return_receives' => ReturnReceive::class,
        'return_reports' => ReturnReport::class,
        'return_report_items' => ReturnReportItem::class,
        'payable_ledgers' => PayableLedger::class,
        'invoices' => Invoice::class,
        'supplier_opening_balances' => SupplierOpeningBalance::class,
        'launch_cutovers' => LaunchCutover::class,
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
}
