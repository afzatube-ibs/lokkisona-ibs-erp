<?php

namespace App\Controllers;

use App\ActivityLog;
use App\Auth;
use App\SupplierContext;
use App\Services\ReadOnly\BusinessDashboardAnalyticsService;
use App\Services\ReadOnly\DashboardReadService;

class DashboardController extends Controller
{
    public function index()
    {
        $this->authorize('dashboard.view');
        ActivityLog::record('dashboard_access', 'Dashboard viewed');

        $role = Auth::role();
        $isSupplier = $role === 'supplier';
        $supplierId = $isSupplier ? SupplierContext::supplierId() : 0;
        $showRetailAmounts = !$isSupplier;

        $this->render('dashboard.index', [
            'pageTitle' => 'Dashboard',
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'active' => true],
            ],
            'isSupplierView' => $isSupplier,
            'showRetailAmounts' => $showRetailAmounts,
            'dashboardAnalytics' => (new BusinessDashboardAnalyticsService())->build($supplierId, $showRetailAmounts),
            'recentNotes' => (new DashboardReadService())->recentNotes(),
            'currentRole' => $role,
            'welcomeDate' => date('l, d F Y'),
        ]);
    }
}
