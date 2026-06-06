<?php

namespace App\Controllers;

use App\ActivityLog;
use App\Auth;
use App\Services\ReadOnly\DashboardReadService;

class DashboardController extends Controller
{
    public function index()
    {
        $this->authorize('dashboard.view');
        ActivityLog::record('dashboard_access', 'Dashboard viewed');

        $role = Auth::role();
        $service = new DashboardReadService();
        $isSupplier = $role === 'supplier';

        $this->render('dashboard.index', [
            'pageTitle' => 'Dashboard',
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'active' => true],
            ],
            'isSupplierView' => $isSupplier,
            'supplierTasks' => $isSupplier ? $service->supplierTaskCounts() : [],
            'ownerMetrics' => $isSupplier ? [] : $service->ownerMetrics(),
            'recentNotes' => $service->recentNotes(),
            'currentRole' => $role,
        ]);
    }
}
