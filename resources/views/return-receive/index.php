<?php use App\Domain\ReturnReceiveType; ?>
<div class="page-header page-header-compact">
    <h1 class="page-title">Return Receive</h1>
    <p class="ops-page-subtitle">Pending Supplier Confirmation — confirm returned parcels before creating a Supplier Return Statement on <a href="<?= e(url('/return-reports')) ?>">Return Reports</a>. <?= e($stageNote ?? '') ?></p>
</div>

<?php view('partials.flash-messages', ['flashSuccess' => $flashSuccess ?? null, 'flashError' => $flashError ?? null]); ?>

<?php view('partials.ops-safety-strip', ['message' => 'Receiving confirmation only · No payable · No supplier ledger · No stock movement · No invoice · No live sync']); ?>

<?php
$renderPendingSection = static function (
    string $title,
    string $intro,
    string $emptyMessage,
    array $orders,
    bool $isSupplierReturn,
    bool $isLokkisonaReturn
) use ($csrfField, $reasonOptions, $receivedConfirmationOptions, $conditionOptions): void {
    ?>
<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header"><h2 class="card-title"><?= e($title) ?></h2></div>
    <div class="card-body">
        <p class="page-description" style="margin-bottom: 1rem;"><?= e($intro) ?></p>
        <?php if ($orders !== []): ?>
            <?php foreach ($orders as $order): ?>
            <div class="card workflow-order-card" style="margin-bottom: 1rem;">
                <div class="card-body">
                    <?php view('partials.return-receive-order-details', ['order' => $order]); ?>
                    <?php view('partials.return-receive-form', [
                        'order' => $order,
                        'csrfField' => $csrfField,
                        'reasonOptions' => $reasonOptions,
                        'receivedConfirmationOptions' => $receivedConfirmationOptions,
                        'conditionOptions' => $conditionOptions,
                        'isSupplierReturn' => $isSupplierReturn,
                        'isLokkisonaReturn' => $isLokkisonaReturn,
                    ]); ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
        <p class="page-description"><span class="badge badge-warn">No pending returns</span> <?= e($emptyMessage) ?></p>
        <?php endif; ?>
    </div>
</div>
    <?php
};
?>

<?php if (!empty($writeGateReady)): ?>

<?php $renderPendingSection(
    'Pending Supplier Confirmation — Hub Returns / Courier Returns',
    'Supplier Return / Vendor Return — Hub Return / Courier Return. Orders at Hub Return from workflow Shipped → Delivery Stop → Return Received.',
    'Move an order to Hub Return via Order Workflow (Delivery Stop → Return Received).',
    $pendingHubReturns ?? [],
    true,
    false
); ?>

<?php $renderPendingSection(
    'Pending Supplier Confirmation — Customer Returns to Supplier',
    $customerReturnEmptyNote ?? '',
    'Orders appear when status is Customer Return / Order Returning (Supplier House mapping).',
    $pendingCustomerReturns ?? [],
    true,
    false
); ?>

<?php $renderPendingSection(
    'Pending Lokkisona / Owner Warehouse Returns',
    $lokkisonaReturnEmptyNote ?? '',
    'Orders appear when status is Customer Return / Order Returning (Lokkisona warehouse mapping).',
    $pendingLokkisonaReturns ?? [],
    false,
    true
); ?>

<?php else: ?>
<?php view('partials.write-gate-warning', ['writeGateReady' => $writeGateReady ?? false, 'writeGate' => $writeGate ?? []]); ?>
<?php endif; ?>

<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header"><h2 class="card-title">Latest Received Returns</h2></div>
    <div class="card-body">
        <?php if (!empty($latestReceived)): ?>
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Order Ref</th>
                        <th>Destination</th>
                        <th>Return Type</th>
                        <th>Reason / Source</th>
                        <th>Received Status</th>
                        <th>Supplier Condition</th>
                        <th>Products</th>
                        <th>Supplier Note</th>
                        <th>Owner / Lokkisona Note</th>
                        <th>Received At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($latestReceived as $row): ?>
                    <tr>
                        <td><strong><?= e((string) ($row['order_reference'] ?? '—')) ?></strong></td>
                        <td><?= e((string) ($row['destination_label'] ?? '')) ?></td>
                        <td><?= e((string) ($row['return_type_label'] ?? '')) ?></td>
                        <td><?= e((string) ($row['reason_label'] ?? '—')) ?></td>
                        <td><?= e((string) ($row['received_label'] ?? '—')) ?></td>
                        <td><span class="badge <?= e((string) ($row['condition_badge'] ?? 'badge-ok')) ?>"><?= e((string) ($row['condition_label'] ?? '—')) ?></span></td>
                        <td class="cell-detail"><?= e((string) ($row['product_summary'] ?? '—')) ?></td>
                        <td><?= e((string) ($row['supplier_note_display'] ?? '—')) ?></td>
                        <td><?= e((string) ($row['owner_note_display'] ?? '—')) ?></td>
                        <td><?= e((string) ($row['received_at'] ?? '')) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <p class="page-description">No received returns yet.</p>
        <?php endif; ?>
    </div>
</div>

<div class="workflow-info-banner" style="margin-bottom: 1.5rem;">
    Confirmed returns are grouped on <a href="<?= e(url('/return-reports')) ?>"><strong>Return Reports</strong></a> as a locked Supplier Return Statement. Legacy Return Batch UI retired in v2.4.0 — no ledger posting from this page.
</div>

<details class="planning-collapsible">
    <summary class="planning-collapsible-summary">Read-Only Return Receive Inventory (developer reference)</summary>
    <div class="planning-collapsible-body">
        <p class="page-description" style="margin-bottom: 1rem;">SELECT only. Return receive submit uses the forms above when tables are ready.</p>
        <?php view('partials.read-inventory-card', ['readInventory' => $readInventory, 'cardTitle' => 'Return Receives']); ?>
    </div>
</details>

<details class="planning-collapsible">
    <summary class="planning-collapsible-summary">Planning Foundation (reference)</summary>
    <div class="planning-collapsible-body">

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Current Return Context</h2>
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
            <h2 class="card-title">Return Receive Purpose</h2>
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

<div class="card-grid">
    <?php foreach ([$supplierReturnFlow, $lokkisonaReturnFlow, $syncMappingRule, $manualEntryRule] as $section): ?>
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><?= e($section['title']) ?></h2>
        </div>
        <div class="card-body">
            <p><?= e($section['summary']) ?></p>
            <ul class="feature-list">
                <?php foreach ($section['points'] as $point): ?>
                    <li><?= e($point) ?></li>
                <?php endforeach; ?>
            </ul>
            <?php if ($section['title'] === 'Sync Status Mapping Rule'): ?>
            <p class="page-description">Supplier Return and Lokkisona Return candidates must be separated during Sync Preview. See <a href="<?= e(url('/status-mapping')) ?>">Status Mapping</a> and <a href="<?= e(url('/sync-preview')) ?>">Sync Preview</a> planning foundations.</p>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><?= e($confirmationRule['title']) ?></h2>
        </div>
        <div class="card-body">
            <p><?= e($confirmationRule['summary']) ?></p>
            <ul class="feature-list">
                <?php foreach ($confirmationRule['points'] as $point): ?>
                    <li><?= e($point) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Pre-Submit Total Warning</h2>
        </div>
        <div class="card-body">
            <p class="page-description">The confirmation warning must show these totals before submit:</p>
            <ul class="feature-list">
                <?php foreach ($totalsWarning as $item): ?>
                    <li><?= e($item) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Confirmation Examples</h2>
    </div>
    <div class="card-body card-body-flush">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Return Type</th>
                    <th>Payable Impact</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($confirmationExamples as $example): ?>
                <tr>
                    <td class="cell-name"><?= e($example['type']) ?></td>
                    <td class="cell-detail"><?= e($example['impact']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card-grid">
    <?php foreach ([$returnBatchPlan, $supplierReviewRule, $lokkisonaNoDeductionRule, $payableAdjustmentRule] as $section): ?>
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><?= e($section['title']) ?></h2>
        </div>
        <div class="card-body">
            <p><?= e($section['summary']) ?></p>
            <ul class="feature-list">
                <?php foreach ($section['points'] as $point): ?>
                    <li><?= e($point) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Remark / Note Rule</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($remarkRule as $rule): ?>
                    <li><?= e($rule) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Optional Image / Proof Upload Plan</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($imageProofPlan as $item): ?>
                    <li><?= e($item) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Approval &amp; Audit Rule</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($approvalRule as $rule): ?>
                    <li><?= e($rule) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Launch Cutover Return Boundary</h2>
        </div>
        <div class="card-body">
            <p>Old return deductions are included in Supplier Opening Balance planning before launch.</p>
            <p class="page-description">New returns after launch go through the normal Return Receive workflow and payable adjustment review.</p>
        </div>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Future Report / Export Plan</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($reportPlan as $item): ?>
                    <li><?= e($item) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<div class="card-grid">
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
            <p class="page-description">Owner and admin can view the Return Receive planning foundation now. Staff may view/manage later based on permission. Supplier role should not see all return receive / batch / payable-impact controls.</p>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Planned Return Receive Fields</h2>
        </div>
        <div class="card-body">
            <p class="page-description">Documented only. No return tables are created automatically and no records are written.</p>
            <ul class="feature-list">
                <?php foreach ($plannedReceiveFields as $field): ?>
                    <li><code><?= e($field) ?></code></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Planned Return Batch Fields</h2>
        </div>
        <div class="card-body">
            <p class="page-description">Documented only. No return tables are created automatically and no records are written.</p>
            <ul class="feature-list">
                <?php foreach ($plannedBatchFields as $field): ?>
                    <li><code><?= e($field) ?></code></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Planned Return Item Fields</h2>
        </div>
        <div class="card-body">
            <p class="page-description">Documented only. No return tables are created automatically and no records are written.</p>
            <ul class="feature-list">
                <?php foreach ($plannedItemFields as $field): ?>
                    <li><code><?= e($field) ?></code></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Planned Payable Adjustment Fields</h2>
        </div>
        <div class="card-body">
            <p class="page-description">Documented only. No payable adjustment tables are created automatically and no records are written.</p>
            <ul class="feature-list">
                <?php foreach ($plannedAdjustmentFields as $field): ?>
                    <li><code><?= e($field) ?></code></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

    </div>
</details>
