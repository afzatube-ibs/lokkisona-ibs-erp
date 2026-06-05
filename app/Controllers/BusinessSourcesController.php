<?php

namespace App\Controllers;

use App\ActivityLog;
use App\Database;
use App\Database\TableName;
use App\Models\BusinessSource;
use App\Permission;
use App\Csrf;
use App\Services\ReadOnly\BusinessSourceReadService;
use App\ReadFoundation\WriteGate;
use App\Services\Write\BusinessSourceWriteService;

class BusinessSourcesController extends Controller
{
    public function index()
    {
        $this->authorize('business_sources.view');
        ActivityLog::record('business_sources_access', 'Business Sources read foundation page viewed');

        $this->render('business-sources.index', [
            'pageTitle' => 'Business Sources',
            'breadcrumbs' => [
                ['label' => 'Operations', 'active' => false],
                ['label' => 'Business Sources', 'active' => true],
            ],
            'accessMode' => Permission::accessMode(),
            'readInventory' => $this->buildReadInventory(),
            'currentSource' => $this->currentSource(),
            'primarySupplierRelationship' => $this->primarySupplierRelationship(),
            'foundationSections' => $this->foundationSections(),
            'plannedFields' => $this->plannedFields(),
            'sourceTypes' => $this->sourceTypes(),
            'plannedBusinessSources' => $this->plannedBusinessSources(),
            'flashSuccess' => $this->pullFlash('success'),
            'flashError' => $this->pullFlash('error'),
            'csrfField' => Csrf::field(),
            'writeGate' => WriteGate::businessSources(),
            'writeGateReady' => WriteGate::businessSources()['ready'],
        ]);
    }

    public function create()
    {
        $this->authorize('business_sources.manage');
        $this->requirePost();
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Invalid security token.');
            redirect('/business-sources');
        }
        $this->redirectWithWriteResult('/business-sources', (new BusinessSourceWriteService())->create($_POST));
    }

    public function edit()
    {
        $this->authorize('business_sources.manage');
        $this->requirePost();
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Invalid security token.');
            redirect('/business-sources');
        }
        $id = (int) ($_POST['business_source_id'] ?? 0);
        $this->redirectWithWriteResult('/business-sources', (new BusinessSourceWriteService())->applyEdit($id, $_POST));
    }

    private function buildReadInventory()
    {
        $databaseStatus = Database::check();
        $defaults = [
            'database_connected' => (bool) ($databaseStatus['connected'] ?? false),
            'service_ready' => false,
            'logical_table' => BusinessSource::table(),
            'prefixed_table' => TableName::forModel(BusinessSource::class),
            'model_class' => 'BusinessSource',
            'primary_key' => BusinessSource::primaryKey(),
            'columns' => BusinessSource::columns(),
            'read_service' => 'BusinessSourceReadService',
            'read_repository' => 'BusinessSourceRepository',
            'table_exists' => false,
            'row_count' => 0,
            'rows' => [],
            'status' => 'error',
            'status_message' => 'Read inventory could not be loaded safely.',
        ];

        try {
            $service = new BusinessSourceReadService();
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
                $defaults['status_message'] = 'Table ready. No business source records yet (read-only; no writes in this release).';

                return $defaults;
            }

            $defaults['status'] = 'ok';
            $defaults['status_message'] = 'Showing up to 50 business source records (SELECT only).';

            return $defaults;
        } catch (\Throwable $e) {
            return $defaults;
        }
    }

    private function currentSource()
    {
        return [
            'name' => 'Lokkisona.com',
            'type' => 'Ecommerce Website',
            'label' => 'Current primary order source',
            'summary' => 'The first workflow starts with Lokkisona.com orders, but the ERP must stay ready for other channels, manual orders, and future business sources.',
        ];
    }

    private function primarySupplierRelationship()
    {
        return [
            'supplier' => 'Iqbal & Brothers',
            'relationship' => 'Current primary supplier relationship',
            'summary' => 'Orders from Lokkisona.com currently connect to Iqbal & Brothers supplier operations, with future support for other suppliers and workflows.',
        ];
    }

    private function foundationSections()
    {
        return [
            [
                'title' => 'Future source / channel structure',
                'description' => 'Each business source will identify its business, channel name, source type, default supplier, and default workflow without hard-coding one website.',
            ],
            [
                'title' => 'Manual / offline order support',
                'description' => 'Manual orders, phone orders, showroom orders, and offline retail orders will be able to enter the same order workflow as ecommerce orders.',
            ],
            [
                'title' => 'Ecommerce channel support',
                'description' => 'Ecommerce websites such as Lokkisona.com will be modeled as channels so orders can keep their source label and workflow routing.',
            ],
            [
                'title' => 'Marketplace / channel support',
                'description' => 'Future marketplaces and external sales channels can be added as separate sources while still connecting to supplier, dispatch, return, and payable operations.',
            ],
            [
                'title' => 'Multi-business readiness',
                'description' => 'The foundation supports future businesses and channels beyond the first Lokkisona workflow, keeping supplier and accounting operations reusable.',
            ],
            [
                'title' => 'Future operations connection',
                'description' => 'Orders will later connect their source to supplier workflow, dispatch, returns, and payable so each channel can route work without custom one-off code.',
            ],
        ];
    }

    private function plannedFields()
    {
        return [
            'business name',
            'channel name',
            'source type',
            'website/domain',
            'order source label',
            'status',
            'default supplier',
            'default workflow',
            'created at',
            'updated at',
        ];
    }

    private function sourceTypes()
    {
        return [
            'Ecommerce Website',
            'Manual Order',
            'Offline Retail',
            'Marketplace',
            'Wholesale',
            'Other',
        ];
    }

    private function plannedBusinessSources()
    {
        return [
            [
                'name' => 'Lokkisona.com',
                'platform' => 'OpenCart',
                'note' => 'Primary ecommerce source — Lokkisona-specific courier mapping and ERP Lokkisona-style invoice later.',
            ],
            [
                'name' => 'Sonamoni.com.bd',
                'platform' => 'WooCommerce',
                'note' => 'Future WooCommerce source — separate courier mapping; manual/external reference entry first; direct sync later.',
            ],
            [
                'name' => 'Manual / Offline Order',
                'platform' => 'Manual',
                'note' => 'External reference order entry — ERP manual invoice later; same supplier workflow/payable/stock logic.',
            ],
        ];
    }
}
