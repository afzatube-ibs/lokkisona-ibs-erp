<?php

namespace App\Controllers;

use App\ActivityLog;

class DashboardController extends Controller
{
    public function index()
    {
        $this->authorize('dashboard.view');
        ActivityLog::record('dashboard_access', 'Dashboard viewed');

        $this->render('dashboard.index', [
            'pageTitle' => 'Dashboard',
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'active' => true],
            ],
        ]);
    }
}
