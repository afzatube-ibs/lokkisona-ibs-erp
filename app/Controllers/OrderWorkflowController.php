<?php

namespace App\Controllers;

use App\ActivityLog;
use App\Database;
use App\Database\TableName;
use App\Domain\OrderWorkflowStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderWorkflowHistory;
use App\Permission;
use App\Repositories\DispatchReportRepository;
use App\Services\ReadOnly\OrderItemReadService;
use App\Services\ReadOnly\OrderReadService;
use App\Csrf;
use App\Services\ReadOnly\OrderWorkflowHistoryReadService;
use App\ReadFoundation\WriteGate;
use App\Services\Write\OrderWorkflowWriteService;

class OrderWorkflowController extends Controller
{
    public function index()
    {
        $this->authorize('order_workflow.view');
        ActivityLog::record('order_workflow_access', 'Order Workflow read foundation page viewed');

        $this->render('order-workflow.index', [
            'pageTitle' => 'Order Workflow',
            'breadcrumbs' => [
                ['label' => 'Operations', 'active' => false],
                ['label' => 'Order Workflow', 'active' => true],
            ],
            'accessMode' => Permission::accessMode(),
            'orderReadInventory' => $this->buildOrderReadInventory(),
            'orderItemReadInventory' => $this->buildOrderItemReadInventory(),
            'orderWorkflowHistoryReadInventory' => $this->buildOrderWorkflowHistoryReadInventory(),
            'currentContext' => $this->currentContext(),
            'purpose' => $this->purpose(),
            'mainStages' => $this->mainStages(),
            'exceptionStages' => $this->exceptionStages(),
            'mainFlowPath' => $this->mainFlowPath(),
            'transitionMatrix' => $this->transitionMatrix(),
            'dispatchGate' => $this->dispatchGate(),
            'mappingRule' => $this->mappingRule(),
            'independentRule' => $this->independentRule(),
            'costSnapshotRule' => $this->costSnapshotRule(),
            'actionLogRule' => $this->actionLogRule(),
            'performanceRules' => $this->performanceRules(),
            'futureSyncSafety' => $this->futureSyncSafety(),
            'flashSuccess' => $this->pullFlash('success'),
            'flashError' => $this->pullFlash('error'),
            'csrfField' => Csrf::field(),
            'writeGate' => WriteGate::orderWorkflow(),
            'writeGateReady' => WriteGate::orderWorkflow()['ready'],
            'workflowBoard' => $this->buildWorkflowBoard(),
            'createdReportSection' => $this->buildCreatedReportSection(),
            'dispatchDevNote' => OrderWorkflowStatus::DISPATCH_DEV_NOTE,
            'syncImportRuleNote' => OrderWorkflowStatus::SYNC_IMPORT_RULE_NOTE,
            'vendorReturnFuture' => $this->vendorReturnFuture(),
            'fulfillmentTableColumns' => $this->fulfillmentTableColumns(),
        ]);
    }

    public function action()
    {
        $this->authorize('order_workflow.manage');
        $this->requirePost();
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Invalid security token.');
            redirect('/order-workflow');
        }
        $orderId = (int) ($_POST['order_id'] ?? 0);
        $toStatus = trim((string) ($_POST['to_status'] ?? ''));
        $note = trim((string) ($_POST['action_note'] ?? '')) ?: null;
        $staffConfirmed = !empty($_POST['staff_confirmation']);
        $actionConfirmed = !empty($_POST['action_confirmed']);
        $this->redirectWithWriteResult(
            '/order-workflow',
            (new OrderWorkflowWriteService())->transition($orderId, $toStatus, $note, $staffConfirmed, $actionConfirmed)
        );
    }

    private function buildWorkflowBoard(): array
    {
        $dispatchModuleReady = WriteGate::dispatchReports()['ready'] ?? false;
        $board = [];
        foreach (OrderWorkflowStatus::groupOrder() as $statusCode) {
            if ($statusCode === 'dispatch_report_created') {
                continue;
            }

            $board[$statusCode] = [
                'code' => $statusCode,
                'label' => OrderWorkflowStatus::groupDisplayLabel($statusCode),
                'orders' => [],
            ];
        }

        $orders = $this->loadWorkflowOrders();
        $dispatchReferences = $this->loadDispatchOrderReferences();
        $historyService = new OrderWorkflowHistoryReadService();
        $historyReady = $historyService->tableExists();

        foreach ($orders as $order) {
            $rawStatus = (string) ($order['ibs_status'] ?? 'new_order');
            $normalized = OrderWorkflowStatus::normalize($rawStatus);
            $orderId = (int) ($order['order_id'] ?? 0);
            $dispatchReference = $dispatchReferences[$orderId] ?? null;

            if ($normalized === 'dispatch_report_created') {
                continue;
            }

            if ($dispatchReference !== null && $dispatchReference !== '') {
                continue;
            }

            if (!isset($board[$normalized])) {
                $board[$normalized] = [
                    'code' => $normalized,
                    'label' => OrderWorkflowStatus::groupDisplayLabel($normalized),
                    'orders' => [],
                ];
            }

            $board[$normalized]['orders'][] = $this->buildWorkflowOrderCard(
                $order,
                $normalized,
                $rawStatus,
                $dispatchModuleReady,
                $dispatchReference,
                $historyReady ? $historyService : null
            );
        }

        $ordered = [];
        foreach (OrderWorkflowStatus::groupOrder() as $statusCode) {
            if ($statusCode === 'dispatch_report_created') {
                continue;
            }

            if (!empty($board[$statusCode]['orders'])) {
                $ordered[] = $board[$statusCode];
            }
        }

        foreach ($board as $statusCode => $group) {
            if (in_array($statusCode, OrderWorkflowStatus::groupOrder(), true)) {
                continue;
            }
            if (!empty($group['orders'])) {
                $ordered[] = $group;
            }
        }

        return $ordered;
    }

    private function buildCreatedReportSection(): array
    {
        $section = [
            'code' => 'dispatch_report_created',
            'label' => OrderWorkflowStatus::groupDisplayLabel('dispatch_report_created'),
            'info_note' => OrderWorkflowStatus::COURIER_FLOW_NOTE,
            'orders' => [],
        ];

        $orders = $this->loadWorkflowOrders();
        $dispatchReferences = $this->loadDispatchOrderReferences();
        $historyService = new OrderWorkflowHistoryReadService();
        $historyReady = $historyService->tableExists();

        foreach ($orders as $order) {
            $orderId = (int) ($order['order_id'] ?? 0);
            if ($orderId <= 0) {
                continue;
            }

            $rawStatus = (string) ($order['ibs_status'] ?? 'new_order');
            $normalized = OrderWorkflowStatus::normalize($rawStatus);
            $dispatchReference = $dispatchReferences[$orderId] ?? null;
            $includedInDispatch = $dispatchReference !== null && $dispatchReference !== '';

            if (!$includedInDispatch && $normalized !== 'dispatch_report_created') {
                continue;
            }

            if (!$includedInDispatch) {
                $dispatchReference = $this->resolveDispatchReferenceFromHistory(
                    $historyReady ? $historyService->findByOrderId($orderId, 10) : []
                );
            }

            $section['orders'][] = $this->buildWorkflowOrderCard(
                $order,
                'dispatch_report_created',
                $rawStatus,
                WriteGate::dispatchReports()['ready'] ?? false,
                $dispatchReference,
                $historyReady ? $historyService : null,
                true
            );
        }

        return $section;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function loadWorkflowOrders(): array
    {
        try {
            $orderService = new OrderReadService();
            if (!$orderService->tableExists()) {
                return [];
            }

            $ordersById = [];
            $groupOrder = OrderWorkflowStatus::groupOrder();
            $perStatus = max(3, (int) floor(50 / max(1, count($groupOrder))));

            foreach ($groupOrder as $statusCode) {
                foreach ($orderService->findByStatus($statusCode, $perStatus) as $order) {
                    $orderId = (int) ($order['order_id'] ?? 0);
                    if ($orderId > 0) {
                        $ordersById[$orderId] = $order;
                    }
                }
            }

            $dispatchReferences = $this->loadDispatchOrderReferences();
            foreach (array_keys($dispatchReferences) as $orderId) {
                if (isset($ordersById[$orderId]) || count($ordersById) >= 50) {
                    continue;
                }

                $order = $orderService->findById((int) $orderId);
                if ($order !== null) {
                    $ordersById[(int) $orderId] = $order;
                }
            }

            return array_values($ordersById);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * @return array<int, string>
     */
    private function loadDispatchOrderReferences(): array
    {
        try {
            return (new DispatchReportRepository())->findIncludedOrderReferences(50);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * @param array<int, array<string, mixed>> $histories
     */
    private function resolveDispatchReferenceFromHistory(array $histories): ?string
    {
        foreach ($histories as $historyRow) {
            $toStatus = OrderWorkflowStatus::normalize((string) ($historyRow['to_status'] ?? ''));
            if ($toStatus !== 'dispatch_report_created') {
                continue;
            }

            $note = trim((string) ($historyRow['action_note'] ?? ''));
            if (str_starts_with($note, 'Dispatch Report ')) {
                return trim(substr($note, strlen('Dispatch Report ')));
            }
        }

        return null;
    }

    private function buildWorkflowOrderCard(
        array $order,
        string $normalized,
        string $rawStatus,
        bool $dispatchModuleReady,
        ?string $dispatchReference,
        ?OrderWorkflowHistoryReadService $historyService,
        bool $createdReportSection = false
    ): array {
        $orderId = (int) ($order['order_id'] ?? 0);
        $actions = [];

        if (!$createdReportSection) {
            foreach (OrderWorkflowStatus::allowedActionCodes($normalized) as $toStatus) {
                if ($dispatchModuleReady && $normalized === 'shipped' && $toStatus === 'dispatch_report_created') {
                    continue;
                }

                $actions[] = [
                    'code' => $toStatus,
                    'label' => OrderWorkflowStatus::actionLabel($normalized, $toStatus),
                    'requires_note' => OrderWorkflowStatus::requiresNoteForTransition($normalized, $toStatus),
                    'requires_checkbox' => OrderWorkflowStatus::requiresCheckbox($normalized, $toStatus),
                    'checkbox_label' => OrderWorkflowStatus::checkboxLabel($normalized, $toStatus),
                    'requires_confirm' => OrderWorkflowStatus::requiresConfirmDialog($normalized, $toStatus),
                    'is_dispatch_gate' => $normalized === 'shipped' && $toStatus === 'dispatch_report_created',
                ];
            }
        }

        $histories = $historyService !== null && $orderId > 0
            ? $historyService->findByOrderId($orderId, 10)
            : [];
        foreach ($histories as $index => $historyRow) {
            $histories[$index]['from_label'] = OrderWorkflowStatus::label((string) ($historyRow['from_status'] ?? ''));
            $histories[$index]['to_label'] = OrderWorkflowStatus::label((string) ($historyRow['to_status'] ?? ''));
        }

        if (($dispatchReference === null || $dispatchReference === '') && $histories !== []) {
            $dispatchReference = $this->resolveDispatchReferenceFromHistory($histories);
        }

        return [
            'order_id' => $orderId,
            'order_reference' => (string) ($order['order_reference'] ?? ''),
            'customer_name' => (string) ($order['customer_name'] ?? ''),
            'ibs_status' => $normalized,
            'ibs_status_label' => OrderWorkflowStatus::label($normalized),
            'legacy_status' => $rawStatus !== $normalized ? $rawStatus : null,
            'dispatch_report_reference' => $dispatchReference,
            'actions' => $actions,
            'status_info_note' => $createdReportSection
                ? OrderWorkflowStatus::COURIER_FLOW_NOTE
                : OrderWorkflowStatus::statusInfoNote($normalized),
            'dispatch_redirect_note' => (!$createdReportSection && $dispatchModuleReady && $normalized === 'shipped')
                ? OrderWorkflowStatus::DISPATCH_REPORT_REDIRECT_NOTE
                : null,
            'histories' => $histories,
        ];
    }

    private function buildOrderReadInventory()
    {
        return $this->buildEntityReadInventory(
            Order::class,
            OrderReadService::class,
            'Order',
            'OrderReadService',
            'OrderRepository',
            'order'
        );
    }

    private function buildOrderItemReadInventory()
    {
        return $this->buildEntityReadInventory(
            OrderItem::class,
            OrderItemReadService::class,
            'OrderItem',
            'OrderItemReadService',
            'OrderItemRepository',
            'order item'
        );
    }

    private function buildOrderWorkflowHistoryReadInventory()
    {
        return $this->buildEntityReadInventory(
            OrderWorkflowHistory::class,
            OrderWorkflowHistoryReadService::class,
            'OrderWorkflowHistory',
            'OrderWorkflowHistoryReadService',
            'OrderWorkflowHistoryRepository',
            'order workflow history'
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
                $defaults['status_message'] = 'Table `' . $defaults['prefixed_table'] . '` not available — migration `0005_orders_manual_orders_workflow.sql` not applied yet. Apply manually with the `ibs_` table prefix from config/database.php.';

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

    private function currentContext()
    {
        return [
            'supplier' => 'Iqbal & Brothers',
            'source' => 'Lokkisona.com',
            'summary' => 'Order Workflow planning starts with Iqbal & Brothers supplier operations and the Lokkisona order workflow, but the workflow model stays channel-neutral and ready for other suppliers, sales channels, manual/offline orders, payable/return handling, and future multi-business expansion.',
        ];
    }

    private function purpose()
    {
        return [
            'Define a safe, independent IBS order workflow before any order sync or database writes are enabled.',
            'Document main fulfillment stages and exception stages with allowed transitions only.',
            'Keep the IBS workflow status independent from source/OpenCart status after import or sync.',
            'Prepare dispatch report gating and cost snapshot rules so payable can rely on locked values later.',
            'Stay channel-neutral so manual, offline, ecommerce, and marketplace orders can share the same workflow.',
        ];
    }

    private function mainStages()
    {
        return OrderWorkflowStatus::mainStages();
    }

    private function exceptionStages()
    {
        return OrderWorkflowStatus::exceptionStages();
    }

    private function mainFlowPath()
    {
        return [
            'New Order',
            'Order Received',
            'Packaging',
            'Shipped',
            'Dispatch Report Created',
            'Out For Delivery',
            'Delivered',
        ];
    }

    private function transitionMatrix()
    {
        return [
            ['from' => 'New Order', 'to' => 'Receive Order / Hold / Cancelled', 'note' => 'Entry stage. Supplier must never move back to New Order.'],
            ['from' => 'Order Received', 'to' => 'Start Packaging / Hold / Cancelled', 'note' => 'Checkbox confirmation required. No Ship button on this tab.'],
            ['from' => 'Packaging', 'to' => 'Mark as Shipped / Hold / Cancelled', 'note' => 'Checkbox confirmation required. Eligible for dispatch report after shipped.'],
            ['from' => 'Hold', 'to' => 'Resume Order / Cancelled', 'note' => 'Resume to Order Received or Packaging only — never New Order.'],
            ['from' => 'Shipped', 'to' => 'Create Dispatch Report / Delivery Stop', 'note' => 'v0.4.4.0 status-only dispatch gate with required note/reference.'],
            ['from' => 'Dispatch Report Created', 'to' => '(no supplier manual action)', 'note' => OrderWorkflowStatus::COURIER_FLOW_NOTE],
            ['from' => 'Out For Delivery', 'to' => '(courier/PIT sync only)', 'note' => OrderWorkflowStatus::OUT_FOR_DELIVERY_NOTE],
            ['from' => 'Delivered', 'to' => '(terminal)', 'note' => 'No further supplier action.'],
            ['from' => 'Delivery Stop', 'to' => 'Return Received', 'note' => 'Requires note. Flow: Shipped → Delivery Stop → Hub Return.'],
            ['from' => 'Hub Return', 'to' => '(Vendor Returns later)', 'note' => 'Status foundation only in v0.4.4.0.'],
            ['from' => 'Customer Return / Order Returning', 'to' => '(PIT mapping only)', 'note' => 'Not a normal supplier manual action in v0.4.4.0.'],
        ];
    }

    private function dispatchGate()
    {
        return [
            'title' => 'Dispatch Report Gate (v0.4.5.0)',
            'summary' => 'Owner/admin creates the daily dispatch report on Dispatch Reports. Orders move to Created Report / Dispatch Report Created with immutable cost snapshot.',
            'points' => [
                'Shipped orders stay Shipped until a Daily Dispatch Report batch is created and locked.',
                OrderWorkflowStatus::DISPATCH_REPORT_REDIRECT_NOTE,
                OrderWorkflowStatus::COURIER_FLOW_NOTE,
                'Delivery Stop is allowed only from Shipped (not from Dispatch Report Created or Out For Delivery).',
            ],
        ];
    }

    private function mappingRule()
    {
        return [
            'title' => 'Source / Status Mapping Rule',
            'summary' => 'Source/origin order status mapping is used only when importing or syncing into IBS. It seeds the initial IBS workflow status and is never a live link afterward.',
            'points' => [
                OrderWorkflowStatus::SYNC_IMPORT_RULE_NOTE,
                'Mapping translates a source/OpenCart status into an IBS workflow status only at import/sync time.',
                'Sync must read Settings/Status Mapping first — see the Status Mapping planning foundation.',
                'If the source/OpenCart order status changes later, it must not automatically overwrite IBS workflow status.',
                'The IBS workflow remains the single source of truth for fulfillment once an order is imported.',
                'Mappings will live in status_mappings and order_status_mappings — planning data only in this release.',
            ],
        ];
    }

    private function independentRule()
    {
        return [
            'title' => 'Independent IBS Workflow Rule',
            'summary' => 'The IBS workflow must stay independent after sync/import so supplier fulfillment is not disturbed by external status changes.',
            'points' => [
                'IBS workflow status is owned and advanced only by IBS supplier actions (courier stages excepted).',
                'No background process may rewrite IBS status from a source/channel after import.',
                'Sync Preview/import cannot overwrite existing IBS workflow.',
                'Supplier must never move an order back to New Order — Resume Order restores Order Received or Packaging only.',
                'Workflow transitions are limited to the allowed transition matrix above.',
            ],
        ];
    }

    private function costSnapshotRule()
    {
        return [
            'title' => 'Cost Snapshot Rule',
            'summary' => 'Dispatch reports must capture a cost snapshot for payable. Payable must use the snapshot, not the live product cost that may change later.',
            'points' => [
                'Dispatch report items will store the cost value used at dispatch/report-creation time.',
                'Live product cost stays editable for planning and future orders.',
                'Payable ledgers will calculate from the captured snapshot, never from live changing cost.',
                'Historical cost remains visible without rewriting past dispatch or payable records.',
            ],
        ];
    }

    private function actionLogRule()
    {
        return [
            'v0.4.4.0 enforces required notes for Hold, Cancelled, Delivery Stop, Return Received (Hub Return), and Create Dispatch Report.',
            'Order Received → Packaging and Packaging → Shipped require staff checkbox confirmation.',
            'Every workflow action requires confirmation before submit and appends to order_workflow_histories (from, to, note, changed_by when available, time).',
            'Out For Delivery and Delivered advance by courier/PIT status mapping — no supplier manual buttons in v0.4.4.0.',
            'Supplier must never move an order back to New Order.',
        ];
    }

    private function vendorReturnFuture()
    {
        return [
            'title' => 'Vendor Return Future Rules (planning)',
            'summary' => 'Return type must be clearly labelled when Vendor Returns module is built.',
            'types' => [
                'Hub Return / Courier Return — from Delivery Stop → Hub Return path.',
                'Customer Return — from PIT/OpenCart return status mapping (Returning / Returning Warehouse).',
            ],
            'conditions' => [
                'Reusable = no deduction',
                'Damaged = fixed or percentage deduction',
                'Broken = fixed or percentage deduction',
            ],
            'note' => 'Deduction affects payable only after approval/settlement rule. No payable implementation in v0.4.4.0.',
        ];
    }

    private function fulfillmentTableColumns()
    {
        return [
            'title' => 'Future Vendor Fulfillment Table (reference)',
            'columns' => [
                'Order No',
                'Customer',
                'Product Card',
                'Total Qty',
                'Total Cost',
                'Fulfillment Status',
                'Courier Status',
                'Consignment ID',
                'OC Order Status',
                'Actions',
            ],
            'note' => 'Do not show SL No. Max 50 orders per load. One order-list request at a time.',
        ];
    }

    private function performanceRules()
    {
        return [
            'Maximum 50 orders per load.',
            'No background auto loops.',
            'No repeated fallback AJAX.',
            'One order-list request at a time.',
            'No auto retry storm.',
            'Test Sync will be visible later for controlled checks.',
            'Full Sync stays hidden from the normal UI.',
        ];
    }

    private function futureSyncSafety()
    {
        return [
            'Do not sync orders yet.',
            'Do not connect to OpenCart yet.',
            'Do not create order tables automatically.',
            'Do not write order records to the database yet.',
            'No CREATE TABLE or ALTER TABLE during page load.',
            'No schema repair on page load.',
            'Old IBS-LK OpenCart extension is reference for workflow only — no old code, routes, or dependencies are used.',
        ];
    }
}
