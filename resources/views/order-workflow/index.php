<?php
$displayActionNote = static function (?string $note): string {
    $note = trim((string) $note);

    return $note !== '' ? $note : '-';
};
$statusFilter = $statusFilter ?? null;
$recentWorkflowHistory = $recentWorkflowHistory ?? [];
?>
<div class="page-header page-header-compact">
    <h1 class="page-title">Order Workflow</h1>
    <p class="ops-page-subtitle">Filter by stage, create manual orders in-page, and advance through allowed actions. Created Report batches on <a href="<?= e(url('/dispatch-reports')) ?>">Dispatch Reports</a>.</p>
</div>

<?php view('partials.flash-messages', ['flashSuccess' => $flashSuccess ?? null, 'flashError' => $flashError ?? null]); ?>

<?php if (!empty($writeGateReady)): ?>
<?php if (!empty($workflowStageNav)): ?>
<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header"><h2 class="card-title">Workflow Stages</h2></div>
    <div class="card-body">
        <div class="workflow-filter-row" style="margin-bottom:0.75rem;">
            <a href="<?= e(url($workflowStageNav['all_url'] ?? '/order-workflow')) ?>" class="workflow-filter-pill<?= !empty($workflowStageNav['all_active']) ? ' is-active' : '' ?>">All stages</a>
        </div>
        <div class="workflow-stage-grid">
            <?php foreach ($workflowStageNav['main'] ?? [] as $stage): ?>
            <a href="<?= e(url($stage['url'] ?? '/order-workflow')) ?>" class="workflow-stage-card workflow-stage-link<?= !empty($stage['active']) ? ' is-active' : '' ?>">
                <span class="workflow-stage-label"><?= e($stage['label']) ?></span>
                <span class="workflow-stage-count badge"><?= (int) ($stage['count'] ?? 0) ?></span>
            </a>
            <?php endforeach; ?>
        </div>
        <div class="workflow-chip-row" style="margin-top: 0.75rem;">
            <?php foreach ($workflowStageNav['exceptions'] ?? [] as $stage): ?>
            <a href="<?= e(url($stage['url'] ?? '/order-workflow')) ?>" class="workflow-chip workflow-chip-link<?= !empty($stage['active']) ? ' is-active' : '' ?>"><?= e($stage['label']) ?> <strong><?= (int) ($stage['count'] ?? 0) ?></strong></a>
            <?php endforeach; ?>
        </div>
        <?php if ($statusFilter !== null): ?>
        <p class="page-description" style="margin:0.75rem 0 0;">Showing <strong><?= e(\App\Domain\OrderWorkflowStatus::groupDisplayLabel($statusFilter)) ?></strong> only. <a href="<?= e(url('/order-workflow')) ?>">Clear filter</a></p>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header workflow-orders-header">
        <h2 class="card-title">Vendor Fulfillment Orders</h2>
        <?php if (!empty($canManageWorkflow)): ?>
        <button type="button" class="btn btn-primary btn-sm" data-open-modal="workflowCreateOrderModal">+ Create New Order</button>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <?php if (empty($workflowBoard)): ?>
            <p class="page-description">
                <?php if ($statusFilter !== null): ?>
                    No orders in this stage yet.
                <?php else: ?>
                    No orders in workflow yet. Use <strong>Create New Order</strong> above or enter orders on <a href="<?= e(url('/manual-orders')) ?>">Manual Orders</a>.
                <?php endif; ?>
            </p>
        <?php else: ?>
            <?php foreach ($workflowBoard as $group): ?>
            <div class="workflow-group<?= ($group['code'] ?? '') === 'dispatch_report_created' ? ' workflow-group-created-report' : '' ?>">
                <h3 class="section-heading" style="margin: 0 0 0.75rem;"><?= e($group['label']) ?> <span class="badge"><?= count($group['orders']) ?></span></h3>
                <?php if (!empty($group['info_note'])): ?>
                <p class="workflow-info-banner" style="margin-bottom: 0.75rem;"><?= e($group['info_note']) ?></p>
                <?php endif; ?>
                <?php foreach ($group['orders'] as $order): ?>
                <?php view('partials.workflow-order-card', [
                    'order' => $order,
                    'csrfField' => $csrfField ?? '',
                    'deliveryStopReasonOptions' => $deliveryStopReasonOptions ?? [],
                    'displayActionNote' => $displayActionNote,
                    'statusFilter' => $statusFilter,
                ]); ?>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($recentWorkflowHistory)): ?>
<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header"><h2 class="card-title">Recent Workflow History</h2></div>
    <div class="card-body">
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>From</th>
                        <th>To</th>
                        <th>Note</th>
                        <th>Changed By</th>
                        <th>Changed At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentWorkflowHistory as $row): ?>
                    <tr>
                        <td><?= e($row['order_reference']) ?></td>
                        <td><?= e($row['from_label']) ?></td>
                        <td><?= e($row['to_label']) ?></td>
                        <td><?= e($displayActionNote($row['action_note'] ?? null)) ?></td>
                        <td><?= e($row['changed_by'] !== '' ? $row['changed_by'] : '-') ?></td>
                        <td><?= e($row['changed_at']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($canManageWorkflow)): ?>
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
        <h2 class="card-title">Main Workflow Path</h2>
    </div>
    <div class="card-body">
        <p class="page-description">
            <?php foreach ($mainFlowPath as $i => $stage): ?>
                <strong><?= e($stage) ?></strong><?= $i < count($mainFlowPath) - 1 ? ' &rarr; ' : '' ?>
            <?php endforeach; ?>
        </p>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Main Workflow Stages</h2>
    </div>
    <div class="card-body card-body-flush">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Label</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($mainStages as $stage): ?>
                <tr>
                    <td class="cell-name"><code><?= e($stage['code']) ?></code></td>
                    <td><?= e($stage['label']) ?></td>
                    <td class="cell-detail"><?= e($stage['description']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Exception Stages</h2>
    </div>
    <div class="card-body card-body-flush">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Label</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($exceptionStages as $stage): ?>
                <tr>
                    <td class="cell-name"><code><?= e($stage['code']) ?></code></td>
                    <td><?= e($stage['label']) ?></td>
                    <td class="cell-detail"><?= e($stage['description']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Allowed Transition Matrix</h2>
    </div>
    <div class="card-body card-body-flush">
        <table class="data-table">
            <thead>
                <tr>
                    <th>From</th>
                    <th>Allowed To</th>
                    <th>Note</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transitionMatrix as $row): ?>
                <tr>
                    <td class="cell-name"><?= e($row['from']) ?></td>
                    <td><?= e($row['to']) ?></td>
                    <td class="cell-detail"><?= e($row['note']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><?= e($dispatchGate['title']) ?></h2>
        </div>
        <div class="card-body">
            <p><?= e($dispatchGate['summary']) ?></p>
            <ul class="feature-list">
                <?php foreach ($dispatchGate['points'] as $point): ?>
                    <li><?= e($point) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><?= e($costSnapshotRule['title']) ?></h2>
        </div>
        <div class="card-body">
            <p><?= e($costSnapshotRule['summary']) ?></p>
            <ul class="feature-list">
                <?php foreach ($costSnapshotRule['points'] as $point): ?>
                    <li><?= e($point) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><?= e($mappingRule['title']) ?></h2>
        </div>
        <div class="card-body">
            <p><?= e($mappingRule['summary']) ?></p>
            <ul class="feature-list">
                <?php foreach ($mappingRule['points'] as $point): ?>
                    <li><?= e($point) ?></li>
                <?php endforeach; ?>
            </ul>
            <p class="page-description">Source status mapping is used only at import/sync time. IBS workflow remains independent after sync. See <a href="<?= e(url('/status-mapping')) ?>">Status Mapping planning foundation</a>.</p>
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
            <p class="page-description">Manual/external orders enter IBS workflow after confirmation and must not bypass workflow rules. See <a href="<?= e(url('/manual-orders')) ?>">Manual Orders</a> and <a href="<?= e(url('/sync-preview')) ?>">Sync Preview</a>.</p>
        </div>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Action Confirmation &amp; Activity Log Rules</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($actionLogRule as $rule): ?>
                    <li><?= e($rule) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

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
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><?= e($vendorReturnFuture['title'] ?? 'Vendor Return Future Rules') ?></h2>
        </div>
        <div class="card-body">
            <p><?= e($vendorReturnFuture['summary'] ?? '') ?></p>
            <ul class="feature-list">
                <?php foreach ($vendorReturnFuture['types'] ?? [] as $type): ?>
                    <li><?= e($type) ?></li>
                <?php endforeach; ?>
            </ul>
            <p class="page-description"><strong>Return received condition (later):</strong></p>
            <ul class="feature-list">
                <?php foreach ($vendorReturnFuture['conditions'] ?? [] as $condition): ?>
                    <li><?= e($condition) ?></li>
                <?php endforeach; ?>
            </ul>
            <p class="page-description"><?= e($vendorReturnFuture['note'] ?? '') ?></p>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><?= e($fulfillmentTableColumns['title'] ?? 'Future Vendor Fulfillment Table') ?></h2>
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
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Future Sync Safety Rules</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($futureSyncSafety as $rule): ?>
                    <li><?= e($rule) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Current Access Mode</h2>
        </div>
        <div class="card-body">
            <dl class="info-list">
                <div class="info-row">
                    <dt>Mode</dt>
                    <dd><?= e($accessMode['mode']) ?></dd>
                </div>
                <div class="info-row">
                    <dt>Current Role</dt>
                    <dd><?= e($accessMode['role']) ?></dd>
                </div>
            </dl>
            <p class="page-description">Owner, admin, and supplier can manage workflow actions when permitted. Staff can view the board.</p>
        </div>
    </div>
</div>

    </div>
</details>
