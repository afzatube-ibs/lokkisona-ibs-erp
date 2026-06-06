<?php

namespace App\Controllers;

use App\ActivityLog;
use App\Csrf;
use App\Domain\SettlementWorkflowStatus;
use App\Permission;
use App\SupplierContext;
use App\ReadFoundation\WriteGate;
use App\Repositories\SupplierRepository;
use App\Repositories\Write\SettlementWriteRepository;
use App\Services\Write\SettlementWriteService;

class SettlementsController extends Controller
{
    public function index()
    {
        $this->authorize('settlements.view');
        ActivityLog::record('settlements_access', 'Supplier settlement workflow page viewed');

        $supplierId = SupplierContext::enforceSupplierId((int) ($_GET['supplier_id'] ?? 0));
        $repo = new SettlementWriteRepository();
        $rows = $repo->listRecent(50, $supplierId);

        $this->render('settlements.index', [
            'pageTitle' => 'Settlements',
            'breadcrumbs' => [
                ['label' => 'Operations', 'active' => false],
                ['label' => 'Settlements', 'active' => true],
            ],
            'accessMode' => Permission::accessMode(),
            'settlements' => $rows,
            'suppliers' => SupplierContext::canSelectSupplier() ? $this->loadSuppliers() : [],
            'selectedSupplierId' => $supplierId,
            'canSelectSupplier' => SupplierContext::canSelectSupplier(),
            'isSupplierView' => SupplierContext::isSupplier(),
            'periodTypes' => SettlementWorkflowStatus::periodTypes(),
            'workflowLabels' => SettlementWorkflowStatus::labels(),
            'flashSuccess' => $this->pullFlash('success'),
            'flashError' => $this->pullFlash('error'),
            'csrfField' => Csrf::field(),
            'writeGate' => WriteGate::settlements(),
            'writeGateReady' => WriteGate::settlements()['ready'],
            'canManage' => Permission::can('settlements.manage'),
        ]);
    }

    public function prepare()
    {
        $this->authorize('settlements.manage');
        $this->requirePost();
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Invalid security token.');
            redirect('/settlements');
        }
        $this->redirectWithWriteResult('/settlements', (new SettlementWriteService())->prepare($_POST));
    }

    public function approve()
    {
        $this->authorize('settlements.manage');
        $this->requirePost();
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Invalid security token.');
            redirect('/settlements');
        }
        $id = (int) ($_POST['settlement_id'] ?? 0);
        $this->redirectWithWriteResult('/settlements', (new SettlementWriteService())->approve($id));
    }

    public function markPaid()
    {
        $this->authorize('settlements.manage');
        $this->requirePost();
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Invalid security token.');
            redirect('/settlements');
        }
        $id = (int) ($_POST['settlement_id'] ?? 0);
        $this->redirectWithWriteResult('/settlements', (new SettlementWriteService())->markPaid($id));
    }

    public function close()
    {
        $this->authorize('settlements.manage');
        $this->requirePost();
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Invalid security token.');
            redirect('/settlements');
        }
        $id = (int) ($_POST['settlement_id'] ?? 0);
        $this->redirectWithWriteResult('/settlements', (new SettlementWriteService())->close($id));
    }

    private function loadSuppliers(): array
    {
        try {
            $repo = new SupplierRepository();
            if (!$repo->tableExists()) {
                return [];
            }

            return $repo->all(100, 0);
        } catch (\Throwable $e) {
            return [];
        }
    }
}
