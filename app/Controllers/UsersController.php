<?php

namespace App\Controllers;

use App\ActivityLog;
use App\Permission;

class UsersController extends Controller
{
    public function index()
    {
        $this->authorize('users.view');
        ActivityLog::record('users_access', 'Users foundation page viewed');

        $this->render('users.index', [
            'pageTitle' => 'Users',
            'breadcrumbs' => [
                ['label' => 'System', 'active' => false],
                ['label' => 'Users', 'active' => true],
            ],
            'accessMode' => Permission::accessMode(),
            'roles' => Permission::roles(),
            'plannedFields' => $this->plannedFields(),
            'securityRules' => $this->securityRules(),
        ]);
    }

    private function plannedFields()
    {
        return [
            'name',
            'email/username',
            'role',
            'status',
            'last login',
            'created at',
        ];
    }

    private function securityRules()
    {
        return [
            'password hashing',
            'session protection',
            'activity logging',
            'role permission checks',
        ];
    }
}
