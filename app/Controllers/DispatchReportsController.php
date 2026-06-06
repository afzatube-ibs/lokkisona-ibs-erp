<?php

namespace App\Controllers;

use App\ActivityLog;
use App\Database;
use App\Database\TableName;
use App\Domain\DispatchCostSnapshot;
use App\Domain\DispatchReportReference;
use App\Models\DispatchReport;
use App\Permission;
use App\Csrf;
use App\Repositories\DispatchReportRepository;
use App\Repositories\OrderItemRepository;
use App\Repositories\Write\OrderWriteRepository;
use App\Services\ReadOnly\DispatchReportReadService;
use App\ReadFoundation\WriteGate;
use App\Services\Write\DispatchReportWriteService;

class DispatchReportsController extends Controller
{
    public function index()
    {
        $this->authorize('dispatch_reports.view');
        ActivityLog::record('dispatch_reports_access', 'Dispatch Report read foundation page viewed');

        $latestReports = $this->buildLatestReports();
        $selectedReportId = (int) ($_GET['report_id'] ?? 0);
        if ($selectedReportId <= 0 && !empty($latestReports[0]['dispatch_report_id'])) {
            $selectedReportId = (int) $latestReports[0]['dispatch_report_id'];
        }

        $this->render('dispatch-reports.index', [
            'pageTitle' => 'Dispatch Reports',
            'breadcrumbs' => [
                ['label' => 'Operations', 'active' => false],
                ['label' => 'Dispatch Reports', 'active' => true],
            ],
            'accessMode' => Permission::accessMode(),
            'eligibleOrders' => $this->buildEligibleOrders(),
            'latestReports' => $latestReports,
            'reportDetail' => $this->buildReportDetail($selectedReportId > 0 ? $selectedReportId : null),
            'selectedReportId' => $selectedReportId,
            'productLineDevNote' => DispatchReportReference::PRODUCT_LINE_DEV_NOTE,
            'payableCheckpointNote' => DispatchReportReference::PAYABLE_CHECKPOINT_NOTE,
            'readInventory' => $this->buildReadInventory(),
            'currentContext' => $this->currentContext(),
            'purpose' => $this->purpose(),
            'dispatchGate' => $this->dispatchGate(),
            'eligibleRule' => $this->eligibleRule(),
            'singleSupplierRule' => $this->singleSupplierRule(),
            'batchLockRule' => $this->batchLockRule(),
            'batchReferenceRule' => $this->batchReferenceRule(),
            'costSnapshotRule' => $this->costSnapshotRule(),
            'deliveryStopRule' => $this->deliveryStopRule(),
            'reportOutputPlan' => $this->reportOutputPlan(),
            'performanceRules' => $this->performanceRules(),
            'plannedReportFields' => $this->plannedReportFields(),
            'plannedItemFields' => $this->plannedItemFields(),
            'flashSuccess' => $this->pullFlash('success'),
            'flashError' => $this->pullFlash('error'),
            'csrfField' => Csrf::field(),
            'writeGate' => WriteGate::dispatchReports(),
            'writeGateReady' => WriteGate::dispatchReports()['ready'],
        ]);
    }

    public function create()
    {
        $this->authorize('dispatch_reports.manage');
        $this->requirePost();
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Invalid security token.');
            redirect('/dispatch-reports');
        }
        $this->redirectWithWriteResult('/dispatch-reports', (new DispatchReportWriteService())->createDailyBatch($_POST));
    }

    private function buildEligibleOrders(): array
    {
        try {
            $orders = (new OrderWriteRepository())->findShippedEligible(50);
        } catch (\Throwable $e) {
            return [];
        }

        $orderItems = new OrderItemRepository();
        $eligible = [];

        foreach ($orders as $order) {
            $orderId = (int) ($order['order_id'] ?? 0);
            if ($orderId <= 0) {
                continue;
            }

            $lineCost = $orderItems->sumSupplierCostByOrderId($orderId);
            $lineQty = $orderItems->sumQuantityByOrderId($orderId);
            $snapshot = DispatchCostSnapshot::forOrder($order, $lineCost, $lineQty);

            $eligible[] = array_merge($order, [
                'preview_cost_snapshot' => $snapshot['product_cost_snapshot'],
                'preview_item_count' => $snapshot['item_count'],
            ]);
        }

        return $eligible;
    }

    private function buildLatestReports(): array
    {
        try {
            $reports = (new DispatchReportRepository())->latest(20);
            foreach ($reports as $index => $report) {
                $reports[$index]['status_label'] = DispatchReportReference::statusLabel(
                    (string) ($report['status'] ?? '')
                );
            }

            return $reports;
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function buildReportDetail(?int $reportId): ?array
    {
        if ($reportId === null || $reportId <= 0) {
            return null;
        }

        try {
            $repository = new DispatchReportRepository();
            if (!$repository->tableExists()) {
                return null;
            }

            $report = $repository->findById($reportId);
            if ($report === null) {
                return null;
            }

            $items = $repository->findItemsWithOrders($reportId);
            $orderItemRepo = new OrderItemRepository();

            foreach ($items as $index => $item) {
                $orderId = (int) ($item['order_id'] ?? 0);
                $items[$index]['product_lines'] = $orderId > 0
                    ? $orderItemRepo->findByOrderId($orderId)
                    : [];
            }

            $report['status_label'] = DispatchReportReference::statusLabel(
                (string) ($report['status'] ?? '')
            );

            return [
                'report' => $report,
                'items' => $items,
            ];
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function buildReadInventory()
    {
        $databaseStatus = Database::check();
        $defaults = [
            'database_connected' => (bool) ($databaseStatus['connected'] ?? false),
            'service_ready' => false,
            'logical_table' => DispatchReport::table(),
            'prefixed_table' => TableName::forModel(DispatchReport::class),
            'model_class' => 'DispatchReport',
            'primary_key' => DispatchReport::primaryKey(),
            'columns' => DispatchReport::columns(),
            'read_service' => 'DispatchReportReadService',
            'read_repository' => 'DispatchReportRepository',
            'table_exists' => false,
            'row_count' => 0,
            'rows' => [],
            'status' => 'error',
            'status_message' => 'Read inventory could not be loaded safely.',
        ];

        try {
            $service = new DispatchReportReadService();
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
                $defaults['status_message'] = 'Table `' . $defaults['prefixed_table'] . '` not available — migration `0006_dispatch_returns_payables.sql` not applied yet. Apply manually with the `ibs_` table prefix from config/database.php.';

                return $defaults;
            }

            $rowCount = $service->count();
            $defaults['row_count'] = $rowCount;
            $defaults['rows'] = $service->all(50, 0);

            if ($rowCount === 0) {
                $defaults['status'] = 'empty';
                $defaults['status_message'] = 'Table ready. No dispatch report records yet (read-only; no writes in this release).';

                return $defaults;
            }

            $defaults['status'] = 'ok';
            $defaults['status_message'] = 'Showing up to 50 dispatch report records (SELECT only).';

            return $defaults;
        } catch (\Throwable $e) {
            return $defaults;
        }
    }

    private function currentContext()
    {
        return [
            'supplier' => 'Iqbal & Brothers',
            'source' => 'Lokkisona.com',
            'summary' => 'Dispatch Report planning starts with Iqbal & Brothers supplier operations and the Lokkisona order workflow, but the dispatch batch model stays channel-neutral and ready for other suppliers, sales channels, manual/offline orders, payable/return handling, and future multi-business expansion.',
        ];
    }

    private function purpose()
    {
        return [
            'Define a safe dispatch batch / dispatch report model before any dispatch writes or order sync are enabled.',
            'Document the locked gate between Shipped and the next delivery stages.',
            'Plan single-supplier batches, batch locking, and batch reference formatting.',
            'Prepare cost snapshots so Product Cost Payable can be created from the snapshot later.',
            'Stay channel-neutral so manual, offline, ecommerce, and marketplace orders can share the same dispatch model.',
        ];
    }

    private function dispatchGate()
    {
        return [
            'title' => 'Dispatch Gate After Shipped',
            'summary' => 'Dispatch Report Created is the required gate after Shipped. Shipped orders cannot move forward to the next delivery stages until a dispatch report/batch is created and locked.',
            'points' => [
                'Shipped → Dispatch Report Created is a locked gate, not an optional step.',
                'Dispatch Report Created should display as a workflow stage after Shipped.',
                'Report-created / batch-locked orders cannot change normal fulfillment status.',
                'After dispatch report creation, normal manual action is blocked for those orders.',
            ],
        ];
    }

    private function eligibleRule()
    {
        return [
            'title' => 'Eligible Order Rule',
            'summary' => 'A dispatch report can only be built from eligible orders.',
            'points' => [
                'Dispatch report can only be created from Shipped orders.',
                'Orders must be unlocked / not already batch_locked.',
                'Orders already in a locked batch cannot be added to another report.',
            ],
        ];
    }

    private function singleSupplierRule()
    {
        return [
            'title' => 'Single Supplier Rule',
            'summary' => 'A dispatch batch belongs to exactly one supplier so payable and returns stay clean.',
            'points' => [
                'All selected orders in a dispatch report must belong to one supplier.',
                'Mixed-supplier batches are not allowed.',
                'Supplier and business source are captured on the report for payable and reconciliation.',
            ],
        ];
    }

    private function batchLockRule()
    {
        return [
            'title' => 'Batch Lock Rule',
            'summary' => 'Creating a dispatch report locks the selected orders into the batch.',
            'points' => [
                'Dispatch report creation locks selected orders (batch_locked).',
                'Locked orders cannot change normal fulfillment status.',
                'Locked orders cannot be moved into another dispatch report.',
                'Only an allowed exception action may apply after locking.',
            ],
        ];
    }

    private function batchReferenceRule()
    {
        return [
            'title' => 'Batch Reference Rule',
            'summary' => 'Each dispatch report carries a readable batch reference for tracking.',
            'points' => [
                'Base reference format: DDMMYYYY (example: 06062026).',
                'Additional same-day batches: DDMMYYYY-P1, DDMMYYYY-P2, DDMMYYYY-P3.',
                'Order count is stored in total_orders — never embedded in the reference string.',
                'Reference is channel-neutral and not hard-coded to any single source.',
            ],
        ];
    }

    private function costSnapshotRule()
    {
        return [
            'title' => 'Cost Snapshot / Payable Rule',
            'summary' => 'Dispatch Report is the official supplier payable checkpoint. v0.4.5.0 stores immutable snapshots only.',
            'points' => [
                'Snapshot supplier cost at dispatch report creation from orders.cost_snapshot_total and/or order_items.supplier_cost_snapshot.',
                'Never use selling_price or order_total for supplier payable snapshot.',
                'Old dispatch reports must never recalculate from latest product cost.',
                DispatchReportReference::PAYABLE_CHECKPOINT_NOTE,
                DispatchReportReference::PRODUCT_LINE_DEV_NOTE,
            ],
        ];
    }

    private function deliveryStopRule()
    {
        return [
            'title' => 'Delivery Stop Exception Rule',
            'summary' => 'After a batch is locked, only one exception action is allowed later.',
            'points' => [
                'The only exception action allowed after dispatch is Delivery Stop.',
                'Delivery Stop can later lead to Hub Return.',
                'Normal manual fulfillment actions stay blocked for locked orders.',
                'Every dispatch action later must require confirmation, note, user, timestamp, and activity log.',
            ],
        ];
    }

    private function reportOutputPlan()
    {
        return [
            'Dispatch reports should later support print, export, and download.',
            'Dispatch reports later support supplier product summary output for packing and supplier review.',
            'No real print, export, or download is implemented in this release.',
            'Output will use stored snapshot values, not live changing cost or stock.',
            'Output layout will stay channel-neutral and supplier-scoped.',
            'Invoice Printing planning owns customer invoice, packing slip, dispatch batch report, and print log rules.',
        ];
    }

    private function performanceRules()
    {
        return [
            'No background auto loops for dispatch building.',
            'No repeated fallback AJAX while creating a report.',
            'One dispatch-build request at a time.',
            'No auto retry storm.',
            'Batch order selection will be bounded to a safe page size later.',
        ];
    }

    private function plannedReportFields()
    {
        return [
            'dispatch_report_id',
            'batch_reference',
            'supplier_id',
            'business_source_id',
            'total_orders',
            'total_items',
            'total_product_cost_payable',
            'created_by',
            'created_at',
            'locked_at',
            'status',
        ];
    }

    private function plannedItemFields()
    {
        return [
            'dispatch_report_item_id',
            'dispatch_report_id',
            'order_id',
            'order_item_id',
            'product_id',
            'variant_id',
            'quantity',
            'cost_snapshot',
            'supplier_model_snapshot',
            'source_model_snapshot',
            'payable_status',
            'created_at',
        ];
    }
}
