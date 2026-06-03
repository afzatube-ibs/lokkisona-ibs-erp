<?php

namespace App\Controllers;

use App\ActivityLog;
use App\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        Auth::requireAuth();
        ActivityLog::record('dashboard_access', 'Dashboard viewed');

        $this->render('dashboard.index', [
            'pageTitle' => 'Dashboard',
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'active' => true],
            ],
        ]);
    }
}
