<?php
$displayActionNote = static function (?string $note): string {
    $note = trim((string) $note);

    return $note !== '' ? $note : '-';
};
$statusFilter = $statusFilter ?? null;
$recentWorkflowHistory = $recentWorkflowHistory ?? [];
$showDeveloperContent = !empty($vfDebugMode);
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

<?php if (!empty($vfDebugPanel)): ?>
<details class="planning-collapsible vf-debug-panel" open>
    <summary class="planning-collapsible-summary">VF status filter debug (local ?debug=1)</summary>
    <div class="planning-collapsible-body">
        <dl class="vf-debug-dl">
            <dt>Active filter bucket</dt>
            <dd><?= e((string) ($vfDebugPanel['active_filter'] ?? '')) ?></dd>
            <dt>SQL IN status codes</dt>
            <dd><code><?= e(implode(', ', $vfDebugPanel['sql_status_codes'] ?? [])) ?></code></dd>
            <dt>Included dispatch order IDs (first 20)</dt>
            <dd><code><?= e(implode(', ', array_map('strval', $vfDebugPanel['included_dispatch_order_ids'] ?? []))) ?></code>
                <?php if (($vfDebugPanel['included_dispatch_total'] ?? 0) > 20): ?>
                <span class="text-muted">(<?= e((string) ($vfDebugPanel['included_dispatch_total'] ?? 0)) ?> total)</span>
                <?php endif; ?>
            </dd>
            <dt>Raw ibs_status histogram (top 10)</dt>
            <dd>
                <?php foreach (($vfDebugPanel['raw_status_histogram'] ?? []) as $status => $count): ?>
                <span class="vf-debug-hist-item"><code><?= e((string) $status) ?></code>=<?= e((string) $count) ?></span>
                <?php endforeach; ?>
            </dd>
            <dt>stageCounts[filter] vs list total</dt>
            <dd>
                <?= e((string) ($vfDebugPanel['stage_count_for_filter'] ?? 0)) ?>
                vs
                <?= e((string) ($vfDebugPanel['list_total_for_filter'] ?? 0)) ?>
                <?php if (!empty($vfDebugPanel['counts_match'])): ?>
                <span class="vf-debug-match-ok">✓ match</span>
                <?php else: ?>
                <span class="vf-debug-match-bad">✗ mismatch</span>
                <?php endif; ?>
            </dd>
        </dl>
    </div>
</details>
<?php endif; ?>

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

<?php if (!empty($recentWorkflowHistory)): ?>
<details class="planning-collapsible vf-history-collapsible">
    <summary class="planning-collapsible-summary">Recent Workflow History</summary>
    <div class="planning-collapsible-body">
        <div class="table-scroll vf-history-table-wrap">
            <table class="data-table vf-history-table">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>From</th>
                        <th>To</th>
                        <th>Action</th>
                        <th>Note</th>
                        <th>Batch</th>
                        <th>User</th>
                        <th>At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentWorkflowHistory as $row): ?>
                    <tr>
                        <td><?= e($row['order_reference']) ?></td>
                        <td><?= e($row['from_label']) ?></td>
                        <td><?= e($row['to_label']) ?></td>
                        <td><?= e($row['action_label'] ?? '—') ?></td>
                        <td><?= e($displayActionNote($row['action_note'] ?? null)) ?></td>
                        <td><?= e($row['batch_reference'] ?? '—') ?></td>
                        <td><?= e($row['changed_by'] !== '' ? $row['changed_by'] : '—') ?></td>
                        <td><?= e($row['changed_at']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</details>
<?php endif; ?>

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

<?php if (!empty($requestTiming)): ?>
<p class="page-description vf-timing-footer">Page timing (local): <?php
    $parts = [];
    foreach ($requestTiming as $label => $ms) {
        $parts[] = e((string) $label) . '=' . e((string) $ms) . 'ms';
    }
    echo implode(' · ', $parts);
?></p>
<?php endif; ?>

<?php else: ?>
<?php view('partials.write-gate-warning', ['writeGateReady' => $writeGateReady ?? false, 'writeGate' => $writeGate ?? []]); ?>
<?php endif; ?>
</div>

<?php if ($showDeveloperContent): ?>
<details class="planning-collapsible">
    <summary class="planning-collapsible-summary">Read-Only Order Workflow Inventory (developer reference)</summary>
    <div class="planning-collapsible-body">
        <p class="page-description" style="margin-bottom: 1rem;">SELECT only. No database writes from these inventory cards.</p>
        <?php view('partials.read-inventory-card', ['readInventory' => $orderReadInventory, 'cardTitle' => 'Orders']); ?>
        <?php view('partials.read-inventory-card', ['readInventory' => $orderItemReadInventory, 'cardTitle' => 'Order Items']); ?>
        <?php view('partials.read-inventory-card', ['readInventory' => $orderWorkflowHistoryReadInventory, 'cardTitle' => 'Order Workflow Histories']); ?>
    </div>
</details>

<details class="planning-collapsible">
    <summary class="planning-collapsible-summary">Planning Foundation (reference)</summary>
    <div class="planning-collapsible-body">

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Current Workflow Context</h2>
        </div>
        <div class="card-body">
            <dl class="info-list">
                <div class="info-row">
                    <dt>Primary Supplier</dt>
                    <dd><?= e($currentContext['supplier']) ?></dd>
                </div>
                <div class="info-row">
                    <dt>Primary Source</dt>
                    <dd><?= e($currentContext['source']) ?></dd>
                </div>
            </dl>
            <p class="page-description"><?= e($currentContext['summary']) ?></p>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Workflow Purpose</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($purpose as $item): ?>
                    <li><?= e($item) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title"><?= e($fulfillmentTableColumns['title'] ?? 'Vendor Fulfillment Table') ?></h2>
    </div>
    <div class="card-body">
        <ul class="feature-list">
            <?php foreach ($fulfillmentTableColumns['columns'] ?? [] as $column): ?>
                <li><?= e($column) ?></li>
            <?php endforeach; ?>
        </ul>
        <p class="page-description"><?= e($fulfillmentTableColumns['note'] ?? '') ?></p>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Performance Rules</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($performanceRules as $rule): ?>
                    <li><?= e($rule) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><?= e($independentRule['title']) ?></h2>
        </div>
        <div class="card-body">
            <p><?= e($independentRule['summary']) ?></p>
            <ul class="feature-list">
                <?php foreach ($independentRule['points'] as $point): ?>
                    <li><?= e($point) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

    </div>
</details>
<?php endif; ?>

<script src="<?= e(asset('js/order-workflow.js')) ?>"></script>
