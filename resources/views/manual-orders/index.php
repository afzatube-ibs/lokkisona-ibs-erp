<div class="page-header">
    <h1 class="page-title">Manual Orders</h1>
    <p class="page-description">Manual/external reference order create (v0.4.0) when migration 0005 is applied. No channel sync.</p>
</div>

<?php view('partials.flash-messages', ['flashSuccess' => $flashSuccess ?? null, 'flashError' => $flashError ?? null]); ?>
<?php if (!empty($writeServiceReady)): ?>
<div class="card" style="margin-bottom:1.5rem;"><div class="card-body">
<form method="post" action="<?= e(url('/manual-orders/create')) ?>"><?= $csrfField ?? '' ?>
<label>Business source ID *<input type="number" name="business_source_id" required min="1"></label>
<label>External order reference<input name="external_order_reference" style="width:100%"></label>
<label>Customer name<input name="customer_name" style="width:100%"></label>
<label>Product ID<input type="number" name="product_id" min="0"></label>
<label>Quantity<input type="number" name="quantity" value="1" min="1"></label>
<label>Selling price<input type="number" name="selling_price" step="0.01"></label>
<button type="submit">Create manual order (v0.4.0)</button></form>
</div></div>
<?php endif; ?>

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
