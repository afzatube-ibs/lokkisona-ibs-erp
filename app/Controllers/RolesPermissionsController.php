<?php

namespace App\Controllers;

use App\ActivityLog;
use App\Database;
use App\Database\TableName;
use App\Models\Role;
use App\Permission;
use App\Services\ReadOnly\RoleReadService;

class RolesPermissionsController extends Controller
{
    public function index()
    {
        $this->authorize('roles_permissions.view');
        ActivityLog::record('roles_permissions_access', 'Role and permission read foundation viewed');

        $this->render('roles-permissions.index', [
            'pageTitle' => 'Role & Permission Foundation',
            'breadcrumbs' => [
                ['label' => 'System', 'active' => false],
                ['label' => 'Role & Permission Foundation', 'active' => true],
            ],
            'readInventory' => $this->buildReadInventory(),
            'roles' => Permission::roles(),
            'groups' => Permission::groups(),
            'accessMode' => Permission::accessMode(),
        ]);
    }

    private function buildReadInventory()
    {
        $databaseStatus = Database::check();
        $defaults = [
            'database_connected' => (bool) ($databaseStatus['connected'] ?? false),
            'service_ready' => false,
            'logical_table' => Role::table(),
            'prefixed_table' => TableName::forModel(Role::class),
            'model_class' => 'Role',
            'primary_key' => Role::primaryKey(),
            'columns' => Role::columns(),
            'read_service' => 'RoleReadService',
            'read_repository' => 'RoleRepository',
            'table_exists' => false,
            'row_count' => 0,
            'rows' => [],
            'status' => 'error',
            'status_message' => 'Read inventory could not be loaded safely.',
        ];

        try {
            $service = new RoleReadService();
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
                $defaults['status_message'] = 'Table ready. No role records yet (read-only; no writes in this release).';

                return $defaults;
            }

            $defaults['status'] = 'ok';
            $defaults['status_message'] = 'Showing up to 50 role records (SELECT only).';

            return $defaults;
        } catch (\Throwable $e) {
            return $defaults;
        }
    }
}
