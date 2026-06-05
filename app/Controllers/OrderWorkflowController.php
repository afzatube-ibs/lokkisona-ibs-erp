<?php

namespace App\Controllers;

use App\ActivityLog;
use App\Database;
use App\Database\TableName;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderWorkflowHistory;
use App\Permission;
use App\Services\ReadOnly\OrderItemReadService;
use App\Services\ReadOnly\OrderReadService;
use App\Csrf;
use App\Services\ReadOnly\OrderWorkflowHistoryReadService;
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
        $this->redirectWithWriteResult('/order-workflow', (new OrderWorkflowWriteService())->transition($orderId, $toStatus, $note));
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
        return [
            ['code' => 'new', 'label' => 'New Order', 'description' => 'Order entered or imported into IBS. Entry point only — workflow can never move back to this stage.'],
            ['code' => 'ready', 'label' => 'Order Received', 'description' => 'Order accepted into IBS fulfillment. Invoice/print is allowed only at this stage before Packaging.'],
            ['code' => 'processing', 'label' => 'Packaging', 'description' => 'Items being packed. Requires invoice/packing confirmation to enter this stage.'],
            ['code' => 'shipped', 'label' => 'Shipped', 'description' => 'Handed over from supplier. Requires shipped/handover confirmation. Cannot move forward without a Dispatch Report.'],
            ['code' => 'dispatch_ready', 'label' => 'Dispatch Report Created', 'description' => 'Batch/report locked. Cost snapshot captured for payable. Normal fulfillment status can no longer change.'],
            ['code' => 'out_for_delivery', 'label' => 'Out For Delivery', 'description' => 'With the courier/last-mile. Only Delivery Stop is allowed as a post-shipping exception.'],
            ['code' => 'delivered', 'label' => 'Delivered', 'description' => 'Final successful fulfillment state for the main workflow.'],
        ];
    }

    private function exceptionStages()
    {
        return [
            ['code' => 'hold', 'label' => 'Hold', 'description' => 'Temporary pause before shipping. Can return to Order Received or Packaging, or be Cancelled.'],
            ['code' => 'cancelled', 'label' => 'Cancelled', 'description' => 'Order stopped before fulfillment completes. Terminal exception state.'],
            ['code' => 'delivery_stop', 'label' => 'Delivery Stop', 'description' => 'Post-shipping stop. Will later require reason/note/user/time. Leads to Hub Return and later enters Return Receive / Review Batch.'],
            ['code' => 'courier_return', 'label' => 'Hub Return', 'description' => 'Returned to hub. Will later require received confirmation, quantity check, and condition note. Later enters Return Receive / Review Batch — no normal fulfillment action.'],
            ['code' => 'order_returning', 'label' => 'Order Returning', 'description' => 'Return handling in progress. Later enters Return Receive / Review Batch. No normal fulfillment action.'],
        ];
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
            ['from' => 'New Order', 'to' => 'Order Received / Hold / Cancelled', 'note' => 'Entry stage. Never returns to New Order.'],
            ['from' => 'Order Received', 'to' => 'Packaging / Hold / Cancelled', 'note' => 'Invoice/print allowed here. Packaging requires invoice/packing confirmation.'],
            ['from' => 'Packaging', 'to' => 'Shipped / Hold / Cancelled', 'note' => 'Shipped requires shipped/handover confirmation.'],
            ['from' => 'Hold', 'to' => 'Order Received / Packaging / Cancelled', 'note' => 'Resume to a pre-shipping stage or cancel only.'],
            ['from' => 'Shipped', 'to' => 'Dispatch Report Created / Delivery Stop', 'note' => 'Locked gate: cannot move forward without creating and locking a Dispatch Report.'],
            ['from' => 'Dispatch Report Created', 'to' => 'Delivery Stop (exception only)', 'note' => 'Batch/report locked. Normal fulfillment status cannot change.'],
            ['from' => 'Out For Delivery', 'to' => 'Delivery Stop (exception only)', 'note' => 'Delivered or Delivery Stop are the only outcomes.'],
            ['from' => 'Delivery Stop', 'to' => 'Hub Return', 'note' => 'Post-shipping exception path only.'],
            ['from' => 'Hub Return', 'to' => 'Vendor Returns / return handling', 'note' => 'Hub Return and Order Returning later enter Return Receive / Review Batch. No normal fulfillment action from this point.'],
        ];
    }

    private function dispatchGate()
    {
        return [
            'title' => 'Dispatch Report Gate',
            'summary' => 'After Shipped, orders must not move forward without creating a Dispatch Report. The report is the gate between supplier handover and last-mile delivery.',
            'points' => [
                'Shipped → Dispatch Report Created is a locked gate before any forward movement.',
                'Dispatch Report Created means the batch/report is locked.',
                'Report-created / batch-locked orders cannot change normal fulfillment status.',
                'The dispatch report must capture a cost snapshot for payable at creation time.',
                'Delivery Stop and Hub Return are the only allowed post-shipping exception actions.',
            ],
        ];
    }

    private function mappingRule()
    {
        return [
            'title' => 'Source / Status Mapping Rule',
            'summary' => 'Source/origin order status mapping is used only when importing or syncing into IBS. It seeds the initial IBS workflow status and is never a live link afterward.',
            'points' => [
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
                'IBS workflow status is owned and advanced only by IBS actions.',
                'No background process may rewrite IBS status from a source/channel after import.',
                'Sync Preview/import cannot overwrite existing IBS workflow.',
                'Do not allow moving back to New Order under any condition.',
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
            'Every workflow action later must require a note/confirmation before it is accepted.',
            'Delivery Stop will require reason, note, user, and time.',
            'Hub Return will require received confirmation, quantity check, and condition note.',
            'Order Received → Packaging will require invoice/packing confirmation.',
            'Packaging → Shipped will require shipped/handover confirmation.',
            'Every workflow action will append to order_workflow_histories for audit (who, from, to, note, time).',
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
