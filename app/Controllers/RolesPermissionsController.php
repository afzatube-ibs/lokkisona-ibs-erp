<?php

namespace App\Controllers;

use App\ActivityLog;
use App\Permission;

class RolesPermissionsController extends Controller
{
    public function index()
    {
        $this->authorize('roles_permissions.view');
        ActivityLog::record('roles_permissions_access', 'Role and permission foundation viewed');

        $this->render('roles-permissions.index', [
            'pageTitle' => 'Role & Permission Foundation',
            'breadcrumbs' => [
                ['label' => 'System', 'active' => false],
                ['label' => 'Role & Permission Foundation', 'active' => true],
            ],
            'roles' => Permission::roles(),
            'groups' => Permission::groups(),
            'accessMode' => Permission::accessMode(),
        ]);
    }
}
