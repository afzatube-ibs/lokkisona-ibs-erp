<?php

namespace App\Controllers;

use App\ActivityLog;
use App\Permission;
use App\SupplierContext;
use App\Repositories\SupplierRepository;
use App\Services\ReadOnly\SupplierReportsReadService;

class ReportsController extends Controller
{
    public function index()
    {
        $this->authorize('supplier_payables.view');
        ActivityLog::record('reports_access', 'Supplier reports page viewed');

        $reportKey = trim((string) ($_GET['report'] ?? ''));
        $supplierId = SupplierContext::enforceSupplierId((int) ($_GET['supplier_id'] ?? 0));
        $month = trim((string) ($_GET['month'] ?? date('Y-m')));

        $service = new SupplierReportsReadService();
        $definitions = $service->definitions();
        if (SupplierContext::isSupplier()) {
            unset($definitions['activity_log']);
        }
        $reportData = $reportKey !== '' ? $service->run($reportKey, $supplierId, $month) : null;

        $this->render('reports.index', [
            'pageTitle' => 'Reports',
            'breadcrumbs' => [
                ['label' => 'Operations', 'active' => false],
                ['label' => 'Reports', 'active' => true],
            ],
            'definitions' => $definitions,
            'reportKey' => $reportKey,
            'reportData' => $reportData,
            'suppliers' => SupplierContext::canSelectSupplier() ? $this->loadSuppliers() : [],
            'selectedSupplierId' => $supplierId,
            'selectedMonth' => $month,
            'canSelectSupplier' => SupplierContext::canSelectSupplier(),
            'isSupplierView' => SupplierContext::isSupplier(),
            'accessMode' => Permission::accessMode(),
        ]);
    }

    private function loadSuppliers(): array
    {
        try {
            $repo = new SupplierRepository();
            if (!$repo->tableExists()) {
                return [];
            }

            return $repo->all(50, 0);
        } catch (\Throwable $e) {
            return [];
        }
    }
}
