<?php

namespace App\Controllers;

use App\ActivityLog;
use App\Auth;
use App\SupplierContext;
use App\Services\ReadOnly\DashboardReadService;
use App\Services\ReadOnly\SupplierIntelligenceDashboardService;

class DashboardController extends Controller
{
    public function index()
    {
        $this->authorize('dashboard.view');
        ActivityLog::record('dashboard_access', 'Dashboard viewed');

        $role = Auth::role();
        $isSupplier = SupplierContext::isSupplier();
        $supplierId = $isSupplier ? SupplierContext::supplierId() : (int) config('auth.supplier_id', 1);
        $showRetailAmounts = !$isSupplier;

        $this->render('dashboard.index', [
            'pageTitle' => 'Supplier Intelligence',
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'active' => true],
            ],
            'isSupplierView' => $isSupplier,
            'showRetailAmounts' => $showRetailAmounts,
            'supplierIntelligence' => (new SupplierIntelligenceDashboardService())->build($supplierId, $showRetailAmounts),
            'supplierDisplayName' => 'Iqbal & Brothers (IBS)',
            'businessSourceLabel' => 'Lokkisona.com',
            'recentNotes' => (new DashboardReadService())->recentNotes(),
            'currentRole' => $role,
        ]);
    }
}
