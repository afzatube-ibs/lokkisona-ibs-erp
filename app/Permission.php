<?php

namespace App;

class Permission
{
    public static function currentRole()
    {
        return Auth::role();
    }

    public static function roles()
    {
        return config('permissions.roles', []);
    }

    public static function groups()
    {
        return config('permissions.groups', []);
    }

    public static function permissionsForRole($role)
    {
        return config('permissions.role_permissions.' . $role, []);
    }

    public static function can($permission, $role = null)
    {
        $role = $role ?: self::currentRole();
        $permissions = self::permissionsForRole($role);

        return in_array('*', $permissions, true) || in_array($permission, $permissions, true);
    }

    /** Owner/admin Sync Hub manage gate (v2.4.8). */
    public static function canSyncHub(): bool
    {
        return self::can('sync_api_settings.manage');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function menuItems()
    {
        $items = [
            ['label' => 'Dashboard', 'path' => '/dashboard', 'permission' => 'dashboard.view', 'tier' => 'dashboard', 'icon' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>'],

            // Orders
            ['label' => 'Order List', 'path' => '/order-workflow', 'permission' => 'order_workflow.view', 'tier' => 'orders', 'icon' => '<path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>'],

            // Fulfillment
            ['label' => 'Daily Dispatch', 'path' => '/dispatch-reports', 'permission' => 'dispatch_reports.view', 'tier' => 'fulfillment', 'icon' => '<rect x="1" y="3" width="15" height="13" rx="1"/><path d="M16 8h4l3 3v5a1 1 0 0 1-1 1h-6z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>'],
            ['label' => 'Return Receive', 'path' => '/return-receive', 'permission' => 'return_receive.view', 'tier' => 'fulfillment', 'icon' => '<path d="M3 7v6h6"/><path d="M3 13a9 9 0 1 0 3-7.7L3 8"/>'],
            ['label' => 'Return Reports', 'path' => '/return-reports', 'permission' => 'returns.view', 'tier' => 'fulfillment', 'icon' => '<path d="M3 7v6h6"/><path d="M3 13a9 9 0 1 0 3-7.7L3 8"/><line x1="12" y1="8" x2="12" y2="14"/>'],

            // Accounts
            ['label' => 'Payables', 'path' => '/supplier-payables', 'permission' => 'supplier_payables.view', 'tier' => 'accounts', 'icon' => '<rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/><path d="M6 15h4"/>'],
            ['label' => 'Inventory Report', 'path' => '/reports', 'match_query' => ['report' => 'inventory_snapshot'], 'permission' => 'supplier_payables.view', 'tier' => 'accounts', 'icon' => '<path d="M3 9l1-5h16l1 5"/><path d="M4 9v10a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1V9"/>'],
            ['label' => 'Sales / Order Report', 'short_label' => 'Sales / Orders', 'path' => '/reports', 'match_query' => ['report' => 'product_sales'], 'permission' => 'supplier_payables.view', 'tier' => 'accounts', 'icon' => '<path d="M3 3v18h18"/><path d="M7 16l4-6 4 3 5-8"/>'],

            // Catalog (owner/admin — hidden for supplier via supplier_hidden)
            ['label' => 'Products', 'path' => '/product-control', 'permission' => 'product_control.view', 'tier' => 'catalog', 'supplier_hidden' => true, 'icon' => '<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/>'],

            // Settings
            ['label' => 'Sync Settings', 'path' => '/sync-api-settings', 'permission' => 'sync_api_settings.view', 'tier' => 'settings', 'supplier_hidden' => true, 'icon' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>'],
            ['label' => 'Status Mapping', 'path' => '/status-mapping', 'permission' => 'status_mapping.view', 'tier' => 'settings', 'supplier_hidden' => true, 'icon' => '<path d="M16 3h5v5"/><path d="M8 3H3v5"/><path d="M12 22v-8.3a4 4 0 0 0-1.172-2.872L3 3"/><path d="m15 9 6-6"/>'],
            ['label' => 'Business Sources', 'path' => '/business-sources', 'permission' => 'business_sources.view', 'tier' => 'settings', 'supplier_hidden' => true, 'icon' => '<circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15.3 15.3 0 0 1 0 20"/><path d="M12 2a15.3 15.3 0 0 0 0 20"/>'],
            ['label' => 'Users', 'path' => '/users', 'permission' => 'users.view', 'tier' => 'settings', 'icon' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>'],
            ['label' => 'Roles', 'path' => '/roles-permissions', 'permission' => 'roles_permissions.view', 'tier' => 'settings', 'icon' => '<path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><path d="M20 8v6M23 11h-6"/>'],
            ['label' => 'Activity Log', 'path' => '/activity-log', 'permission' => 'activity_log.view', 'tier' => 'settings', 'icon' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 13h8M8 17h8M8 9h2"/>'],
            ['label' => 'Health Check', 'path' => '/health', 'permission' => 'health.view', 'tier' => 'settings', 'icon' => '<path d="M22 12h-4l-3 9L9 3l-3 9H2"/>'],
            ['label' => 'Version', 'path' => '/version', 'permission' => 'version.view', 'tier' => 'settings', 'icon' => '<path d="M12 2l3 7h7l-5.5 4 2 7L12 16l-6.5 4 2-7L2 9h7z"/>'],

            // Future Plan
            ['label' => 'Sales', 'path' => '/manual-orders', 'permission' => 'manual_orders.view', 'tier' => 'future_plans', 'icon' => '<path d="M9 12h6M9 16h6"/><path d="M8 2h8l4 4v16H4V2z"/><path d="M16 2v5h5"/>'],
            ['label' => 'Categories', 'path' => '', 'permission' => 'product_control.view', 'tier' => 'future_plans', 'nav_disabled' => true, 'icon' => '<path d="M4 6h16v12H4z"/><path d="M8 10h8M8 14h5"/>'],
            ['label' => 'Inventory', 'path' => '', 'permission' => 'product_control.view', 'tier' => 'future_plans', 'nav_disabled' => true, 'icon' => '<path d="M3 9l1-5h16l1 5"/><path d="M4 9v10a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1V9"/>'],
            ['label' => 'Dispatch Summary', 'path' => '/reports', 'match_query' => ['report' => 'dispatch_payable'], 'permission' => 'supplier_payables.view', 'tier' => 'future_plans', 'icon' => '<rect x="1" y="3" width="15" height="13" rx="1"/><path d="M16 8h4l3 3v5a1 1 0 0 1-1 1h-6z"/>'],
            ['label' => 'Supplier Summary', 'path' => '/reports', 'match_query' => ['report' => 'supplier_statement'], 'permission' => 'supplier_payables.view', 'tier' => 'future_plans', 'icon' => '<path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/>'],
            ['label' => 'Product Summary', 'path' => '/reports', 'match_query' => ['report' => 'product_dispatch'], 'permission' => 'supplier_payables.view', 'tier' => 'future_plans', 'icon' => '<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>'],
            ['label' => 'Return Summary', 'path' => '/reports', 'match_query' => ['report' => 'hub_return'], 'permission' => 'supplier_payables.view', 'tier' => 'future_plans', 'icon' => '<path d="M3 7v6h6"/><path d="M3 13a9 9 0 1 0 3-7.7L3 8"/>'],
            ['label' => 'Sync Preview', 'path' => '/sync-preview', 'permission' => 'sync_preview.view', 'tier' => 'future_plans', 'supplier_hidden' => true, 'icon' => '<path d="M3 3v18h18"/><path d="M7 16l4-6 4 3 5-8"/>'],
            ['label' => 'Status Mapping', 'path' => '/status-mapping', 'permission' => 'status_mapping.view', 'tier' => 'future_plans', 'nav_disabled' => true, 'icon' => '<path d="M16 3h5v5"/><path d="M8 3H3v5"/><path d="M12 22v-8.3a4 4 0 0 0-1.172-2.872L3 3"/><path d="m15 9 6-6"/>'],
            ['label' => 'Connector Diagnostics', 'path' => '', 'permission' => 'sync_api_settings.view', 'tier' => 'future_plans', 'nav_disabled' => true, 'icon' => '<circle cx="12" cy="12" r="3"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42"/>'],
            ['label' => 'Advanced Sync', 'path' => '', 'permission' => 'sync_preview.view', 'tier' => 'future_plans', 'nav_disabled' => true, 'icon' => '<path d="M21 12a9 9 0 0 1-9 9m9-9a9 9 0 0 0-9-9m9 9H3m9 9a9 9 0 0 1-9-9m9 9V3"/>'],
            ['label' => 'Sync Settings', 'path' => '/sync-api-settings', 'permission' => 'sync_api_settings.view', 'tier' => 'future_plans', 'nav_disabled' => true, 'icon' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>'],
            ['label' => 'Database Safety', 'path' => '/database-safety', 'permission' => 'database_safety.view', 'tier' => 'future_plans', 'icon' => '<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M9 12l2 2 4-4"/>'],
            ['label' => 'Suppliers', 'path' => '/suppliers', 'permission' => 'suppliers.view', 'tier' => 'future_plans', 'icon' => '<path d="M3 9l1-5h16l1 5"/><path d="M4 9v10a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1V9"/><path d="M9 22V12h6v10"/>'],
            ['label' => 'Settlements', 'path' => '/settlements', 'permission' => 'settlements.view', 'tier' => 'future_plans', 'icon' => '<path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/><circle cx="18" cy="18" r="3"/>'],
            ['label' => 'Balance Sheet', 'path' => '/reports', 'match_query' => ['report' => 'monthly_payable'], 'permission' => 'supplier_payables.view', 'tier' => 'future_plans', 'icon' => '<path d="M3 3v18h18"/><path d="M7 16l4-6 4 3 5-8"/>'],
            ['label' => 'Sales / Order Reports', 'short_label' => 'Sales / Orders', 'path' => '/reports', 'match_query' => ['report' => 'product_sales'], 'permission' => 'supplier_payables.view', 'tier' => 'future_plans', 'icon' => '<path d="M3 3v18h18"/><path d="M7 16l4-6 4 3 5-8"/>'],
            ['label' => 'Supplier Tools', 'path' => '/supplier-tools', 'permission' => 'supplier_quick_invoice.manage', 'tier' => 'future_plans', 'supplier_hidden' => true, 'icon' => '<path d="M6 2h9l5 5v15H6z"/><path d="M14 2v6h6"/><path d="M9 13h6M9 17h6"/>'],
            ['label' => 'Calculator', 'path' => '', 'nav_action' => 'supplierCalculatorModal', 'permission' => 'supplier_calculator.view', 'tier' => 'future_plans', 'supplier_hidden' => true, 'icon' => '<rect x="4" y="2" width="16" height="20" rx="2"/><line x1="8" y1="6" x2="16" y2="6"/>'],
            ['label' => 'Invoice Printing', 'path' => '/invoice-printing', 'permission' => 'invoice_printing.view', 'tier' => 'future_plans', 'icon' => '<path d="M6 2h9l5 5v15H6z"/><path d="M14 2v6h6"/><path d="M9 13h6M9 17h6"/>'],
            ['label' => 'Forecasting', 'path' => '', 'permission' => 'health.view', 'tier' => 'future_plans', 'nav_disabled' => true, 'icon' => '<path d="M3 3v18h18"/><path d="M7 14l3-3 3 2 5-6"/>'],
            ['label' => 'AI Tools', 'short_label' => 'AI Tools', 'path' => '', 'permission' => 'health.view', 'tier' => 'future_plans', 'nav_disabled' => true, 'icon' => '<path d="M12 2a4 4 0 0 1 4 4v1h1a3 3 0 0 1 0 6h-1v1a4 4 0 0 1-8 0v-1H7a3 3 0 0 1 0-6h1V6a4 4 0 0 1 4-4z"/>'],
            ['label' => 'Migration Runner', 'path' => '/migration-runner', 'permission' => 'migration_runner.view', 'tier' => 'future_plans', 'icon' => '<path d="M4 4h16v6H4z"/><path d="M4 14h16v6H4z"/>'],
            ['label' => 'Migration Files', 'path' => '/migration-files', 'permission' => 'migration_files.view', 'tier' => 'future_plans', 'icon' => '<path d="M6 2h9l5 5v15H6z"/><path d="M14 2v6h6"/>'],
            ['label' => 'Migration Dry Run', 'path' => '/migration-dry-run', 'permission' => 'migration_dry_run.view', 'tier' => 'future_plans', 'icon' => '<path d="M4 4h16v16H4z"/>'],
            ['label' => 'Migration Approval', 'path' => '/migration-approval', 'permission' => 'migration_approval.view', 'tier' => 'future_plans', 'icon' => '<path d="M12 2l7 4v6c0 5-3 8-7 10-4-2-7-5-7-10V6z"/>'],
            ['label' => 'Migration Lock', 'short_label' => 'Migration Lock', 'path' => '/migration-execution-lock', 'permission' => 'migration_execution_lock.view', 'tier' => 'future_plans', 'icon' => '<rect x="5" y="11" width="14" height="10" rx="2"/>'],
            ['label' => 'Build Queue', 'path' => '/build-queue', 'permission' => 'build_queue.view', 'tier' => 'future_plans', 'icon' => '<path d="M4 6h16M4 12h16M4 18h16"/>'],
            ['label' => 'Dev DB Activation', 'path' => '/dev-db-activation', 'permission' => 'dev_db_activation.view', 'tier' => 'future_plans', 'dev_only' => true, 'icon' => '<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/>'],
        ];

        $filtered = array_values(array_filter($items, function ($item) {
            if (!self::can($item['permission'])) {
                return false;
            }
            if (!empty($item['supplier_hidden']) && SupplierContext::isSupplier()) {
                return false;
            }

            return true;
        }));

        return $filtered;
    }

    /**
     * Navigation structure for sidebar rendering.
     *
     * @return array{
     *     dashboard: array<int, array<string, mixed>>,
     *     orders: array<int, array<string, mixed>>,
     *     fulfillment: array<int, array<string, mixed>>,
     *     accounts: array<int, array<string, mixed>>,
     *     catalog: array<int, array<string, mixed>>,
     *     settings: array<int, array<string, mixed>>,
     *     future_plans: array<int, array<string, mixed>>
     * }
     */
    public static function menuNavigation(): array
    {
        $dashboard = [];
        $orders = [];
        $fulfillment = [];
        $accounts = [];
        $catalog = [];
        $settings = [];
        $futurePlans = [];

        foreach (self::menuItems() as $item) {
            $tier = $item['tier'] ?? 'orders';
            if ($tier === 'dashboard') {
                $dashboard[] = $item;
            } elseif ($tier === 'orders') {
                $orders[] = $item;
            } elseif ($tier === 'fulfillment') {
                $fulfillment[] = $item;
            } elseif ($tier === 'accounts') {
                $accounts[] = $item;
            } elseif ($tier === 'catalog') {
                $catalog[] = $item;
            } elseif ($tier === 'settings') {
                $settings[] = $item;
            } elseif ($tier === 'future_plans') {
                $futurePlans[] = $item;
            }
        }

        return [
            'dashboard' => $dashboard,
            'orders' => $orders,
            'fulfillment' => $fulfillment,
            'accounts' => $accounts,
            'catalog' => $catalog,
            'settings' => $settings,
            'future_plans' => $futurePlans,
        ];
    }

    public static function accessMode()
    {
        return [
            'mode' => 'Config-based admin login',
            'role' => self::currentRole(),
            'summary' => 'Current configured admin is treated as owner-level access until database users are added manually.',
        ];
    }
}
