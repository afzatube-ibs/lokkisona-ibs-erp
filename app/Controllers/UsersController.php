<?php

namespace App\Controllers;

use App\ActivityLog;
use App\Database;
use App\Database\TableName;
use App\Models\User;
use App\Permission;
use App\Services\ReadOnly\UserReadService;

class UsersController extends Controller
{
    public function index()
    {
        $this->authorize('users.view');
        ActivityLog::record('users_access', 'Users read foundation page viewed');

        $this->render('users.index', [
            'pageTitle' => 'Users',
            'breadcrumbs' => [
                ['label' => 'System', 'active' => false],
                ['label' => 'Users', 'active' => true],
            ],
            'accessMode' => Permission::accessMode(),
            'readInventory' => $this->buildReadInventory(),
            'roles' => Permission::roles(),
            'plannedFields' => $this->plannedFields(),
            'securityRules' => $this->securityRules(),
        ]);
    }

    private function buildReadInventory()
    {
        $databaseStatus = Database::check();
        $defaults = [
            'database_connected' => (bool) ($databaseStatus['connected'] ?? false),
            'service_ready' => false,
            'logical_table' => User::table(),
            'prefixed_table' => TableName::forModel(User::class),
            'model_class' => 'User',
            'primary_key' => User::primaryKey(),
            'columns' => User::columns(),
            'read_service' => 'UserReadService',
            'read_repository' => 'UserRepository',
            'redact_sensitive_fields' => true,
            'table_exists' => false,
            'row_count' => 0,
            'rows' => [],
            'status' => 'error',
            'status_message' => 'Read inventory could not be loaded safely.',
        ];

        try {
            $service = new UserReadService();
            $defaults['service_ready'] = true;

            if (!$defaults['database_connected']) {
                $defaults['status'] = 'not_connected';
                $defaults['status_message'] = 'Database not connected. Read inventory unavailable.';

                return $defaults;
            }

            $tableExists = $service->tableExists();
            $defaults['table_exists'] = $tableExists;

            if (!$tableExists) {
                $defaults['status'] = 'table_missing';
                $defaults['status_message'] = 'Table `' . $defaults['prefixed_table'] . '` not available — migration `0002_core_users_roles_activity.sql` not applied yet. Apply manually with the `ibs_` table prefix from config/database.php.';

                return $defaults;
            }

            $rowCount = $service->count();
            $defaults['row_count'] = $rowCount;
            $defaults['rows'] = $service->all(50, 0);

            if ($rowCount === 0) {
                $defaults['status'] = 'empty';
                $defaults['status_message'] = 'Table ready. No user records yet (read-only; no writes in this release). Sensitive fields are redacted when rows exist.';

                return $defaults;
            }

            $defaults['status'] = 'ok';
            $defaults['status_message'] = 'Showing up to 50 user records (SELECT only). Sensitive fields such as password_hash are redacted.';

            return $defaults;
        } catch (\Throwable $e) {
            return $defaults;
        }
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
