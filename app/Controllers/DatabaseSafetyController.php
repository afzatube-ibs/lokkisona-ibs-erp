<?php

namespace App\Controllers;

use App\ActivityLog;
use App\Database;
use App\Database\QueryGuard;
use App\ReadFoundation\ReadFoundationQa;
use App\ReadFoundation\WritePathWhitelist;
use App\Repositories\ReadOnlyRepositoryRegistry;

class DatabaseSafetyController extends Controller
{
    public function index()
    {
        $this->authorize('database_safety.view');
        ActivityLog::record('database_safety_access', 'Database safety page viewed');

        $this->render('database-safety.index', [
            'pageTitle' => 'Database Safety',
            'breadcrumbs' => [
                ['label' => 'System', 'active' => false],
                ['label' => 'Database Safety', 'active' => true],
            ],
            'databaseStatus' => Database::check(),
            'plannedTables' => $this->plannedTables(),
            'queryGuardActive' => QueryGuard::isActive(),
            'readOnlyRepositorySummary' => $this->readOnlyRepositorySummary(),
            'readFoundationQa' => ReadFoundationQa::coverageSummary(),
            'readFoundationModulePages' => ReadFoundationQa::modulePages(),
            'modelPendingTables' => ReadFoundationQa::modelPendingTables(),
            'readinessChecklist' => ReadFoundationQa::readinessChecklist(),
            'writePathWhitelistRules' => WritePathWhitelist::rules(),
            'writePathWhitelistDirs' => WritePathWhitelist::allowedDirectories(),
        ]);
    }

    private function readOnlyRepositorySummary()
    {
        try {
            return ReadOnlyRepositoryRegistry::statusSummary();
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function plannedTables()
    {
        return [
            'users',
            'roles',
            'user_roles',
            'activity_logs',
            'businesses',
            'sales_channels',
            'suppliers',
            'supplier_quick_invoices',
            'supplier_quick_invoice_items',
            'supplier_quick_invoice_audits',
            'products',
            'product_variants',
            'supplier_product_costs',
            'product_stock_histories',
            'product_cost_histories',
            'orders',
            'order_items',
            'manual_orders',
            'manual_order_items',
            'manual_order_audits',
            'order_status_mappings',
            'status_mappings',
            'courier_status_mappings',
            'sync_previews',
            'sync_preview_items',
            'sync_imports',
            'sync_logs',
            'source_product_mappings',
            'courier_accounts',
            'invoices',
            'invoice_items',
            'invoice_templates',
            'packing_prints',
            'print_logs',
            'order_workflow_histories',
            'dispatch_reports',
            'dispatch_report_items',
            'supplier_returns',
            'lokkisona_returns',
            'return_receives',
            'return_batches',
            'return_batch_items',
            'return_workflow_histories',
            'payable_adjustments',
            'payable_ledgers',
            'supplier_invoices',
            'supplier_payments',
            'supplier_deductions',
            'supplier_settlements',
            'settings',
        ];
    }
}
