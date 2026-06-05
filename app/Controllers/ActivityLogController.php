<?php

namespace App\Controllers;

use App\ActivityLog;
use App\Database;
use App\Database\TableName;
use App\Models\ActivityLog as ActivityLogModel;
use App\Services\ReadOnly\ActivityLogReadService;

class ActivityLogController extends Controller
{
    public function index()
    {
        $this->authorize('activity_log.view');
        ActivityLog::record('activity_log_access', 'Activity log read foundation page viewed');

        $this->render('activity-log.index', [
            'pageTitle' => 'Activity Log',
            'breadcrumbs' => [
                ['label' => 'System', 'active' => false],
                ['label' => 'Activity Log', 'active' => true],
            ],
            'readInventory' => $this->buildReadInventory(),
            'entries' => ActivityLog::recent(100),
        ]);
    }

    private function buildReadInventory()
    {
        $databaseStatus = Database::check();
        $defaults = [
            'database_connected' => (bool) ($databaseStatus['connected'] ?? false),
            'service_ready' => false,
            'logical_table' => ActivityLogModel::table(),
            'prefixed_table' => TableName::forModel(ActivityLogModel::class),
            'model_class' => 'ActivityLog',
            'primary_key' => ActivityLogModel::primaryKey(),
            'columns' => ActivityLogModel::columns(),
            'read_service' => 'ActivityLogReadService',
            'read_repository' => 'ActivityLogRepository',
            'table_exists' => false,
            'row_count' => 0,
            'rows' => [],
            'status' => 'error',
            'status_message' => 'Read inventory could not be loaded safely.',
        ];

        try {
            $service = new ActivityLogReadService();
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
                $defaults['status_message'] = 'Table `' . $defaults['prefixed_table'] . '` not available — migration `0002_core_users_roles_activity.sql` not applied yet. Apply manually with the `ibs_` table prefix from config/database.php. File-based runtime logging remains active separately.';

                return $defaults;
            }

            $rowCount = $service->count();
            $defaults['row_count'] = $rowCount;
            $defaults['rows'] = $service->all(50, 0);

            if ($rowCount === 0) {
                $defaults['status'] = 'empty';
                $defaults['status_message'] = 'Table ready. No database activity log records yet (read-only; file-based runtime logging remains active).';

                return $defaults;
            }

            $defaults['status'] = 'ok';
            $defaults['status_message'] = 'Showing up to 50 database activity log records (SELECT only). File-based runtime logging remains active separately.';

            return $defaults;
        } catch (\Throwable $e) {
            return $defaults;
        }
    }
}
