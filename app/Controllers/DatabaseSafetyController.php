<?php

namespace App\Controllers;

use App\ActivityLog;
use App\Database;

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
        ]);
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
            'products',
            'product_variants',
            'supplier_product_costs',
            'product_stock_histories',
            'product_cost_histories',
            'orders',
            'order_items',
            'order_status_mappings',
            'order_workflow_histories',
            'dispatch_reports',
            'dispatch_report_items',
            'supplier_returns',
            'owner_returns',
            'payable_ledgers',
            'supplier_invoices',
            'supplier_payments',
            'supplier_deductions',
            'supplier_settlements',
            'settings',
        ];
    }
}
