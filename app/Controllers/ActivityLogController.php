<?php

namespace App\Controllers;

use App\ActivityLog;
use App\Auth;

class ActivityLogController extends Controller
{
    public function index()
    {
        Auth::requireAuth();
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
