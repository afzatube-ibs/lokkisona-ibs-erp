<?php
$manualOrderRows = array_slice($manualOrderReadInventory['rows'] ?? [], 0, 20);
$manualOrderItemRows = array_slice($manualOrderItemReadInventory['rows'] ?? [], 0, 20);
$bridgeOrderRows = array_slice($orderReadInventory['rows'] ?? [], 0, 20);
$bridgeReady = !empty($orderReadInventory['table_exists']) && !empty($orderItemReadInventory['table_exists']);
$cell = static function (array $row, string $key): string {
    return (string) ($row[$key] ?? '');
};
?>
<div class="page-header">
    <h1 class="page-title">Manual Orders</h1>
    <p class="page-description">Manual order create QA and audit visibility repair (v0.4.2.9). DEV/TEST only; no payable, stock deduction, invoice, or channel sync.</p>
</div>

<?php view('partials.flash-messages', ['flashSuccess' => $flashSuccess ?? null, 'flashError' => $flashError ?? null]); ?>

<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header"><h2 class="card-title">Manual Order Safety Badges</h2></div>
    <div class="card-body">
        <span class="badge badge-warn">DEV/TEST ONLY</span>
        <span class="badge badge-ok">No payable created</span>
        <span class="badge badge-ok">No stock deducted</span>
        <span class="badge badge-ok">No invoice generated</span>
        <span class="badge badge-ok">No channel sync</span>
        <span class="badge badge-warn">Opening balance separate and draft/test only</span>
    </div>
</div>

<?php if (!empty($writeGateReady)): ?>
<div class="card" style="margin-bottom:1.5rem;">
    <div class="card-header"><h2 class="card-title">Create DEV/TEST Manual Order</h2></div>
    <div class="card-body">
        <form method="post" action="<?= e(url('/manual-orders/create')) ?>">
            <?= $csrfField ?? '' ?>
            <div class="form-grid" style="display: grid; gap: 0.75rem; max-width: 760px;">
                <label>Business Source ID *<input type="number" name="business_source_id" required min="1" class="form-input" style="width:100%"></label>
                <label>Supplier ID optional<input type="number" name="supplier_id" min="1" class="form-input" style="width:100%"></label>
                <label>Manual order reference optional<input type="text" name="manual_order_reference" class="form-input" style="width:100%"></label>
                <label>External order reference optional<input type="text" name="external_order_reference" class="form-input" style="width:100%"></label>
                <label>External invoice reference optional<input type="text" name="external_invoice_reference" class="form-input" style="width:100%"></label>
                <label>Customer name *<input type="text" name="customer_name" required class="form-input" style="width:100%"></label>
                <label>Customer phone<input type="text" name="customer_phone" class="form-input" style="width:100%"></label>
                <label>Customer address<textarea name="customer_address" class="form-input" style="width:100%; min-height: 72px;"></textarea></label>
                <label>Product ID *<input type="number" name="product_id" required min="1" class="form-input" style="width:100%"></label>
                <label>Product Variant ID optional<input type="number" name="product_variant_id" min="1" class="form-input" style="width:100%"></label>
                <label>Variant label optional<input type="text" name="variant_label" class="form-input" style="width:100%"></label>
                <label>Quantity *<input type="number" name="quantity" required value="1" min="1" class="form-input" style="width:100%"></label>
                <label>Selling price *<input type="number" name="selling_price" required step="0.01" min="0" class="form-input" style="width:100%"></label>
                <label>Confirmation note *<textarea name="confirmation_note" required class="form-input" style="width:100%; min-height: 72px;"></textarea></label>
                <label style="display:flex; gap:0.5rem; align-items:flex-start;">
                    <input type="checkbox" name="dev_test_confirmation" value="1" required>
                    <span>I confirm this is a dev/test manual order only and it must not create payable, stock deduction, invoice, or live sync.</span>
                </label>
                <button type="submit" class="btn btn-primary">Create manual order for testing</button>
            </div>
        </form>
    </div>
</div>
<?php else: ?>
<?php view('partials.write-gate-warning', ['writeGateReady' => $writeGateReady ?? false, 'writeGate' => $writeGate ?? []]); ?>
<?php endif; ?>

<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header"><h2 class="card-title">Manual Order Audit Confirmation latest 20</h2></div>
    <div class="card-body">
        <?php if (!empty($manualOrderRows)): ?>
        <table class="data-table" style="width:100%;">
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
                    <th>Created At</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($manualOrderRows as $row): ?>
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
                    <td><?= e($cell($row, 'created_at')) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p class="page-description"><span class="badge badge-warn">Empty state</span> <?= e($manualOrderReadInventory['status_message'] ?? 'Manual order audit confirmation is not ready yet.') ?></p>
        <?php endif; ?>
    </div>
</div>

<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header"><h2 class="card-title">Manual Order Items Confirmation latest 20</h2></div>
    <div class="card-body">
        <?php if (!empty($manualOrderItemRows)): ?>
        <table class="data-table" style="width:100%;">
            <thead>
                <tr>
                    <th>Manual Order Item ID</th>
                    <th>Manual Order ID</th>
                    <th>Product ID</th>
                    <th>Variant ID</th>
                    <th>Product Name</th>
                    <th>Variant Label</th>
                    <th>Quantity</th>
                    <th>Selling Price</th>
                    <th>Supplier Cost Snapshot</th>
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
        <?php else: ?>
        <p class="page-description"><span class="badge badge-warn">Empty state</span> <?= e($manualOrderItemReadInventory['status_message'] ?? 'Manual order item confirmation is not ready yet.') ?></p>
        <?php endif; ?>
    </div>
</div>

<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header"><h2 class="card-title">ERP Order Bridge Confirmation</h2></div>
    <div class="card-body">
        <?php if ($bridgeReady): ?>
            <p class="page-description">Latest bridged ERP orders from <code>ibs_orders</code>. SELECT only; no payable, stock deduction, invoice, or sync is triggered by this display.</p>
            <?php if (!empty($bridgeOrderRows)): ?>
            <table class="data-table" style="width:100%; margin-top: 1rem;">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Order Ref</th>
                        <th>Source Ref</th>
                        <th>Customer</th>
                        <th>Phone</th>
                        <th>Source ID</th>
                        <th>Supplier ID</th>
                        <th>IBS Status</th>
                        <th>Order Total</th>
                        <th>Cost Snapshot Total</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bridgeOrderRows as $row): ?>
                    <tr>
                        <td><?= e($cell($row, 'order_id')) ?></td>
                        <td><?= e($cell($row, 'order_reference')) ?></td>
                        <td><?= e($cell($row, 'source_order_reference')) ?></td>
                        <td><?= e($cell($row, 'customer_name')) ?></td>
                        <td><?= e($cell($row, 'customer_phone')) ?></td>
                        <td><?= e($cell($row, 'business_source_id')) ?></td>
                        <td><?= e($cell($row, 'supplier_id')) ?></td>
                        <td><?= e($cell($row, 'ibs_status')) ?></td>
                        <td><?= e($cell($row, 'order_total')) ?></td>
                        <td><?= e($cell($row, 'cost_snapshot_total')) ?></td>
                        <td><?= e($cell($row, 'created_at')) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p class="page-description" style="margin-top: 1rem;"><span class="badge badge-warn">Empty state</span> ERP bridge tables are ready, but no bridged ERP orders are visible yet.</p>
            <?php endif; ?>
        <?php else: ?>
            <p class="page-description"><span class="badge badge-warn">Bridge not ready</span> ERP order bridge tables not ready. Manual order was saved only in manual order tables.</p>
        <?php endif; ?>
    </div>
</div>

<h2 class="section-heading" style="margin: 1.5rem 0 0.75rem;">Raw Read Inventory</h2>
<p class="page-description" style="margin-bottom: 1rem;">SELECT only. No database writes, no migration apply, no payable creation, no stock deduction, no invoice generation, and no channel sync from these inventory cards.</p>
<?php view('partials.read-inventory-card', ['readInventory' => $manualOrderReadInventory, 'cardTitle' => 'Manual Orders raw read inventory']); ?>
<?php view('partials.read-inventory-card', ['readInventory' => $manualOrderItemReadInventory, 'cardTitle' => 'Manual Order Items raw read inventory']); ?>
<?php view('partials.read-inventory-card', ['readInventory' => $orderReadInventory, 'cardTitle' => 'ERP Orders raw read inventory']); ?>
<?php view('partials.read-inventory-card', ['readInventory' => $orderItemReadInventory, 'cardTitle' => 'ERP Order Items raw read inventory']); ?>

<h2 class="section-heading" style="margin: 1.5rem 0 1rem;">Planning Foundation</h2>

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
            <p class="page-description">Owner and admin can view Manual Order planning now. Staff may manage later based on permission. Supplier role should not create global manual orders unless explicitly allowed later.</p>
        </div>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Manual Migration Required</h2>
        </div>
        <div class="card-body">
            <p>No manual order, manual order item, or manual order audit tables are created automatically and no order records are written in this release.</p>
            <p class="page-description">No payable records are created, no stock is deducted, no invoice is generated, and no OpenCart/WooCommerce sync is connected.</p>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Launch Cutover Boundary</h2>
        </div>
        <div class="card-body">
            <p>Manual orders after ERP launch are normal ERP transactions, not supplier opening balance entries.</p>
            <p class="page-description">Old manual payable belongs in Supplier Opening Balance planning before launch lock.</p>
        </div>
    </div>
</div>
