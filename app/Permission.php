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

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function menuItems()
    {
        $items = [
            ['label' => 'Dashboard', 'path' => '/dashboard', 'permission' => 'dashboard.view', 'tier' => 'primary', 'group' => 'Fulfillment', 'icon' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>'],
            ['label' => 'Order Workflow', 'short_label' => 'Orders', 'path' => '/order-workflow', 'permission' => 'order_workflow.view', 'tier' => 'primary', 'group' => 'Fulfillment', 'icon' => '<path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>'],
            ['label' => 'Manual Orders', 'path' => '/manual-orders', 'permission' => 'manual_orders.view', 'tier' => 'primary', 'group' => 'Fulfillment', 'icon' => '<path d="M9 12h6M9 16h6"/><path d="M8 2h8l4 4v16H4V2z"/><path d="M16 2v5h5"/>'],
            ['label' => 'Dispatch Reports', 'short_label' => 'Dispatch', 'path' => '/dispatch-reports', 'permission' => 'dispatch_reports.view', 'tier' => 'primary', 'group' => 'Fulfillment', 'icon' => '<rect x="1" y="3" width="15" height="13" rx="1"/><path d="M16 8h4l3 3v5a1 1 0 0 1-1 1h-6z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>'],
            ['label' => 'Return Receive', 'short_label' => 'Returns', 'path' => '/return-receive', 'permission' => 'return_receive.view', 'tier' => 'primary', 'group' => 'Fulfillment', 'icon' => '<path d="M3 7v6h6"/><path d="M3 13a9 9 0 1 0 3-7.7L3 8"/>'],
            ['label' => 'Supplier Payables', 'short_label' => 'Payables', 'path' => '/supplier-payables', 'permission' => 'supplier_payables.view', 'tier' => 'primary', 'group' => 'Finance', 'icon' => '<rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/><path d="M6 15h4"/>'],
            ['label' => 'Settlements', 'path' => '/settlements', 'permission' => 'settlements.view', 'tier' => 'primary', 'group' => 'Finance', 'icon' => '<path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/><circle cx="18" cy="18" r="3"/>'],
            ['label' => 'Reports', 'path' => '/reports', 'permission' => 'supplier_payables.view', 'tier' => 'primary', 'group' => 'Finance', 'icon' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 13h8M8 17h6"/>'],
            ['label' => 'Product Control', 'short_label' => 'Products', 'path' => '/product-control', 'permission' => 'product_control.view', 'tier' => 'primary', 'group' => 'Catalog', 'icon' => '<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/>'],
            ['label' => 'Status Mapping', 'path' => '/status-mapping', 'permission' => 'status_mapping.view', 'tier' => 'primary', 'group' => 'Catalog', 'icon' => '<path d="M16 3h5v5"/><path d="M8 3H3v5"/><path d="M12 22v-8.3a4 4 0 0 0-1.172-2.872L3 3"/><path d="m15 9 6-6"/>'],
            ['label' => 'Sync Preview', 'path' => '/sync-preview', 'permission' => 'sync_preview.view', 'tier' => 'primary', 'group' => 'Catalog', 'icon' => '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>'],
            ['label' => 'Invoice Printing', 'short_label' => 'Invoices', 'path' => '/invoice-printing', 'permission' => 'invoice_printing.view', 'tier' => 'primary', 'group' => 'Catalog', 'icon' => '<path d="M6 2h9l5 5v15H6z"/><path d="M14 2v6h6"/><path d="M9 13h6M9 17h6"/>'],
            ['label' => 'Users', 'path' => '/users', 'permission' => 'users.view', 'tier' => 'setup', 'group' => 'Setup', 'icon' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>'],
            ['label' => 'Suppliers', 'path' => '/suppliers', 'permission' => 'suppliers.view', 'tier' => 'setup', 'group' => 'Setup', 'icon' => '<path d="M3 9l1-5h16l1 5"/><path d="M4 9v10a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1V9"/><path d="M9 22V12h6v10"/>'],
            ['label' => 'Business Sources', 'path' => '/business-sources', 'permission' => 'business_sources.view', 'tier' => 'setup', 'group' => 'Setup', 'icon' => '<circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15.3 15.3 0 0 1 0 20"/><path d="M12 2a15.3 15.3 0 0 0 0 20"/>'],
            ['label' => 'Supplier Opening Balances', 'short_label' => 'Opening Balances', 'path' => '/supplier-opening-balances', 'permission' => 'supplier_opening_balances.view', 'tier' => 'setup', 'group' => 'Setup', 'icon' => '<path d="M4 6h16v12H4z"/><path d="M8 10h8M8 14h5"/><path d="M18 3v6M21 6h-6"/>'],
            ['label' => 'Supplier Tools', 'path' => '/supplier-tools', 'permission' => 'supplier_tools.view', 'tier' => 'setup', 'group' => 'Setup', 'icon' => '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.8-3.8a6 6 0 0 1-7.9 7.9l-6.1 6.1a2.1 2.1 0 0 1-3-3l6.1-6.1a6 6 0 0 1 7.9-7.9l-3.8 3.8z"/>'],
            ['label' => 'Activity Log', 'path' => '/activity-log', 'permission' => 'activity_log.view', 'tier' => 'system', 'group' => 'System', 'icon' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 13h8M8 17h8M8 9h2"/>'],
            ['label' => 'Health Check', 'path' => '/health', 'permission' => 'health.view', 'tier' => 'system', 'group' => 'System', 'icon' => '<path d="M22 12h-4l-3 9L9 3l-3 9H2"/>'],
            ['label' => 'Version', 'path' => '/version', 'permission' => 'version.view', 'tier' => 'system', 'group' => 'System', 'icon' => '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/>'],
            ['label' => 'Role & Permissions', 'short_label' => 'Roles', 'path' => '/roles-permissions', 'permission' => 'roles_permissions.view', 'tier' => 'system', 'group' => 'System', 'icon' => '<path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><path d="M20 8v6M23 11h-6"/>'],
            ['label' => 'Database Safety', 'path' => '/database-safety', 'permission' => 'database_safety.view', 'tier' => 'developer', 'group' => 'Developer', 'icon' => '<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M9 12l2 2 4-4"/>'],
            ['label' => 'Dev DB Activation', 'path' => '/dev-db-activation', 'permission' => 'dev_db_activation.view', 'tier' => 'developer', 'group' => 'Developer', 'icon' => '<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/><path d="M12 12v4"/><path d="M12 8h.01"/>'],
            ['label' => 'Migration Runner', 'path' => '/migration-runner', 'permission' => 'migration_runner.view', 'tier' => 'developer', 'group' => 'Developer', 'icon' => '<path d="M4 4h16v6H4z"/><path d="M4 14h16v6H4z"/><path d="M8 7h.01M8 17h.01"/><path d="M12 7h4M12 17h4"/>'],
            ['label' => 'Migration Files', 'path' => '/migration-files', 'permission' => 'migration_files.view', 'tier' => 'developer', 'group' => 'Developer', 'icon' => '<path d="M6 2h9l5 5v15H6z"/><path d="M14 2v6h6"/><path d="M9 13h6M9 17h4"/>'],
            ['label' => 'Migration Dry Run', 'path' => '/migration-dry-run', 'permission' => 'migration_dry_run.view', 'tier' => 'developer', 'group' => 'Developer', 'icon' => '<path d="M4 4h16v16H4z"/><path d="M8 9h8M8 13h5"/><path d="M16 17l2 2 4-4"/>'],
            ['label' => 'Migration Approval', 'path' => '/migration-approval', 'permission' => 'migration_approval.view', 'tier' => 'developer', 'group' => 'Developer', 'icon' => '<path d="M12 2l7 4v6c0 5-3 8-7 10-4-2-7-5-7-10V6z"/><path d="M9 12l2 2 4-5"/>'],
            ['label' => 'Migration Execution Lock', 'short_label' => 'Migration Lock', 'path' => '/migration-execution-lock', 'permission' => 'migration_execution_lock.view', 'tier' => 'developer', 'group' => 'Developer', 'icon' => '<rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/><path d="M12 15v2"/>'],
            ['label' => 'Build Queue', 'path' => '/build-queue', 'permission' => 'build_queue.view', 'tier' => 'developer', 'group' => 'Developer', 'icon' => '<path d="M4 6h16M4 12h16M4 18h16"/><path d="M8 4v4M12 10v4M16 16v4"/>'],
        ];

        $filtered = array_values(array_filter($items, function ($item) {
            return self::can($item['permission']);
        }));

        if (self::currentRole() === 'supplier') {
            foreach ($filtered as &$item) {
                if (($item['path'] ?? '') === '/supplier-tools') {
                    $item['tier'] = 'primary';
                    $item['group'] = 'Fulfillment';
                }
            }
            unset($item);
        }

        return $filtered;
    }

    /**
     * Navigation structure for sidebar rendering.
     *
     * @return array{primary: array<string, array<int, array<string, mixed>>>, setup: array<int, array<string, mixed>>, system: array<int, array<string, mixed>>, developer: array<int, array<string, mixed>>}
     */
    public static function menuNavigation(): array
    {
        $primaryOrder = ['Fulfillment', 'Finance', 'Catalog'];
        $primary = [];
        $setup = [];
        $system = [];
        $developer = [];

        foreach (self::menuItems() as $item) {
            $tier = $item['tier'] ?? 'primary';
            if ($tier === 'primary') {
                $group = $item['group'] ?? 'General';
                $primary[$group][] = $item;
            } elseif ($tier === 'setup') {
                $setup[] = $item;
            } elseif ($tier === 'system') {
                $system[] = $item;
            } elseif ($tier === 'developer') {
                $developer[] = $item;
            }
        }

        $orderedPrimary = [];
        foreach ($primaryOrder as $groupName) {
            if (!empty($primary[$groupName])) {
                $orderedPrimary[$groupName] = $primary[$groupName];
            }
        }
        foreach ($primary as $groupName => $groupItems) {
            if (!isset($orderedPrimary[$groupName])) {
                $orderedPrimary[$groupName] = $groupItems;
            }
        }

        return [
            'primary' => $orderedPrimary,
            'setup' => $setup,
            'system' => $system,
            'developer' => $developer,
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
