<?php
$statusFilter = $statusFilter ?? null;
$recentWorkflowHistory = $recentWorkflowHistory ?? [];
?>
<div class="vf-ops-page">
<div class="page-header page-header-compact vf-page-header">
    <h1 class="page-title">Vendor Fulfillment</h1>
    <?php if (!empty($canCreateOrders)): ?>
    <button type="button" class="btn btn-primary btn-sm vf-header-create-btn" data-open-modal="workflowCreateOrderModal">+ Create New Order</button>
    <?php endif; ?>
</div>

<?php view('partials.flash-messages', [
    'flashSuccess' => $flashSuccess ?? null,
    'flashSuccessLink' => $flashSuccessLink ?? null,
    'flashError' => $flashError ?? null,
]); ?>

<?php if (!empty($writeGateReady)): ?>

<?php view('partials.workflow-stage-nav', [
    'workflowStageNav' => $workflowStageNav ?? [],
    'statusFilter' => $statusFilter,
]); ?>

<?php view('partials.vendor-fulfillment-toolbar', [
    'fulfillmentFilters' => $fulfillmentFilters ?? [],
    'statusFilter' => $statusFilter,
    'canManageWorkflow' => $canManageWorkflow ?? false,
    'bulkActionForFilter' => $bulkActionForFilter ?? null,
    'bulkActionLabelForFilter' => $bulkActionLabelForFilter ?? null,
    'statusFilterOptions' => $statusFilterOptions ?? [],
]); ?>

<?php view('partials.vendor-fulfillment-table', [
    'fulfillmentRows' => $fulfillmentRows ?? [],
    'fulfillmentPagination' => $fulfillmentPagination ?? [],
    'fulfillmentFilters' => $fulfillmentFilters ?? [],
    'tableReady' => $tableReady ?? false,
    'canManageWorkflow' => $canManageWorkflow ?? false,
    'dispatchGateReady' => $dispatchGateReady ?? false,
    'statusFilter' => $statusFilter,
    'csrfField' => $csrfField ?? '',
    'deliveryStopReasonOptions' => $deliveryStopReasonOptions ?? [],
    'isSupplierView' => !empty($isSupplierView),
]); ?>

<?php view('partials.vendor-fulfillment-modals', [
    'csrfField' => $csrfField ?? '',
    'statusFilter' => $statusFilter,
    'deliveryStopReasonOptions' => $deliveryStopReasonOptions ?? [],
    'dispatchFlash' => $dispatchFlash ?? null,
    'flashSuccess' => $flashSuccess ?? null,
]); ?>

<details class="planning-collapsible vf-history-collapsible">
    <summary class="planning-collapsible-summary">Recent Workflow History</summary>
    <div class="planning-collapsible-body">
        <?php if (!empty($recentWorkflowHistory)): ?>
        <div class="table-scroll vf-history-table-wrap">
            <table class="data-table vf-history-table">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Action</th>
                        <th>From</th>
                        <th>To</th>
                        <th>User</th>
                        <th>Date Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentWorkflowHistory as $row): ?>
                    <tr>
                        <td><?= e($row['order_reference']) ?></td>
                        <td><?= e($row['action_label'] ?? '—') ?></td>
                        <td><?= e($row['from_label']) ?></td>
                        <td><?= e($row['to_label']) ?></td>
                        <td><?= e($row['changed_by'] !== '' ? $row['changed_by'] : '—') ?></td>
                        <td><?= e($row['changed_at']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <p class="page-description vf-history-empty">No workflow actions recorded yet.</p>
        <?php endif; ?>
    </div>
</details>

<?php if (!empty($canCreateOrders)): ?>
<?php view('partials.order-workflow-create-order-modal', [
    'manualOrderGateReady' => $manualOrderGateReady ?? false,
    'writeGateMessage' => $manualOrderGateMessage ?? '',
    'csrfField' => $csrfField ?? '',
    'businessSourceOptions' => $businessSourceOptions ?? [],
    'supplierOptions' => $supplierOptions ?? [],
    'productOptions' => $productOptions ?? [],
    'variantOptionsByProduct' => $variantOptionsByProduct ?? [],
    'productCostById' => $productCostById ?? [],
]); ?>
<?php endif; ?>

<?php else: ?>
<?php view('partials.write-gate-warning', ['writeGateReady' => $writeGateReady ?? false, 'writeGate' => $writeGate ?? []]); ?>
<?php endif; ?>
</div>
