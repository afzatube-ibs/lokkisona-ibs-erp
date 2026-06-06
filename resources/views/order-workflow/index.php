<?php
$displayActionNote = static function (?string $note): string {
    $note = trim((string) $note);

    return $note !== '' ? $note : '-';
};
?>
<div class="page-header">
    <h1 class="page-title">Vendor Fulfillment / Order Workflow</h1>
    <p class="page-description">Supplier fulfillment workflow (v0.4.6.0). Iqbal &amp; Brothers. Created Report batch is created on <a href="<?= e(url('/dispatch-reports')) ?>">Dispatch Reports</a>. Out For Delivery and Delivered change by courier/status mapping.</p>
</div>

<?php view('partials.flash-messages', ['flashSuccess' => $flashSuccess ?? null, 'flashError' => $flashError ?? null]); ?>

<?php if (!empty($writeGateReady)): ?>
<?php if (!empty($workflowStageNav)): ?>
<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header"><h2 class="card-title">Workflow Stages</h2></div>
    <div class="card-body">
        <div class="workflow-stage-grid">
            <?php foreach ($workflowStageNav['main'] ?? [] as $stage): ?>
            <div class="workflow-stage-card">
                <span class="workflow-stage-label"><?= e($stage['label']) ?></span>
                <span class="workflow-stage-count badge"><?= (int) ($stage['count'] ?? 0) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="workflow-chip-row" style="margin-top: 0.75rem;">
            <?php foreach ($workflowStageNav['exceptions'] ?? [] as $stage): ?>
            <span class="workflow-chip"><?= e($stage['label']) ?> <strong><?= (int) ($stage['count'] ?? 0) ?></strong></span>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header"><h2 class="card-title">Vendor Fulfillment Orders</h2></div>
    <div class="card-body">
        <?php if (empty($workflowBoard)): ?>
            <p class="page-description">No orders in workflow yet. Create a manual order on <a href="<?= e(url('/manual-orders')) ?>">Manual Orders</a> or wait for mapped channel import.</p>
        <?php else: ?>
            <?php foreach ($workflowBoard as $group): ?>
            <div class="workflow-group<?= ($group['code'] ?? '') === 'dispatch_report_created' ? ' workflow-group-created-report' : '' ?>">
                <h3 class="section-heading" style="margin: 0 0 0.75rem;"><?= e($group['label']) ?> <span class="badge"><?= count($group['orders']) ?></span></h3>
                <?php if (!empty($group['info_note'])): ?>
                <p class="workflow-info-banner" style="margin-bottom: 0.75rem;"><?= e($group['info_note']) ?></p>
                <?php endif; ?>
                <?php if (($group['code'] ?? '') === 'dispatch_report_created' && empty($group['orders'])): ?>
                <p class="page-description">No orders in Created Report yet. Shipped orders move here after Daily Dispatch Report is created.</p>
                <?php endif; ?>
                <?php foreach ($group['orders'] as $order): ?>
                <?php view('partials.workflow-order-card', [
                    'order' => $order,
                    'csrfField' => $csrfField ?? '',
                    'deliveryStopReasonOptions' => $deliveryStopReasonOptions ?? [],
                    'displayActionNote' => $displayActionNote,
                ]); ?>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<?php else: ?>
<?php view('partials.write-gate-warning', ['writeGateReady' => $writeGateReady ?? false, 'writeGate' => $writeGate ?? []]); ?>
<?php endif; ?>

<details class="planning-collapsible">
    <summary class="planning-collapsible-summary">Read-Only Order Workflow Inventory (developer reference)</summary>
    <div class="planning-collapsible-body">
        <p class="page-description" style="margin-bottom: 1rem;">SELECT only. No database writes. No order status action. No workflow mutation. No sync/import. No migration apply from this page.</p>
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
            <p class="page-description">Manual/external orders enter IBS workflow after confirmation and must not bypass workflow rules. Sync Preview/import cannot overwrite existing IBS workflow. See <a href="<?= e(url('/manual-orders')) ?>">Manual Orders planning foundation</a> and <a href="<?= e(url('/sync-preview')) ?>">Sync Preview planning foundation</a>.</p>
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
            <p class="page-description">Owner, admin, and staff can view the Order Workflow planning foundation now. No order, dispatch, or workflow tables are created automatically and no database records are written in this release.</p>
        </div>
    </div>
</div>

    </div>
</details>
