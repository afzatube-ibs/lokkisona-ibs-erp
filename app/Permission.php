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

    public static function menuItems()
    {
        $items = [
            [
                'label' => 'Dashboard',
                'path' => '/dashboard',
                'permission' => 'dashboard.view',
                'icon' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
            ],
            [
                'label' => 'Health Check',
                'path' => '/health',
                'permission' => 'health.view',
                'icon' => '<path d="M22 12h-4l-3 9L9 3l-3 9H2"/>',
            ],
            [
                'label' => 'Version',
                'path' => '/version',
                'permission' => 'version.view',
                'icon' => '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/>',
            ],
            [
                'label' => 'Activity Log',
                'path' => '/activity-log',
                'permission' => 'activity_log.view',
                'icon' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 13h8M8 17h8M8 9h2"/>',
            ],
            [
                'label' => 'Role & Permissions',
                'path' => '/roles-permissions',
                'permission' => 'roles_permissions.view',
                'icon' => '<path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><path d="M20 8v6M23 11h-6"/>',
            ],
            [
                'label' => 'Database Safety',
                'path' => '/database-safety',
                'permission' => 'database_safety.view',
                'icon' => '<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M9 12l2 2 4-4"/>',
            ],
            [
                'label' => 'Users',
                'path' => '/users',
                'permission' => 'users.view',
                'icon' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
            ],
            [
                'label' => 'Suppliers',
                'path' => '/suppliers',
                'permission' => 'suppliers.view',
                'icon' => '<path d="M3 9l1-5h16l1 5"/><path d="M4 9v10a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1V9"/><path d="M9 22V12h6v10"/>',
            ],
            [
                'label' => 'Business Sources',
                'path' => '/business-sources',
                'permission' => 'business_sources.view',
                'icon' => '<circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15.3 15.3 0 0 1 0 20"/><path d="M12 2a15.3 15.3 0 0 0 0 20"/>',
            ],
            [
                'label' => 'Product Control',
                'path' => '/product-control',
                'permission' => 'product_control.view',
                'icon' => '<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/>',
            ],
            [
                'label' => 'Order Workflow',
                'path' => '/order-workflow',
                'permission' => 'order_workflow.view',
                'icon' => '<path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>',
            ],
            [
                'label' => 'Dispatch Reports',
                'path' => '/dispatch-reports',
                'permission' => 'dispatch_reports.view',
                'icon' => '<rect x="1" y="3" width="15" height="13" rx="1"/><path d="M16 8h4l3 3v5a1 1 0 0 1-1 1h-6z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>',
            ],
        ];

        return array_values(array_filter($items, function ($item) {
            return self::can($item['permission']);
        }));
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
