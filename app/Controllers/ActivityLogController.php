<?php

namespace App\Controllers;

use App\ActivityLog;

class ActivityLogController extends Controller
{
    public function index()
    {
        $this->authorize('activity_log.view');
        ActivityLog::record('activity_log_access', 'Activity log viewed');

        $this->render('activity-log.index', [
            'pageTitle' => 'Activity Log',
            'breadcrumbs' => [
                ['label' => 'System', 'active' => false],
                ['label' => 'Activity Log', 'active' => true],
            ],
            'entries' => ActivityLog::recent(100),
        ]);
    }
}
