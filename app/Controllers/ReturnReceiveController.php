<?php

namespace App\Controllers;

use App\ActivityLog;
use App\Csrf;
use App\Database;
use App\Database\TableName;
use App\Domain\ReturnReceiveCondition;
use App\Domain\ReturnReceiveNote;
use App\Domain\ReturnReceivePhysicalConfirmation;
use App\Domain\ReturnReceiveReason;
use App\Domain\ReturnReceiveOrderContext;
use App\Domain\ReturnReceiveReference;
use App\Domain\ReturnReceiveType;
use App\Domain\OrderWorkflowStatus;
use App\Repositories\DispatchReportRepository;
use App\Models\ReturnReceive;
use App\Permission;
use App\ReadFoundation\WriteGate;
use App\Repositories\OrderItemRepository;
use App\Repositories\OrderWorkflowHistoryRepository;
use App\Repositories\ReturnReceiveRepository;
use App\Repositories\Write\OrderWriteRepository;
use App\Services\ReadOnly\ReturnReceiveReadService;
use App\Services\Write\ReturnBatchWriteService;
use App\Services\Write\ReturnReceiveWriteService;

class ReturnReceiveController extends Controller
{
    public function index()
    {
        $this->authorize('return_receive.view');
        ActivityLog::record('return_receive_access', 'Return Receive page viewed');

        $this->render('return-receive.index', [
            'pageTitle' => 'Return Receive',
            'breadcrumbs' => [
                ['label' => 'Operations', 'active' => false],
                ['label' => 'Return Receive', 'active' => true],
            ],
            'accessMode' => Permission::accessMode(),
            'pendingHubReturns' => $this->buildPendingReturns(ReturnReceiveType::HUB_COURIER_RETURN),
            'pendingCustomerReturns' => $this->buildPendingReturns(ReturnReceiveType::CUSTOMER_RETURN_TO_SUPPLIER),
            'pendingLokkisonaReturns' => $this->buildPendingReturns(ReturnReceiveType::LOKKISONA_WAREHOUSE_RETURN),
            'latestReceived' => $this->buildLatestReceived(),
            'conditionOptions' => ReturnReceiveCondition::options(),
            'reasonOptions' => ReturnReceiveReason::options(),
            'receivedConfirmationOptions' => ReturnReceivePhysicalConfirmation::options(),
            'devNote' => ReturnReceiveReference::DEV_NOTE,
            'accountingNote' => ReturnReceiveReference::ACCOUNTING_NOTE,
            'stageNote' => ReturnReceiveReference::STAGE_NOTE,
            'customerReturnEmptyNote' => 'Supplier customer returns appear when PIT mapping sets order_returning (Supplier House).',
            'lokkisonaReturnEmptyNote' => 'Lokkisona / Owner Warehouse returns appear when PIT mapping sets order_returning (warehouse path). Owner receive only — no supplier accounting at this stage.',
            'returnBatchFutureNote' => ReturnReceiveOrderContext::RETURN_BATCH_FUTURE_NOTE,
            'lokkisonaStockFutureNote' => ReturnReceiveOrderContext::LOKKISONA_STOCK_FUTURE_NOTE,
            'erpSourceNote' => 'Return Receive uses ibs_orders, ibs_order_items, and workflow history as the main ERP source. Manual orders and future PIT/OpenCart sync both land in the same ERP order tables.',
            'flashSuccess' => $this->pullFlash('success'),
            'flashError' => $this->pullFlash('error'),
            'csrfField' => Csrf::field(),
            'writeGate' => WriteGate::returnReceive(),
            'writeGateReady' => WriteGate::returnReceive()['ready'],
            'returnBatches' => (new ReturnBatchWriteService())->listLatest(20),
            'canApproveBatch' => Permission::can('return_receive.manage'),
            'readInventory' => $this->buildReadInventory(),
            'currentContext' => $this->currentContext(),
            'purpose' => $this->purpose(),
            'supplierReturnFlow' => $this->supplierReturnFlow(),
            'lokkisonaReturnFlow' => $this->lokkisonaReturnFlow(),
            'syncMappingRule' => $this->syncMappingRule(),
            'manualEntryRule' => $this->manualEntryRule(),
            'confirmationRule' => $this->confirmationRule(),
            'totalsWarning' => $this->totalsWarning(),
            'confirmationExamples' => $this->confirmationExamples(),
            'returnBatchPlan' => $this->returnBatchPlan(),
            'supplierReviewRule' => $this->supplierReviewRule(),
            'lokkisonaNoDeductionRule' => $this->lokkisonaNoDeductionRule(),
            'remarkRule' => $this->remarkRule(),
            'imageProofPlan' => $this->imageProofPlan(),
            'payableAdjustmentRule' => $this->payableAdjustmentRule(),
            'approvalRule' => $this->approvalRule(),
            'reportPlan' => $this->reportPlan(),
            'plannedReceiveFields' => $this->plannedReceiveFields(),
            'plannedBatchFields' => $this->plannedBatchFields(),
            'plannedItemFields' => $this->plannedItemFields(),
            'plannedAdjustmentFields' => $this->plannedAdjustmentFields(),
        ]);
    }

    public function confirm()
    {
        $this->authorize('return_receive.manage');
        $this->requirePost();
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Invalid security token.');
            redirect('/return-receive');
        }
        $this->redirectWithWriteResult('/return-receive', (new ReturnReceiveWriteService())->confirmReceive($_POST));
    }

    public function approveBatch()
    {
        $this->authorize('return_receive.manage');
        $this->requirePost();
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Invalid security token.');
            redirect('/return-receive');
        }
        $id = (int) ($_POST['return_batch_id'] ?? 0);
        $this->redirectWithWriteResult('/return-receive', (new ReturnBatchWriteService())->approveBatch($id));
    }

    private function buildPendingReturns(string $returnType): array
    {
        $ibsStatus = ReturnReceiveType::ibsStatusFor($returnType);
        if ($ibsStatus === null) {
            return [];
        }

        try {
            $orders = (new OrderWriteRepository())->findReturnPending($returnType, $ibsStatus, 50);
        } catch (\Throwable $e) {
            return [];
        }

        $orderItems = new OrderItemRepository();
        $dispatchReferences = (new DispatchReportRepository())->findIncludedOrderReferences(50);
        $pending = [];

        foreach ($orders as $order) {
            $orderId = (int) ($order['order_id'] ?? 0);
            if ($orderId <= 0) {
                continue;
            }

            $productLines = $orderItems->findByOrderId($orderId);
            $enriched = ReturnReceiveOrderContext::enrich(
                $order,
                $productLines,
                $dispatchReferences[$orderId] ?? null
            );

            $pending[] = array_merge($enriched, [
                'return_type' => $returnType,
                'return_type_label' => ReturnReceiveType::label($returnType),
            ]);
        }

        return $pending;
    }

    private function buildLatestReceived(): array
    {
        try {
            $rows = (new ReturnReceiveRepository())->findReceivedWithOrders(20);
        } catch (\Throwable $e) {
            return [];
        }

        $historyRepo = new OrderWorkflowHistoryRepository();
        $orderItems = new OrderItemRepository();
        $historyReady = $historyRepo->tableExists();
        $received = [];

        foreach ($rows as $row) {
            $orderId = (int) ($row['order_id'] ?? 0);
            $parsed = ReturnReceiveNote::parse('');

            if ($historyReady && $orderId > 0) {
                foreach ($historyRepo->findByOrderId($orderId, 10) as $historyRow) {
                    $note = trim((string) ($historyRow['action_note'] ?? ''));
                    if (!str_starts_with($note, 'Return Received |')) {
                        continue;
                    }
                    $parsed = ReturnReceiveNote::parse($note);
                    break;
                }
            }

            $conditionCode = $parsed['condition_code'] ?? null;
            $supplierNote = trim((string) ($parsed['supplier_note'] ?? ''));
            $ownerNote = trim((string) ($parsed['owner_note'] ?? ''));
            $verificationNote = trim((string) ($parsed['verification_note'] ?? ''));
            $supplierNoteDisplay = $supplierNote !== '' && $supplierNote !== '-' ? $supplierNote : '—';
            $ownerNoteDisplay = $ownerNote !== '' && $ownerNote !== '-' ? $ownerNote : '—';
            if ($verificationNote !== '' && $verificationNote !== '-' && $ownerNoteDisplay === '—') {
                $ownerNoteDisplay = $verificationNote;
            }

            $productLines = $orderId > 0 ? $orderItems->findByOrderId($orderId) : [];
            $productSummary = ReturnReceiveOrderContext::productSummary(
                array_map(static fn (array $line): array => [
                    'product_id' => (string) ($line['product_id'] ?? ''),
                    'product_name' => (string) ($line['product_name'] ?? ''),
                    'variant_label' => (string) ($line['variant_label'] ?? ''),
                    'quantity' => (int) ($line['quantity'] ?? 0),
                ], $productLines)
            );

            $received[] = array_merge($row, [
                'return_type_label' => ReturnReceiveType::label((string) ($row['return_type'] ?? '')),
                'destination_label' => $parsed['destination'] ?? ReturnReceiveType::destinationLabel((string) ($row['return_type'] ?? '')),
                'reason_label' => $parsed['reason'] ?? '—',
                'received_label' => $parsed['received'] ?? '—',
                'condition_label' => $parsed['condition'] ?? '—',
                'condition_code' => $conditionCode,
                'condition_badge' => $conditionCode !== null
                    ? ReturnReceiveCondition::badgeClass($conditionCode)
                    : 'badge-ok',
                'order_reference' => $parsed['order_reference'] ?? ($row['order_reference'] ?? '—'),
                'product_summary' => $productSummary,
                'fulfillment_status' => OrderWorkflowStatus::label((string) ($row['ibs_status'] ?? '')),
                'supplier_note_display' => $supplierNoteDisplay,
                'owner_note_display' => $ownerNoteDisplay,
            ]);
        }

        return $received;
    }

    private function buildReadInventory()
    {
        $databaseStatus = Database::check();
        $defaults = [
            'database_connected' => (bool) ($databaseStatus['connected'] ?? false),
            'service_ready' => false,
            'logical_table' => ReturnReceive::table(),
            'prefixed_table' => TableName::forModel(ReturnReceive::class),
            'model_class' => 'ReturnReceive',
            'primary_key' => ReturnReceive::primaryKey(),
            'columns' => ReturnReceive::columns(),
            'read_service' => 'ReturnReceiveReadService',
            'read_repository' => 'ReturnReceiveRepository',
            'table_exists' => false,
            'row_count' => 0,
            'rows' => [],
            'status' => 'error',
            'status_message' => 'Read inventory could not be loaded safely.',
        ];

        try {
            $service = new ReturnReceiveReadService();
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
                $defaults['status_message'] = 'Table ready. No return receive records yet (read-only; no writes in this release).';

                return $defaults;
            }

            $defaults['status'] = 'ok';
            $defaults['status_message'] = 'Showing up to 50 return receive records (SELECT only).';

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
            'summary' => 'Return Receive & Review Batch planning is owner/admin controlled and starts with Iqbal & Brothers supplier operations and the Lokkisona order workflow, but the return model stays channel-neutral and ready for other suppliers, sales channels, manual/offline orders, payable workflows, and future multi-business expansion.',
        ];
    }

    private function purpose()
    {
        return [
            'Define a safe internal Return Receive and Review Batch model before any return writes or live links are enabled.',
            'Separate Supplier Return from Lokkisona Return at receive time.',
            'Prevent wrong total order/product/quantity/cost submission with a pre-submit confirmation.',
            'Keep all return control owner/admin only — not supplier-facing in this foundation.',
            'Stay channel-neutral so manual, offline, ecommerce, and marketplace returns can share the same model.',
        ];
    }

    private function supplierReturnFlow()
    {
        return [
            'title' => 'Supplier Return Flow',
            'summary' => 'Supplier Return covers returned items related to supplier-side settlement/review.',
            'points' => [
                'Delivery Stop / Hub Return / Order Returning can enter Return Receive as a Supplier Return.',
                'May affect supplier payable only after owner/admin review.',
                'If the supplier charges any amount for return/damage/reason, owner/admin add it manually as Additional Payable / Payable Adjustment.',
                'Any such amount may increase Supplier Payable only after review/approval.',
            ],
        ];
    }

    private function lokkisonaReturnFlow()
    {
        return [
            'title' => 'Lokkisona Return Flow',
            'summary' => 'Lokkisona Return covers product returned/received/kept by the Lokkisona side.',
            'points' => [
                'Track / list / control only.',
                'No supplier payable deduction.',
                'Reason: product received by Lokkisona side.',
                'Manual Lokkisona Return entry is list/control only and does not deduct supplier payable.',
            ],
        ];
    }

    private function syncMappingRule()
    {
        return [
            'title' => 'Sync Status Mapping Rule',
            'summary' => 'Normal Supplier Return and Lokkisona Return can later be mapped/imported by sync order status.',
            'points' => [
                'Supplier Return and Lokkisona Return can later be created by source status mapping or manual entry.',
                'Supplier Return and Lokkisona Return candidates must be separated during Sync Preview.',
                'Sync mapping should detect eligible source/order statuses.',
                'Detected returns are placed into the correct return receive/review list.',
                'Sync mapping is used only at import — it does not overwrite IBS workflow afterward.',
                'No order sync is connected in this release — see Status Mapping planning foundation.',
            ],
        ];
    }

    private function manualEntryRule()
    {
        return [
            'title' => 'Manual Return Entry Rule',
            'summary' => 'Manual entry must be available for offline/manual orders.',
            'points' => [
                'Manual Supplier Return and Manual Lokkisona Return must be supported later.',
                'Manual Supplier Return entry can affect supplier payable only after owner/admin approval.',
                'Manual Lokkisona Return entry is list/control only and does not deduct supplier payable.',
            ],
        ];
    }

    private function confirmationRule()
    {
        return [
            'title' => 'Pre-Submit Confirmation Rule',
            'summary' => 'Before submitting Return Receive / Return Batch, the system must show a confirmation warning.',
            'points' => [
                'Return Receive must require confirmation before submit.',
                'Return batches prevent wrong total order/product/quantity/cost submission.',
                'Total cost must be based on product cost snapshot, not selling price.',
                'No automatic charge/deduction calculation now.',
            ],
        ];
    }

    private function totalsWarning()
    {
        return [
            'Total selected orders',
            'Total products / items',
            'Total quantity',
            'Total cost / cost snapshot amount',
            'Supplier',
            'Business source',
            'Return type',
            'Payable impact',
        ];
    }

    private function confirmationExamples()
    {
        return [
            [
                'type' => 'Supplier Return',
                'impact' => 'Review required. May affect Supplier Payable after owner/admin approval.',
            ],
            [
                'type' => 'Lokkisona Return',
                'impact' => 'No deduction. Reason: product received by Lokkisona side.',
            ],
        ];
    }

    private function returnBatchPlan()
    {
        return [
            'title' => 'Return Batch Planning',
            'summary' => 'Return batches group reviewed return receives for controlled processing.',
            'points' => [
                'A return batch carries supplier, business source, return type, and snapshot totals.',
                'Batches keep created_by, reviewed_by, and approved_by for audit.',
                'Batch reference and status track the review lifecycle.',
                'No return tables are created and no records are written in this release.',
            ],
        ];
    }

    private function supplierReviewRule()
    {
        return [
            'title' => 'Supplier Return Payable Review Rule',
            'summary' => 'Supplier Return can create payable impact only after owner/admin approval.',
            'points' => [
                'Supplier Return may affect Net Payable to Supplier only after owner/admin approval.',
                'No automatic charge/deduction calculation is performed now.',
                'Supplier return/damage-related charges are added manually as Additional Payable / Payable Adjustment after review.',
            ],
        ];
    }

    private function lokkisonaNoDeductionRule()
    {
        return [
            'title' => 'Lokkisona Return No-Deduction Rule',
            'summary' => 'Lokkisona Return records do not deduct supplier payable.',
            'points' => [
                'Lokkisona Return is list/control only.',
                'No supplier payable deduction is created from Lokkisona Return.',
                'Reason: product received by Lokkisona side.',
            ],
        ];
    }

    private function remarkRule()
    {
        return [
            'Each return receive/item should support an optional remark/note.',
            'Each return receive/item should support an optional cost snapshot display.',
            'Each return receive/item should carry a payable impact status.',
        ];
    }

    private function imageProofPlan()
    {
        return [
            'Optional image/proof upload is planned for return receives and items.',
            'Store image references following POIP / PIT reference logic, not duplicated files, unless a manual upload workflow is explicitly added.',
            'No real image upload is implemented in this release.',
        ];
    }

    private function payableAdjustmentRule()
    {
        return [
            'title' => 'Payable Adjustment Review Rule',
            'summary' => 'Payable adjustments from returns are reviewed before they affect settlement.',
            'points' => [
                'If the supplier charges the business for any return/damage/reason, it is entered manually later as Additional Payable / Payable Adjustment after owner/admin review.',
                'Payable adjustments carry adjustment type, reason, amount, and direction.',
                'No payable adjustment records are created in this release.',
            ],
        ];
    }

    private function approvalRule()
    {
        return [
            'Every return receive / batch / payable-adjustment action later requires note, confirmation, user, timestamp, and activity log.',
            'Return control is owner/admin only and is not supplier-facing in this foundation.',
            'Supplier role should not see all return receive / batch / payable-impact controls.',
            'No automatic payable mutation without a clear, reviewed event source.',
        ];
    }

    private function reportPlan()
    {
        return [
            'Return receive and review batch statements should later support print, export, and download.',
            'No real print, export, or download is implemented in this release.',
            'Reports will use approved entries and snapshot values, not live changing cost.',
            'Reports stay channel-neutral and supplier-scoped.',
        ];
    }

    private function plannedReceiveFields()
    {
        return [
            'return_receive_id',
            'return_type',
            'supplier_id',
            'business_source_id',
            'source_order_id',
            'source_order_reference',
            'ibs_order_id',
            'receive_status',
            'total_orders',
            'total_items',
            'total_quantity',
            'total_cost_snapshot',
            'payable_impact_type',
            'received_by',
            'received_at',
            'remark_note',
            'proof_image_reference',
            'created_at',
            'updated_at',
        ];
    }

    private function plannedBatchFields()
    {
        return [
            'return_batch_id',
            'batch_reference',
            'supplier_id',
            'business_source_id',
            'return_batch_type',
            'total_orders',
            'total_items',
            'total_quantity',
            'total_cost_snapshot',
            'payable_impact_type',
            'created_by',
            'reviewed_by',
            'approved_by',
            'created_at',
            'approved_at',
            'status',
        ];
    }

    private function plannedItemFields()
    {
        return [
            'return_item_id',
            'return_batch_id',
            'return_receive_id',
            'product_id',
            'variant_id',
            'product_name_snapshot',
            'supplier_model_snapshot',
            'source_model_snapshot',
            'quantity',
            'cost_snapshot',
            'item_remark_note',
            'proof_image_reference',
            'payable_impact',
            'created_at',
        ];
    }

    private function plannedAdjustmentFields()
    {
        return [
            'payable_adjustment_id',
            'supplier_id',
            'return_batch_id',
            'adjustment_type',
            'adjustment_reason',
            'amount',
            'direction',
            'status',
            'reviewed_by',
            'approved_by',
            'approved_at',
            'created_at',
        ];
    }
}
