<?php

namespace App\Controllers;

use App\ActivityLog;
use App\Permission;

class ReturnReceiveController extends Controller
{
    public function index()
    {
        $this->authorize('return_receive.view');
        ActivityLog::record('return_receive_access', 'Return Receive and Review Batch planning foundation page viewed');

        $this->render('return-receive.index', [
            'pageTitle' => 'Return Receive',
            'breadcrumbs' => [
                ['label' => 'Operations', 'active' => false],
                ['label' => 'Return Receive', 'active' => true],
            ],
            'accessMode' => Permission::accessMode(),
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
