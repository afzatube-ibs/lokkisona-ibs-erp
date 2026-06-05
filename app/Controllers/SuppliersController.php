<?php

namespace App\Controllers;

use App\ActivityLog;
use App\Database;
use App\Database\TableName;
use App\Models\Supplier;
use App\Permission;
use App\Csrf;
use App\Services\ReadOnly\SupplierReadService;
use App\Services\Write\SupplierWriteService;

class SuppliersController extends Controller
{
    public function index()
    {
        $this->authorize('suppliers.view');
        ActivityLog::record('suppliers_access', 'Suppliers read foundation page viewed');

        $this->render('suppliers.index', [
            'pageTitle' => 'Suppliers',
            'breadcrumbs' => [
                ['label' => 'Operations', 'active' => false],
                ['label' => 'Suppliers', 'active' => true],
            ],
            'accessMode' => Permission::accessMode(),
            'readInventory' => $this->buildReadInventory(),
            'primarySupplier' => $this->primarySupplier(),
            'operationPurpose' => $this->operationPurpose(),
            'foundationSections' => $this->foundationSections(),
            'plannedFields' => $this->plannedFields(),
            'accountingTerms' => $this->accountingTerms(),
            'flashSuccess' => $this->pullFlash('success'),
            'flashError' => $this->pullFlash('error'),
            'csrfField' => Csrf::field(),
            'writeServiceReady' => (new SupplierWriteService())->tableReady(),
        ]);
    }

    public function create()
    {
        $this->authorize('suppliers.manage');
        $this->requirePost();
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Invalid security token.');
            redirect('/suppliers');
        }
        $this->redirectWithWriteResult('/suppliers', (new SupplierWriteService())->create($_POST));
    }

    public function edit()
    {
        $this->authorize('suppliers.manage');
        $this->requirePost();
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Invalid security token.');
            redirect('/suppliers');
        }
        $id = (int) ($_POST['supplier_id'] ?? 0);
        $this->redirectWithWriteResult('/suppliers', (new SupplierWriteService())->applyEdit($id, $_POST));
    }

    private function buildReadInventory()
    {
        $databaseStatus = Database::check();
        $defaults = [
            'database_connected' => (bool) ($databaseStatus['connected'] ?? false),
            'service_ready' => false,
            'logical_table' => Supplier::table(),
            'prefixed_table' => TableName::forModel(Supplier::class),
            'model_class' => 'Supplier',
            'primary_key' => Supplier::primaryKey(),
            'columns' => Supplier::columns(),
            'read_service' => 'SupplierReadService',
            'read_repository' => 'SupplierRepository',
            'table_exists' => false,
            'row_count' => 0,
            'rows' => [],
            'status' => 'error',
            'status_message' => 'Read inventory could not be loaded safely.',
        ];

        try {
            $service = new SupplierReadService();
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
                $defaults['status_message'] = 'Table `' . $defaults['prefixed_table'] . '` not available — migration `0003_business_sources_suppliers_products.sql` not applied yet. Apply manually with the `ibs_` table prefix from config/database.php.';

                return $defaults;
            }

            $rowCount = $service->count();
            $defaults['row_count'] = $rowCount;
            $defaults['rows'] = $service->all(50, 0);

            if ($rowCount === 0) {
                $defaults['status'] = 'empty';
                $defaults['status_message'] = 'Table ready. No supplier records yet (read-only; no writes in this release).';

                return $defaults;
            }

            $defaults['status'] = 'ok';
            $defaults['status_message'] = 'Showing up to 50 supplier records (SELECT only).';

            return $defaults;
        } catch (\Throwable $e) {
            return $defaults;
        }
    }

    private function primarySupplier()
    {
        return [
            'name' => 'Iqbal & Brothers',
            'role' => 'Current primary supplier',
            'channel' => 'Default supplier operations with Lokkisona order workflow',
            'summary' => 'Operations start with Iqbal & Brothers, but the architecture stays channel-neutral for other suppliers, sales channels, and businesses.',
        ];
    }

    private function operationPurpose()
    {
        return [
            'Maintain a clear record of each supplier the business buys product from.',
            'Track product cost payable and settlement per supplier without hard-coding one channel.',
            'Connect supplier operations to orders, returns, and payable workflows in future releases.',
            'Stay ready for multiple suppliers, sales channels, manual/offline orders, and multi-business expansion.',
        ];
    }

    private function foundationSections()
    {
        return [
            [
                'title' => 'Future supplier account structure',
                'description' => 'Each supplier will have its own account profile, contact details, payment terms, and status, independent of any single sales channel.',
            ],
            [
                'title' => 'Future payable / settlement link',
                'description' => 'Supplier accounts will link to a payable ledger covering product cost payable, supplier invoices, additional payable, payments made, and net payable to supplier.',
            ],
            [
                'title' => 'Future product cost / stock link',
                'description' => 'Suppliers will connect to product cost and stock records so product cost payable is calculated from real purchase and stock movement data.',
            ],
            [
                'title' => 'Future order fulfillment link',
                'description' => 'Suppliers will link to order fulfillment so each order or dispatch knows which supplier provided the product, across manual, offline, and channel orders.',
            ],
            [
                'title' => 'Future return / damage deduction link',
                'description' => 'Supplier returns and damage will reduce the net payable through a clear return/damage deduction, separate from owner-side returns.',
            ],
            [
                'title' => 'Future multi-supplier / multi-business readiness',
                'description' => 'The structure supports many suppliers, multiple businesses, and multiple sales channels so no single supplier or channel is hard-coded.',
            ],
        ];
    }

    private function plannedFields()
    {
        return [
            'supplier name',
            'contact person',
            'phone',
            'email',
            'address',
            'payment terms',
            'payable balance',
            'status',
            'linked business/channel',
            'created at',
            'updated at',
        ];
    }

    private function accountingTerms()
    {
        return [
            'Product Cost Payable',
            'Supplier Invoice',
            'Additional Payable',
            'Return/Damage Deduction',
            'Payment Made to Supplier',
            'Advance Received from Supplier',
            'Net Payable to Supplier',
        ];
    }
}
