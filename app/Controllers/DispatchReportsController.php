<?php

namespace App\Controllers;

use App\ActivityLog;
use App\Permission;

class DispatchReportsController extends Controller
{
    public function index()
    {
        $this->authorize('dispatch_reports.view');
        ActivityLog::record('dispatch_reports_access', 'Dispatch Report planning foundation page viewed');

        $this->render('dispatch-reports.index', [
            'pageTitle' => 'Dispatch Reports',
            'breadcrumbs' => [
                ['label' => 'Operations', 'active' => false],
                ['label' => 'Dispatch Reports', 'active' => true],
            ],
            'accessMode' => Permission::accessMode(),
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
        ]);
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
                'Batch/report reference format should follow DDMMYYYY(COUNT_ORDER).',
                'Example: a 12-order batch on 04 June 2026 would read 04062026(12).',
                'The reference is generated at creation time and stored on the report.',
                'Reference is channel-neutral and not hard-coded to any single source.',
            ],
        ];
    }

    private function costSnapshotRule()
    {
        return [
            'title' => 'Cost Snapshot / Payable Rule',
            'summary' => 'A dispatch report captures cost snapshots so payable uses locked values, not live changing cost.',
            'points' => [
                'Dispatch report items store a cost snapshot at creation time.',
                'Dispatch report later creates Product Cost Payable using the cost snapshot, feeding Supplier Payables.',
                'Live product cost stays editable for planning and future orders.',
                'Payable must never recalculate from live cost after a snapshot is taken.',
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
