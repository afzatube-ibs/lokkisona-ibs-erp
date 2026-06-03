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
