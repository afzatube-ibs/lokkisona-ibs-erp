<?php

namespace App\Controllers;

use App\ActivityLog;
use App\Permission;

class StatusMappingController extends Controller
{
    public function index()
    {
        $this->authorize('status_mapping.view');
        ActivityLog::record('status_mapping_access', 'Status Mapping and Sync Planning foundation page viewed');

        $this->render('status-mapping.index', [
            'pageTitle' => 'Status Mapping',
            'breadcrumbs' => [
                ['label' => 'Operations', 'active' => false],
                ['label' => 'Status Mapping', 'active' => true],
            ],
            'accessMode' => Permission::accessMode(),
            'currentContext' => $this->currentContext(),
            'purpose' => $this->purpose(),
            'mappingTypes' => $this->mappingTypes(),
            'ibsWorkflowExamples' => $this->ibsWorkflowExamples(),
            'returnMappingExamples' => $this->returnMappingExamples(),
            'workflowMappingRule' => $this->workflowMappingRule(),
            'supplierReturnRule' => $this->supplierReturnRule(),
            'lokkisonaReturnRule' => $this->lokkisonaReturnRule(),
            'courierMappingRule' => $this->courierMappingRule(),
            'independentWorkflowRule' => $this->independentWorkflowRule(),
            'skipMissingRule' => $this->skipMissingRule(),
            'unmappedSafetyRule' => $this->unmappedSafetyRule(),
            'testSyncPreviewRule' => $this->testSyncPreviewRule(),
            'performanceSyncRules' => $this->performanceSyncRules(),
            'manualOfflineRule' => $this->manualOfflineRule(),
            'futureMappingSettings' => $this->futureMappingSettings(),
            'plannedOrderSyncColumns' => $this->plannedOrderSyncColumns(),
            'plannedMappingFields' => $this->plannedMappingFields(),
            'plannedSyncPreviewFields' => $this->plannedSyncPreviewFields(),
            'plannedSyncLogFields' => $this->plannedSyncLogFields(),
        ]);
    }

    private function currentContext()
    {
        return [
            'supplier' => 'Iqbal & Brothers',
            'source' => 'Lokkisona.com',
            'summary' => 'Status Mapping and Sync Planning starts with Iqbal & Brothers supplier operations and the Lokkisona order workflow, but the mapping model stays channel-neutral and ready for other suppliers, sales channels, manual/offline orders, payable/return workflows, and future multi-business expansion.',
        ];
    }

    private function purpose()
    {
        return [
            'Plan how source/origin order statuses map into IBS workflow, return receive, supplier return, Lokkisona return, courier status, and dispatch/payable flow before any sync is enabled.',
            'Require Settings/Status Mapping to be read before any order import or sync runs.',
            'Use source/origin order status only at first sync/import time — IBS workflow stays independent afterward.',
            'Block blind imports: unmapped source statuses go to mapping review or blocked preview, not into IBS automatically.',
            'Stay channel-neutral so manual, offline, ecommerce, and marketplace sources can share the same mapping model.',
        ];
    }

    private function mappingTypes()
    {
        return [
            'Source Order Status → IBS Workflow Status',
            'Source Order Status → Return Receive Type',
            'Source Order Status → Supplier Return',
            'Source Order Status → Lokkisona Return',
            'Courier Status → IBS Delivery Status',
            'Source Product / Option mapping (later)',
            'Supplier mapping (later)',
            'Business Source mapping (later)',
        ];
    }

    private function ibsWorkflowExamples()
    {
        return [
            'New Order',
            'Order Received',
            'Packaging',
            'Shipped',
            'Dispatch Report Created',
            'Out For Delivery',
            'Delivered',
            'Hold',
            'Cancelled',
            'Delivery Stop',
            'Hub Return',
            'Order Returning',
        ];
    }

    private function returnMappingExamples()
    {
        return [
            [
                'type' => 'Supplier Return',
                'rule' => 'May affect supplier payable only after owner/admin review.',
            ],
            [
                'type' => 'Lokkisona Return',
                'rule' => 'List/control only. No supplier payable deduction.',
            ],
        ];
    }

    private function workflowMappingRule()
    {
        return [
            'title' => 'Source Status → IBS Workflow Rule',
            'summary' => 'Source/origin order status maps to an initial IBS workflow status only at import/sync time.',
            'points' => [
                'Sync must read Settings/Status Mapping first.',
                'No orders should be imported without valid mapping.',
                'Use current/source order status only — not old order history.',
                'After import, IBS workflow is advanced only by IBS actions.',
                'If source/OpenCart order status changes later, it must not overwrite IBS workflow automatically.',
            ],
        ];
    }

    private function supplierReturnRule()
    {
        return [
            'title' => 'Source Status → Supplier Return Rule',
            'summary' => 'Eligible source statuses can later seed Supplier Return receive/review entries at import time.',
            'points' => [
                'Mapping type: Source Order Status → Supplier Return.',
                'Detected returns are placed into the Supplier Return receive/review list.',
                'May affect supplier payable only after owner/admin review.',
                'Manual Supplier Return entry remains possible for offline/manual orders.',
            ],
        ];
    }

    private function lokkisonaReturnRule()
    {
        return [
            'title' => 'Source Status → Lokkisona Return Rule',
            'summary' => 'Eligible source statuses can later seed Lokkisona Return list/control entries at import time.',
            'points' => [
                'Mapping type: Source Order Status → Lokkisona Return.',
                'Lokkisona Return is list/control only.',
                'No supplier payable deduction from Lokkisona Return mapping.',
                'Manual Lokkisona Return entry remains possible for offline/manual orders.',
            ],
        ];
    }

    private function courierMappingRule()
    {
        return [
            'title' => 'Courier Status Mapping Rule',
            'summary' => 'Courier status will map to IBS delivery status separately from source order status.',
            'points' => [
                'Mapping type: Courier Status → IBS Delivery Status.',
                'Courier updates do not rewrite IBS workflow status automatically.',
                'Consignment ID and courier status stay visible on future order/sync lists.',
                'Delivery Stop, Hub Return, and Order Returning remain IBS workflow exceptions.',
            ],
        ];
    }

    private function independentWorkflowRule()
    {
        return [
            'title' => 'Independent IBS Workflow Rule',
            'summary' => 'Once synced into IBS, workflow status is owned by IBS — not by the source channel.',
            'points' => [
                'Source status mapping is used only at import/sync time.',
                'IBS workflow remains independent after sync.',
                'No background process may rewrite IBS status from source changes.',
                'Dispatch, return receive, and payable flows use IBS status and snapshots.',
            ],
        ];
    }

    private function skipMissingRule()
    {
        return [
            'title' => 'Skip Missing / Status 0 Rule',
            'summary' => 'Missing or OpenCart status 0 orders must be skipped during sync preview and import.',
            'points' => [
                'Skip Missing/OpenCart status 0 — do not import blindly.',
                'Skipped orders are counted in sync preview totals.',
                'Use current/source order status only, not historical status rows.',
            ],
        ];
    }

    private function unmappedSafetyRule()
    {
        return [
            'title' => 'Unmapped Status Safety Rule',
            'summary' => 'Unmapped source statuses must not enter IBS as imported orders.',
            'points' => [
                'Unmapped source status goes to mapping review / blocked preview.',
                'No orders should be imported without valid mapping.',
                'Sync preview must show total unmapped orders before any import action.',
                'Owner/admin resolves mapping before retrying import.',
            ],
        ];
    }

    private function testSyncPreviewRule()
    {
        return [
            'title' => 'Test Sync Preview Rule',
            'summary' => 'Test Sync must show counts before import. Full Sync stays hidden from normal UI.',
            'points' => [
                'Test Sync must be visible later for controlled checks.',
                'Full Sync must be hidden from normal UI.',
                'Sync preview shows: total matched orders, total skipped orders, total unmapped orders, total supplier-related orders, total return candidates.',
                'Preview is checked and logged before any import is allowed.',
            ],
        ];
    }

    private function performanceSyncRules()
    {
        return [
            'Maximum 50 orders per load/sync request.',
            'No background auto loops.',
            'No repeated fallback AJAX.',
            'One order-list/sync request at a time.',
            'No auto retry storm.',
            'Test Sync visible later; Full Sync hidden from normal UI.',
        ];
    }

    private function manualOfflineRule()
    {
        return [
            'title' => 'Manual / Offline Order Support Rule',
            'summary' => 'Manual/offline order entry must remain possible without source status mapping.',
            'points' => [
                'Manual orders do not require a source status mapping row.',
                'Staff can enter orders directly into IBS workflow later when permitted.',
                'Supplier Return and Lokkisona Return can also be created by manual entry.',
                'Architecture stays ready for phone, showroom, and offline retail orders.',
            ],
        ];
    }

    private function futureMappingSettings()
    {
        return [
            'Settings will store active business source, default supplier, and mapping review preferences.',
            'status_mappings will hold per-source status rows with mapping type and target values.',
            'order_status_mappings and courier_status_mappings will support workflow and delivery translation.',
            'sync_previews and sync_logs will audit every Test Sync and import attempt.',
            'No mapping tables are created and no mapping/sync records are written in this release.',
        ];
    }

    private function plannedOrderSyncColumns()
    {
        return [
            'IBS Order ID',
            'Source Order ID',
            'Source/Origin Order Status',
            'IBS Workflow Status',
            'Courier Status',
            'Consignment ID',
            'Supplier',
            'Business Source',
            'Sync status',
            'Mapping status',
            'Last synced at',
        ];
    }

    private function plannedMappingFields()
    {
        return [
            'status_mapping_id',
            'business_source_id',
            'source_status_id',
            'source_status_name',
            'mapping_type',
            'target_ibs_status',
            'target_return_type',
            'supplier_id',
            'is_active',
            'sort_order',
            'created_by',
            'updated_by',
            'created_at',
            'updated_at',
        ];
    }

    private function plannedSyncPreviewFields()
    {
        return [
            'sync_preview_id',
            'business_source_id',
            'source_status_id',
            'mapped_target',
            'total_matched_orders',
            'total_skipped_orders',
            'total_unmapped_orders',
            'total_return_candidates',
            'total_supplier_related_orders',
            'checked_by',
            'checked_at',
        ];
    }

    private function plannedSyncLogFields()
    {
        return [
            'sync_log_id',
            'business_source_id',
            'sync_type',
            'request_limit',
            'total_checked',
            'total_imported',
            'total_skipped',
            'total_unmapped',
            'status',
            'message',
            'created_by',
            'created_at',
        ];
    }
}
