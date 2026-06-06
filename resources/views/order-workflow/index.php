<?php
$displayActionNote = static function (?string $note): string {
    $note = trim((string) $note);

    return $note !== '' ? $note : '-';
};
?>
<div class="page-header">
    <h1 class="page-title">Order Workflow</h1>
    <p class="page-description">Fulfillment Workflow Action Foundation (v0.4.4.0). Iqbal &amp; Brothers supplier workflow. Orders grouped by status with allowed supplier actions only. Max 50 orders per load. <?= e($syncImportRuleNote ?? '') ?></p>
</div>

<?php view('partials.flash-messages', ['flashSuccess' => $flashSuccess ?? null, 'flashError' => $flashError ?? null]); ?>

<?php if (!empty($writeGateReady)): ?>
<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header"><h2 class="card-title">Workflow Actions (v0.4.4.0)</h2></div>
    <div class="card-body">
        <p class="page-description" style="margin-bottom: 1rem;">Only allowed supplier actions are shown. Notes required for Hold, Cancelled, Delivery Stop, Return Received, and Create Dispatch Report. Packaging and Shipped moves require staff checkbox confirmation. Courier stages (Dispatch Report Created onward) have no supplier manual buttons except Return Received from Delivery Stop.</p>
        <?php if (empty($workflowBoard)): ?>
            <p class="page-description">No orders available for workflow actions yet. Create a manual order first on <a href="<?= e(url('/manual-orders')) ?>">Manual Orders</a>.</p>
        <?php else: ?>
            <?php foreach ($workflowBoard as $group): ?>
            <div class="workflow-group">
                <h3 class="section-heading" style="margin: 0 0 0.75rem;"><?= e($group['label']) ?> <span class="badge"><?= count($group['orders']) ?></span></h3>
                <?php foreach ($group['orders'] as $order): ?>
                <div class="card workflow-order-card">
                    <div class="card-body">
                        <dl class="info-list" style="margin-bottom: 0.75rem;">
                            <div class="info-row"><dt>Order ID</dt><dd><?= e((string) $order['order_id']) ?></dd></div>
                            <div class="info-row"><dt>Reference</dt><dd><?= e($order['order_reference']) ?></dd></div>
                            <div class="info-row"><dt>Customer</dt><dd><?= e($order['customer_name']) ?></dd></div>
                            <div class="info-row"><dt>Status</dt><dd><?= e($order['ibs_status_label']) ?><?php if (!empty($order['legacy_status'])): ?> <span class="badge badge-warn">legacy: <?= e($order['legacy_status']) ?></span><?php endif; ?></dd></div>
                        </dl>

                        <?php if (!empty($order['status_info_note'])): ?>
                        <p class="workflow-info-banner"><?= e($order['status_info_note']) ?></p>
                        <?php endif; ?>

                        <?php if (!empty($order['actions'])): ?>
                        <div class="workflow-action-grid">
                            <?php foreach ($order['actions'] as $action): ?>
                            <form method="post" action="<?= e(url('/order-workflow/action')) ?>" class="workflow-action-form js-workflow-action-form" data-confirm-label="<?= e($action['label']) ?>">
                                <?= $csrfField ?? '' ?>
                                <input type="hidden" name="order_id" value="<?= e((string) $order['order_id']) ?>">
                                <input type="hidden" name="to_status" value="<?= e($action['code']) ?>">
                                <input type="hidden" name="action_confirmed" value="0" class="js-action-confirmed">
                                <strong><?= e($action['label']) ?></strong>
                                <?php if (!empty($action['is_dispatch_gate'])): ?>
                                    <p class="page-description" style="margin: 0.5rem 0;"><em><?= e($dispatchDevNote ?? '') ?></em></p>
                                <?php endif; ?>
                                <?php if (!empty($action['requires_checkbox']) && !empty($action['checkbox_label'])): ?>
                                <label class="workflow-confirm-checkbox">
                                    <input type="checkbox" name="staff_confirmation" value="1" required>
                                    <span><?= e($action['checkbox_label']) ?></span>
                                </label>
                                <?php endif; ?>
                                <label>
                                    Action note<?= !empty($action['requires_note']) ? ' *' : ' (optional)' ?>
                                    <textarea name="action_note" class="form-input" <?= !empty($action['requires_note']) ? 'required' : '' ?> placeholder="<?= !empty($action['is_dispatch_gate']) ? 'Dispatch reference e.g. DR-04062026-12' : '' ?>"></textarea>
                                </label>
                                <button type="submit" class="btn btn-primary" style="margin-top: 0.5rem;"><?= e($action['label']) ?></button>
                            </form>
                            <?php endforeach; ?>
                        </div>
                        <?php elseif (empty($order['status_info_note'])): ?>
                        <p class="page-description">No further supplier actions (terminal or sync-only state).</p>
                        <?php endif; ?>

                        <?php if (!empty($order['histories'])): ?>
                        <h4 style="margin: 1rem 0 0.5rem;">Workflow History</h4>
                        <div class="table-scroll">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>When</th>
                                        <th>From</th>
                                        <th>To</th>
                                        <th>Action Note</th>
                                        <th>Changed By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($order['histories'] as $history): ?>
                                    <tr>
                                        <td><?= e((string) ($history['changed_at'] ?? '')) ?></td>
                                        <td><?= e((string) ($history['from_label'] ?? $history['from_status'] ?? '')) ?></td>
                                        <td><?= e((string) ($history['to_label'] ?? $history['to_status'] ?? '')) ?></td>
                                        <td><strong class="audit-note"><?= e($displayActionNote((string) ($history['action_note'] ?? ''))) ?></strong></td>
                                        <td><?= e((string) ($history['changed_by'] ?? '')) !== '' ? e((string) $history['changed_by']) : '-' ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
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
