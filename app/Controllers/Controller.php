<?php

namespace App\Controllers;

use App\ActivityLog;
use App\Auth;
use App\Permission;

class Controller
{
    protected function render($view, $data = [], $layout = 'layouts.admin')
    {
        $data['pageTitle'] = $data['pageTitle'] ?? config('app.name');
        $data['appName'] = config('app.name');
        $data['appVersion'] = config('app.version');
        $data['appReleaseLabel'] = config('app.release_label');
        $data['currentUser'] = Auth::user();
        $data['currentRole'] = Auth::role();
        $data['navItems'] = Permission::menuItems();
        $data['currentPath'] = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

        ob_start();
        view($view, $data);
        $content = ob_get_clean();

        view($layout, array_merge($data, ['content' => $content]));
    }

    protected function authorize($permission)
    {
        Auth::requireAuth();

        if (Permission::can($permission)) {
            return;
        }

        ActivityLog::record('access_denied', 'Permission check failed', [
            'permission' => $permission,
            'role' => Auth::role(),
        ]);

        http_response_code(403);
        $this->render('errors.403', [
            'pageTitle' => 'Access Denied',
            'breadcrumbs' => [
                ['label' => 'System', 'active' => false],
                ['label' => 'Access Denied', 'active' => true],
            ],
            'permission' => $permission,
        ]);
        exit;
    }
}
