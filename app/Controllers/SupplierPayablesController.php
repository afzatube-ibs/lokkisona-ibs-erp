<?php

namespace App\Controllers;

use App\ActivityLog;
use App\Csrf;
use App\Database;
use App\Database\TableName;
use App\Domain\PayableLedgerType;
use App\Models\PayableLedger;
use App\Permission;
use App\ReadFoundation\WriteGate;
use App\Repositories\SupplierRepository;
use App\Repositories\Write\PayableLedgerWriteRepository;
use App\Repositories\Write\ReturnReceiveWriteRepository;
use App\Services\ReadOnly\PayableLedgerReadService;
use App\Services\Write\PayableLedgerWriteService;
use App\Services\Write\ReturnBatchWriteService;

class SupplierPayablesController extends Controller
{
    public function index()
    {
        $this->authorize('supplier_payables.view');
        ActivityLog::record('supplier_payables_access', 'Supplier Payable ledger page viewed');

        $supplierId = (int) ($_GET['supplier_id'] ?? 0);
        $ledgerService = new PayableLedgerReadService();
        $ledgerRows = $supplierId > 0
            ? $ledgerService->forSupplier($supplierId, 200)
            : $ledgerService->all(200);

        $this->render('supplier-payables.index', [
            'pageTitle' => 'Supplier Payables',
            'breadcrumbs' => [
                ['label' => 'Operations', 'active' => false],
                ['label' => 'Supplier Payables', 'active' => true],
            ],
            'accessMode' => Permission::accessMode(),
            'readInventory' => $this->buildReadInventory(),
            'ledgerRows' => $ledgerRows,
            'ledgerSummary' => $ledgerService->summary(),
            'suppliers' => $this->loadSuppliers(),
            'supplierReturns' => $this->loadSupplierReturnsForDeduction(),
            'eligibleReturnBatches' => $this->loadEligibleReturnBatchesForDeduction(),
            'selectedSupplierId' => $supplierId,
            'ledgerTypes' => PayableLedgerType::labels(),
            'manualEntryTypes' => PayableLedgerType::manualEntryTypes(),
            'netPayableFormula' => $this->netPayableFormula(),
            'flashSuccess' => $this->pullFlash('success'),
            'flashError' => $this->pullFlash('error'),
            'csrfField' => Csrf::field(),
            'writeGate' => WriteGate::supplierPayables(),
            'writeGateReady' => WriteGate::supplierPayables()['ready'],
            'canManage' => Permission::can('supplier_payables.manage'),
        ]);
    }

    public function createManual()
    {
        $this->authorize('supplier_payables.manage');
        $this->requirePost();
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Invalid security token.');
            redirect('/supplier-payables');
        }
        $this->redirectWithWriteResult('/supplier-payables', (new PayableLedgerWriteService())->createManualEntry($_POST));
    }

    public function approve()
    {
        $this->authorize('supplier_payables.manage');
        $this->requirePost();
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Invalid security token.');
            redirect('/supplier-payables');
        }
        $id = (int) ($_POST['payable_ledger_id'] ?? 0);
        $this->redirectWithWriteResult('/supplier-payables', (new PayableLedgerWriteService())->approve($id));
    }

    public function reject()
    {
        $this->authorize('supplier_payables.manage');
        $this->requirePost();
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Invalid security token.');
            redirect('/supplier-payables');
        }
        $id = (int) ($_POST['payable_ledger_id'] ?? 0);
        $this->redirectWithWriteResult('/supplier-payables', (new PayableLedgerWriteService())->reject($id));
    }

    public function postFromDispatch()
    {
        $this->authorize('supplier_payables.manage');
        $this->requirePost();
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Invalid security token.');
            redirect('/supplier-payables');
        }
        $dispatchReportId = (int) ($_POST['dispatch_report_id'] ?? 0);
        $this->redirectWithWriteResult('/supplier-payables', (new PayableLedgerWriteService())->createDraftFromDispatch($dispatchReportId));
    }

    public function postFromReturnBatch()
    {
        $this->authorize('supplier_payables.manage');
        $this->requirePost();
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Invalid security token.');
            redirect('/supplier-payables');
        }
        $returnBatchId = (int) ($_POST['return_batch_id'] ?? 0);
        $this->redirectWithWriteResult('/supplier-payables', (new PayableLedgerWriteService())->createDraftFromReturnBatch($returnBatchId));
    }

    private function buildReadInventory()
    {
        $databaseStatus = Database::check();
        $defaults = [
            'database_connected' => (bool) ($databaseStatus['connected'] ?? false),
            'service_ready' => false,
            'logical_table' => PayableLedger::table(),
            'prefixed_table' => TableName::forModel(PayableLedger::class),
            'model_class' => 'PayableLedger',
            'primary_key' => PayableLedger::primaryKey(),
            'columns' => PayableLedger::columns(),
            'read_service' => 'PayableLedgerReadService',
            'read_repository' => 'PayableLedgerRepository',
            'table_exists' => false,
            'row_count' => 0,
            'rows' => [],
            'status' => 'error',
            'status_message' => 'Read inventory could not be loaded safely.',
        ];

        try {
            $service = new PayableLedgerReadService();
            $defaults['service_ready'] = true;

            if (!$defaults['database_connected']) {
                $defaults['status'] = 'not_connected';
                $defaults['status_message'] = 'Database not connected.';

                return $defaults;
            }

            $tableExists = $service->tableExists();
            $defaults['table_exists'] = $tableExists;

            if (!$tableExists) {
                $defaults['status'] = 'table_missing';
                $defaults['status_message'] = 'Table not available — apply migration 0006 manually.';

                return $defaults;
            }

            $rowCount = $service->count();
            $defaults['row_count'] = $rowCount;
            $defaults['rows'] = $service->all(20, 0);
            $defaults['status'] = $rowCount === 0 ? 'empty' : 'ok';
            $defaults['status_message'] = $rowCount === 0
                ? 'Table ready. No ledger entries yet.'
                : 'Developer read sample (collapsed below).';

            return $defaults;
        } catch (\Throwable $e) {
            return $defaults;
        }
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

    private function loadSupplierReturnsForDeduction(): array
    {
        try {
            $repo = new ReturnReceiveWriteRepository();
            if (!$repo->tableExists()) {
                return [];
            }

            $table = TableName::forModel(\App\Models\ReturnReceive::class);
            $pdo = \App\Database\Connection::pdo();
            $sql = 'SELECT return_receive_id, return_reference, supplier_id, return_type, total_cost_snapshot, status '
                . 'FROM `' . str_replace('`', '``', $table) . '` '
                . 'WHERE status = :status AND return_type IN (:type1, :type2) '
                . 'ORDER BY return_receive_id DESC LIMIT 50';
            $statement = $pdo->prepare($sql);
            $statement->execute([
                'status' => 'received',
                'type1' => 'hub_courier_return',
                'type2' => 'customer_return_to_supplier',
            ]);

            return $statement->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Owner-approved return batches that don't yet have a Return / Damage Deduction draft.
     * Surfaced so the owner can create the deduction draft with one click (still owner-gated).
     *
     * @return array<int, array<string, mixed>>
     */
    private function loadEligibleReturnBatchesForDeduction(): array
    {
        try {
            $batches = (new ReturnBatchWriteService())->listLatest(50);
            if ($batches === []) {
                return [];
            }

            $ledgers = new PayableLedgerWriteRepository();
            $ledgerReady = $ledgers->tableExists();
            $eligible = [];

            foreach ($batches as $batch) {
                if (($batch['status'] ?? '') !== 'owner_approved') {
                    continue;
                }

                if ((float) ($batch['total_adjustment_amount'] ?? 0) <= 0) {
                    continue;
                }

                $reference = (string) ($batch['return_batch_reference'] ?? '');
                if ($ledgerReady && $reference !== '') {
                    $existing = $ledgers->findBySourceAndType($reference, PayableLedgerType::RETURN_DEDUCTION);
                    if ($existing !== null) {
                        continue;
                    }
                }

                $eligible[] = $batch;
            }

            return $eligible;
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function netPayableFormula(): array
    {
        return [
            'summary' => 'Net Payable = Opening Balance + Product Cost Payable + Supplier Invoice + Additional Payable + Debit Adjustment − Return Deduction − Payment Made − Advance Received − Credit Adjustment',
            'points' => [
                'Product Cost Payable uses locked dispatch cost snapshot only.',
                'Return deductions require receive confirmation and owner approval.',
                'All financial entries are draft until owner posts them.',
            ],
        ];
    }
}
