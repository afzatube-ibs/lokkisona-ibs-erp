<?php
$manualOrderRows = array_slice($manualOrderReadInventory['rows'] ?? [], 0, 20);
$manualOrderItemRows = array_slice($manualOrderItemReadInventory['rows'] ?? [], 0, 20);
$bridgeOrderRows = array_slice($orderReadInventory['rows'] ?? [], 0, 20);
$bridgeReady = !empty($orderReadInventory['table_exists']) && !empty($orderItemReadInventory['table_exists']);
$cell = static function (array $row, string $key): string {
    return (string) ($row[$key] ?? '');
};
$displayNote = static function (?string $note): string {
    $note = trim((string) $note);

    return $note !== '' ? $note : '-';
};
$manualOrderNotes = $manualOrderConfirmationNotes ?? [];
$orderBridgeNotes = $orderBridgeConfirmationNotes ?? [];
$businessSourceOptions = $businessSourceOptions ?? [];
$supplierOptions = $supplierOptions ?? [];
$productOptions = $productOptions ?? [];
$recentOrders = $recentOrders ?? [];
?>
<div class="page-header">
    <h1 class="page-title">Sales</h1>
    <p class="page-description">v<?= e($appVersion) ?> — <?= e($appReleaseLabel ?? '') ?> · Offline, Sonamoni reference, and manual channel entry. No payable, stock deduction, invoice, or channel sync on create.</p>
</div>

<?php view('partials.flash-messages', ['flashSuccess' => $flashSuccess ?? null, 'flashError' => $flashError ?? null]); ?>

<div class="manual-order-safety-strip" style="margin-bottom:1.5rem;padding:0.65rem 1rem;background:var(--color-bg);border:1px solid var(--color-border);border-radius:var(--radius-md);font-size:0.8125rem;">
    <strong>Safety:</strong> No payable · No stock deducted · No invoice · No channel sync
</div>

<?php if (!empty($writeGateReady)): ?>
<div class="card" style="margin-bottom:1.5rem;">
    <div class="card-header"><h2 class="card-title">Create Manual Order</h2></div>
    <div class="card-body">
        <?php view('partials.manual-order-create-form', [
            'formAction' => url('/manual-orders/create'),
            'formId' => 'manualOrderForm',
            'submitLabel' => 'Create Manual Order',
            'csrfField' => $csrfField ?? '',
            'businessSourceOptions' => $businessSourceOptions,
            'supplierOptions' => $supplierOptions,
            'productOptions' => $productOptions,
        ]); ?>
    </div>
</div>

<script type="application/json" id="moVariantMap"><?= json_encode($variantOptionsByProduct ?? [], JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
<script type="application/json" id="moProductCosts"><?= json_encode($productCostById ?? [], JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
<template id="moProductOptionsTemplate"><?php foreach ($productOptions as $opt): ?><option value="<?= (int) $opt['id'] ?>"><?= e($opt['label']) ?></option><?php endforeach; ?></template>
<script src="<?= e(asset('js/manual-orders.js')) ?>"></script>
<?php else: ?>
<?php view('partials.write-gate-warning', [
    'writeGateReady' => $writeGateReady ?? false,
    'writeGate' => $writeGate ?? [],
    'writeGateMessage' => ($writeGate['message'] ?? \App\ReadFoundation\WriteGate::WARNING_MESSAGE)
        . (isset($writeGateMigrationHint) ? ' Apply ' . $writeGateMigrationHint : ''),
]); ?>
<?php endif; ?>

<div class="card" style="margin-bottom:1.5rem;">
    <div class="card-header"><h2 class="card-title">Recent Manual Orders</h2></div>
    <div class="card-body">
        <?php if (!empty($recentOrders)): ?>
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Customer</th>
                        <th>Source</th>
                        <th>Items</th>
                        <th>Order Total</th>
                        <th>Cost Snapshot</th>
                        <th>Created</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentOrders as $order): ?>
                    <tr>
                        <td><?= e($order['manual_order_reference']) ?></td>
                        <td><?= e($order['customer_name']) ?></td>
                        <td><?= e($order['source_label']) ?></td>
                        <td><?= (int) $order['item_count'] ?></td>
                        <td><?= e(number_format((float) $order['order_total'], 2)) ?></td>
                        <td><?= e(number_format((float) $order['cost_snapshot_total'], 2)) ?></td>
                        <td><?= e($order['created_at']) ?></td>
                        <td>
                            <?php if (!empty($order['order_id'])): ?>
                                <a href="<?= e(url('/order-workflow?status=new_order')) ?>" class="btn btn-ghost btn-sm">Open in Workflow</a>
                            <?php else: ?>
                                <span class="badge badge-warn">Manual only</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <p class="page-description"><?= e($manualOrderReadInventory['status_message'] ?? 'No manual orders yet.') ?></p>
        <?php endif; ?>
    </div>
</div>

<details class="planning-collapsible">
    <summary class="planning-collapsible-summary">Developer reference — audit tables &amp; raw inventory</summary>
    <div class="planning-collapsible-body">

        <div class="card" style="margin-bottom:1.5rem;">
            <div class="card-header"><h2 class="card-title">Manual Order Audit Confirmation (latest 20)</h2></div>
            <div class="card-body">
                <?php if (!empty($manualOrderRows)): ?>
                <div class="table-scroll">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Manual Order ID</th>
                            <th>Manual Ref</th>
                            <th>External Ref</th>
                            <th>Customer</th>
                            <th>Phone</th>
                            <th>Source ID</th>
                            <th>Supplier ID</th>
                            <th>IBS Status</th>
                            <th>Entry Status</th>
                            <th>Order Total</th>
                            <th>Confirmation Note</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($manualOrderRows as $row): ?>
                        <?php $confirmationNote = $manualOrderNotes[(int) ($row['manual_order_id'] ?? 0)] ?? ''; ?>
                        <tr>
                            <td><?= e($cell($row, 'manual_order_id')) ?></td>
                            <td><?= e($cell($row, 'manual_order_reference')) ?></td>
                            <td><?= e($cell($row, 'external_order_reference')) ?></td>
                            <td><?= e($cell($row, 'customer_name')) ?></td>
                            <td><?= e($cell($row, 'customer_phone')) ?></td>
                            <td><?= e($cell($row, 'business_source_id')) ?></td>
                            <td><?= e($cell($row, 'supplier_id')) ?></td>
                            <td><?= e($cell($row, 'ibs_status')) ?></td>
                            <td><?= e($cell($row, 'entry_status')) ?></td>
                            <td><?= e($cell($row, 'order_total')) ?></td>
                            <td><strong class="audit-note"><?= e($displayNote($confirmationNote)) ?></strong></td>
                            <td><?= e($cell($row, 'created_at')) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
                <?php else: ?>
                <p class="page-description"><?= e($manualOrderReadInventory['status_message'] ?? 'No manual order records yet.') ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="card" style="margin-bottom:1.5rem;">
            <div class="card-header"><h2 class="card-title">Manual Order Items (latest 20)</h2></div>
            <div class="card-body">
                <?php if (!empty($manualOrderItemRows)): ?>
                <div class="table-scroll">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Item ID</th>
                            <th>Manual Order ID</th>
                            <th>Product ID</th>
                            <th>Variant ID</th>
                            <th>Product Name</th>
                            <th>Variant Label</th>
                            <th>Qty</th>
                            <th>Selling Price</th>
                            <th>Cost Snapshot</th>
                            <th>Line Total</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($manualOrderItemRows as $row): ?>
                        <tr>
                            <td><?= e($cell($row, 'manual_order_item_id')) ?></td>
                            <td><?= e($cell($row, 'manual_order_id')) ?></td>
                            <td><?= e($cell($row, 'product_id')) ?></td>
                            <td><?= e($cell($row, 'product_variant_id')) ?></td>
                            <td><?= e($cell($row, 'product_name')) ?></td>
                            <td><?= e($cell($row, 'variant_label')) ?></td>
                            <td><?= e($cell($row, 'quantity')) ?></td>
                            <td><?= e($cell($row, 'selling_price')) ?></td>
                            <td><?= e($cell($row, 'supplier_cost_snapshot')) ?></td>
                            <td><?= e($cell($row, 'line_total')) ?></td>
                            <td><?= e($cell($row, 'created_at')) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
                <?php else: ?>
                <p class="page-description"><?= e($manualOrderItemReadInventory['status_message'] ?? 'No manual order items yet.') ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="card" style="margin-bottom:1.5rem;">
            <div class="card-header"><h2 class="card-title">ERP Order Bridge</h2></div>
            <div class="card-body">
                <?php if ($bridgeReady): ?>
                    <?php if (!empty($bridgeOrderRows)): ?>
                    <div class="table-scroll">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Order Ref</th>
                                <th>Source Ref</th>
                                <th>Customer</th>
                                <th>IBS Status</th>
                                <th>Order Total</th>
                                <th>Cost Snapshot Total</th>
                                <th>Confirmation Note</th>
                                <th>Created At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bridgeOrderRows as $row): ?>
                            <?php $bridgeNote = $orderBridgeNotes[(int) ($row['order_id'] ?? 0)] ?? ''; ?>
                            <tr>
                                <td><?= e($cell($row, 'order_id')) ?></td>
                                <td><?= e($cell($row, 'order_reference')) ?></td>
                                <td><?= e($cell($row, 'source_order_reference')) ?></td>
                                <td><?= e($cell($row, 'customer_name')) ?></td>
                                <td><?= e($cell($row, 'ibs_status')) ?></td>
                                <td><?= e($cell($row, 'order_total')) ?></td>
                                <td><?= e($cell($row, 'cost_snapshot_total')) ?></td>
                                <td><strong class="audit-note"><?= e($displayNote($bridgeNote)) ?></strong></td>
                                <td><?= e($cell($row, 'created_at')) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                    <?php else: ?>
                    <p class="page-description">Bridge tables ready; no bridged ERP orders yet.</p>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="page-description"><span class="badge badge-warn">Bridge not ready</span> ERP order bridge tables not applied.</p>
                <?php endif; ?>
            </div>
        </div>

        <p class="page-description" style="margin-bottom:1rem;">SELECT only. No database writes from these inventory cards.</p>
        <?php view('partials.read-inventory-card', ['readInventory' => $manualOrderReadInventory, 'cardTitle' => 'Manual Orders raw read inventory']); ?>
        <?php view('partials.read-inventory-card', ['readInventory' => $manualOrderItemReadInventory, 'cardTitle' => 'Manual Order Items raw read inventory']); ?>
        <?php view('partials.read-inventory-card', ['readInventory' => $orderReadInventory, 'cardTitle' => 'ERP Orders raw read inventory']); ?>
        <?php view('partials.read-inventory-card', ['readInventory' => $orderItemReadInventory, 'cardTitle' => 'ERP Order Items raw read inventory']); ?>
        <?php view('partials.read-inventory-card', ['readInventory' => $productReadInventory, 'cardTitle' => 'Products raw read inventory']); ?>
        <?php view('partials.read-inventory-card', ['readInventory' => $productVariantReadInventory, 'cardTitle' => 'Product Variants raw read inventory']); ?>

    </div>
</details>

<details class="planning-collapsible">
    <summary class="planning-collapsible-summary">Planning Foundation (reference)</summary>
    <div class="planning-collapsible-body">

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Current Manual Order Context</h2>
        </div>
        <div class="card-body">
            <dl class="info-list">
                <div class="info-row">
                    <dt>Primary Source</dt>
                    <dd><?= e($currentContext['primarySource']) ?></dd>
                </div>
                <div class="info-row">
                    <dt>Future Source</dt>
                    <dd><?= e($currentContext['futureSource']) ?></dd>
                </div>
                <div class="info-row">
                    <dt>Primary Supplier</dt>
                    <dd><?= e($currentContext['primarySupplier']) ?></dd>
                </div>
            </dl>
            <p class="page-description"><?= e($currentContext['summary']) ?></p>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Manual / External Order Purpose</h2>
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
    <?php foreach ([$sonamoniReferencePlan, $offlineOrderPlan, $businessSourceRule, $externalReferenceRule] as $section): ?>
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
    <?php foreach ([$productMappingRule, $sharedStockRule, $costSnapshotRule, $workflowEntryRule] as $section): ?>
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
    <?php foreach ([$invoicePlanningRule, $confirmationAuditRule, $duplicateReferenceRule, $woocommerceUpgradeRule] as $section): ?>
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
            <h2 class="card-title">Planned manual_orders Fields</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($plannedManualOrderFields as $field): ?>
                    <li><code><?= e($field) ?></code></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Planned manual_order_items Fields</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($plannedManualOrderItemFields as $field): ?>
                    <li><code><?= e($field) ?></code></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Planned manual_order_audits Fields</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($plannedManualOrderAuditFields as $field): ?>
                    <li><code><?= e($field) ?></code></li>
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
            <p class="page-description">Owner and admin can create manual orders. Supplier role should not create global manual orders unless explicitly allowed later.</p>
        </div>
    </div>
</div>

    </div>
</details>
