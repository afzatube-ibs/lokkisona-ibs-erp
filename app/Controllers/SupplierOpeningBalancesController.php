<?php

namespace App\Controllers;

use App\ActivityLog;
use App\Database;
use App\Database\TableName;
use App\Models\LaunchCutover;
use App\Models\SupplierOpeningBalance;
use App\Csrf;
use App\Permission;
use App\Services\Write\LaunchCutoverWriteService;
use App\Services\Write\SupplierOpeningBalanceWriteService;
use App\Services\ReadOnly\LaunchCutoverReadService;
use App\Services\ReadOnly\SupplierOpeningBalanceReadService;

class SupplierOpeningBalancesController extends Controller
{
    public function index()
    {
        $this->authorize('supplier_opening_balances.view');
        ActivityLog::record('supplier_opening_balances_access', 'Supplier Opening Balances read foundation page viewed');

        $this->render('supplier-opening-balances.index', [
            'pageTitle' => 'Supplier Opening Balances',
            'breadcrumbs' => [
                ['label' => 'Suppliers', 'active' => false],
                ['label' => 'Supplier Opening Balances', 'active' => true],
            ],
            'accessMode' => Permission::accessMode(),
            'openingBalanceReadInventory' => $this->buildOpeningBalanceReadInventory(),
            'launchCutoverReadInventory' => $this->buildLaunchCutoverReadInventory(),
            'rules' => $this->rules(),
            'balanceTypes' => $this->balanceTypes(),
            'openingBalanceFields' => $this->openingBalanceFields(),
            'adjustmentFields' => $this->adjustmentFields(),
            'auditFields' => $this->auditFields(),
            'launchChecklist' => $this->launchChecklist(),
            'launchCutoverFields' => $this->launchCutoverFields(),
            'exampleBalance' => $this->exampleBalance(),
            'flashSuccess' => $this->pullFlash('success'),
            'flashError' => $this->pullFlash('error'),
            'csrfField' => Csrf::field(),
            'launchLocked' => (new LaunchCutoverWriteService())->isLocked(),
        ]);
    }

    public function create()
    {
        $this->authorize('supplier_opening_balances.manage');
        $this->requirePost();
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Invalid security token.');
            redirect('/supplier-opening-balances');
        }
        $this->redirectWithWriteResult('/supplier-opening-balances', (new SupplierOpeningBalanceWriteService())->create($_POST));
    }

    public function approve()
    {
        $this->authorize('supplier_opening_balances.approve');
        $this->requirePost();
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Invalid security token.');
            redirect('/supplier-opening-balances');
        }
        $id = (int) ($_POST['supplier_opening_balance_id'] ?? 0);
        $this->redirectWithWriteResult('/supplier-opening-balances', (new SupplierOpeningBalanceWriteService())->approve($id));
    }

    public function launchLock()
    {
        $this->authorize('supplier_opening_balances.approve');
        $this->requirePost();
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Invalid security token.');
            redirect('/supplier-opening-balances');
        }
        $this->redirectWithWriteResult('/supplier-opening-balances', (new LaunchCutoverWriteService())->lock($_POST));
    }

    private function buildOpeningBalanceReadInventory()
    {
        return $this->buildEntityReadInventory(
            SupplierOpeningBalance::class,
            SupplierOpeningBalanceReadService::class,
            'SupplierOpeningBalance',
            'SupplierOpeningBalanceReadService',
            'SupplierOpeningBalanceRepository',
            'supplier opening balance'
        );
    }

    private function buildLaunchCutoverReadInventory()
    {
        return $this->buildEntityReadInventory(
            LaunchCutover::class,
            LaunchCutoverReadService::class,
            'LaunchCutover',
            'LaunchCutoverReadService',
            'LaunchCutoverRepository',
            'launch cutover'
        );
    }

    private function buildEntityReadInventory(
        string $modelClass,
        string $serviceClass,
        string $modelShortName,
        string $readServiceName,
        string $readRepositoryName,
        string $recordLabel
    ) {
        $databaseStatus = Database::check();
        $defaults = [
            'database_connected' => (bool) ($databaseStatus['connected'] ?? false),
            'service_ready' => false,
            'logical_table' => $modelClass::table(),
            'prefixed_table' => TableName::forModel($modelClass),
            'model_class' => $modelShortName,
            'primary_key' => $modelClass::primaryKey(),
            'columns' => $modelClass::columns(),
            'read_service' => $readServiceName,
            'read_repository' => $readRepositoryName,
            'table_exists' => false,
            'row_count' => 0,
            'rows' => [],
            'status' => 'error',
            'status_message' => 'Read inventory could not be loaded safely.',
        ];

        try {
            $service = new $serviceClass();
            $defaults['service_ready'] = true;

            if (!$defaults['database_connected']) {
                $defaults['status'] = 'not_connected';
                $defaults['status_message'] = 'Database not connected. Read inventory unavailable.';

                return $defaults;
            }

            $tableExists = $service->tableExists();
            $defaults['table_exists'] = $tableExists;

            if (!$tableExists) {
                $defaults['status'] = 'table_missing';
                $defaults['status_message'] = 'Table `' . $defaults['prefixed_table'] . '` not available — migration `0008_supplier_opening_balances_launch_cutovers.sql` not applied yet. Apply manually with the `ibs_` table prefix from config/database.php.';

                return $defaults;
            }

            $rowCount = $service->count();
            $defaults['row_count'] = $rowCount;
            $defaults['rows'] = $service->all(50, 0);

            if ($rowCount === 0) {
                $defaults['status'] = 'empty';
                $defaults['status_message'] = 'Table ready. No ' . $recordLabel . ' records yet (read-only; no writes in this release).';

                return $defaults;
            }

            $defaults['status'] = 'ok';
            $defaults['status_message'] = 'Showing up to 50 ' . $recordLabel . ' records (SELECT only).';

            return $defaults;
        } catch (\Throwable $e) {
            return $defaults;
        }
    }

    private function rules()
    {
        return [
            [
                'title' => 'Opening Balance Purpose',
                'points' => [
                    'Capture old/manual supplier payable or advance as the ERP starting balance.',
                    'Separate pre-launch history from new ERP transactions after go-live.',
                    'Document the plan only; no payable ledger records are created in this release.',
                ],
            ],
            [
                'title' => 'Not Normal Order Payable',
                'points' => [
                    'Old supplier balance must not be entered as a normal order payable.',
                    'Normal Product Cost Payable should start only after the launch cut-off date.',
                    'Opening balance keeps the starting ledger clean and auditable.',
                ],
            ],
            [
                'title' => 'Payable and Advance Opening Balance',
                'points' => [
                    'Payable to supplier means the business starts owing the supplier.',
                    'Advance from supplier means the supplier starts owing/crediting the business.',
                    'Neutral zero start means no old balance is carried into launch.',
                ],
            ],
            [
                'title' => 'Cutoff Date Rule',
                'points' => [
                    'Cutoff date is the day before ERP real launch.',
                    'Old manual calculations belong before or on the cutoff date.',
                    'New ERP transactions start after the cutoff date only.',
                ],
            ],
            [
                'title' => 'Supplier and Source Rule',
                'points' => [
                    'Opening balance must select the supplier, starting with Iqbal & Brothers.',
                    'Balance can apply to one business source or all sources when old records are combined.',
                    'Source choice must be approved before launch lock.',
                ],
            ],
            [
                'title' => 'Reference and Proof Planning',
                'points' => [
                    'Calculation summary and reference note should explain old product costs, received amounts, return deductions, and manual adjustments.',
                    'Proof attachment paths and file names are planned for later.',
                    'This page does not upload files or write attachment records.',
                ],
            ],
            [
                'title' => 'Owner Approval and Audit Rule',
                'points' => [
                    'Owner approval is required before locking opening balance after launch.',
                    'Entry, approval, adjustment, and lock actions should be auditable later.',
                    'Supplier opening balance approval is separate from normal payable workflow approval.',
                ],
            ],
            [
                'title' => 'Adjustment Safety Rule',
                'points' => [
                    'Opening balance adjustments must be requested and owner-approved.',
                    'Adjustment reason, previous amount, and new amount should be preserved.',
                    'Adjustments after launch must not silently rewrite starting balance history.',
                ],
            ],
        ];
    }

    private function balanceTypes()
    {
        return [
            'payable_to_supplier',
            'advance_from_supplier',
            'neutral_zero_start',
        ];
    }

    private function openingBalanceFields()
    {
        return [
            'supplier_opening_balance_id',
            'supplier_id',
            'business_source_id',
            'applies_to_all_sources',
            'balance_type',
            'amount',
            'currency_code',
            'cutoff_date',
            'calculation_summary',
            'reference_note',
            'proof_file_path',
            'proof_file_name',
            'owner_approval_status',
            'owner_approved_by',
            'owner_approved_at',
            'entered_by',
            'entered_at',
            'locked_after_launch',
            'status',
            'created_at',
            'updated_at',
        ];
    }

    private function adjustmentFields()
    {
        return [
            'supplier_opening_balance_adjustment_id',
            'supplier_opening_balance_id',
            'adjustment_type',
            'adjustment_amount',
            'adjustment_reason',
            'previous_amount',
            'new_amount',
            'requested_by',
            'approved_by',
            'approved_at',
            'status',
            'created_at',
        ];
    }

    private function auditFields()
    {
        return [
            'supplier_opening_balance_audit_id',
            'supplier_opening_balance_id',
            'action',
            'previous_status',
            'new_status',
            'actor_user_id',
            'actor_role',
            'note',
            'created_at',
        ];
    }

    private function launchChecklist()
    {
        return [
            'choose ERP go-live date',
            'choose cutoff date',
            'confirm old manual payable calculation',
            'confirm supplier',
            'confirm business source/all sources',
            'upload/support proof later',
            'owner approval',
            'lock opening balance after launch',
            'start new ERP transactions after cutoff only',
            'no mixing old manual payable with new dispatch payable',
        ];
    }

    private function launchCutoverFields()
    {
        return [
            'launch_cutover_id',
            'cutover_reference',
            'go_live_date',
            'cutoff_date',
            'primary_supplier_id',
            'opening_balance_confirmed',
            'product_control_ready',
            'status_mapping_ready',
            'sync_preview_ready',
            'manual_orders_ready',
            'dispatch_workflow_ready',
            'return_workflow_ready',
            'payable_workflow_ready',
            'invoice_printing_ready',
            'owner_approved_by',
            'owner_approved_at',
            'status',
            'created_at',
            'updated_at',
        ];
    }

    private function exampleBalance()
    {
        return [
            'supplier' => 'Iqbal & Brothers',
            'balance_type' => 'Payable to Supplier',
            'estimated_amount' => '1,200,000 BDT',
            'cutoff_date' => 'Day before ERP real launch',
            'status' => 'Owner approval required before locking',
        ];
    }
}
