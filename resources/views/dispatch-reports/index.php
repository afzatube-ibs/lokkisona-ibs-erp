<div class="page-header">
    <h1 class="page-title">Dispatch Reports</h1>
    <p class="page-description">Dispatch read inventory plus dispatch report create from ready orders (v0.4.2) when migration 0006 is applied.</p>
</div>

<?php view('partials.flash-messages', ['flashSuccess' => $flashSuccess ?? null, 'flashError' => $flashError ?? null]); ?>
<div class="card" style="margin-bottom:1.5rem;"><div class="card-body">
<form method="post" action="<?= e(url('/dispatch-reports/create')) ?>"><?= $csrfField ?? '' ?>
<label>Supplier ID (optional)<input type="number" name="supplier_id" min="0"></label>
<label>Business source ID (optional)<input type="number" name="business_source_id" min="0"></label>
<button type="submit">Create dispatch report from ready_for_dispatch orders (v0.4.2)</button></form>
</div></div>

<h2 class="section-heading" style="margin: 0 0 0.75rem;">Read-Only Dispatch Report Inventory</h2>
<p class="page-description" style="margin-bottom: 1rem;">SELECT only. No database writes. No dispatch batch creation. No dispatch lock. No migration apply from this page.</p>

<?php view('partials.read-inventory-card', ['readInventory' => $readInventory, 'cardTitle' => 'Dispatch Reports']); ?>

<h2 class="section-heading" style="margin: 1.5rem 0 1rem;">Planning Foundation</h2>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Current Dispatch Context</h2>
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
            <h2 class="card-title">Dispatch Report Purpose</h2>
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
            <h2 class="card-title"><?= e($eligibleRule['title']) ?></h2>
        </div>
        <div class="card-body">
            <p><?= e($eligibleRule['summary']) ?></p>
            <ul class="feature-list">
                <?php foreach ($eligibleRule['points'] as $point): ?>
                    <li><?= e($point) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><?= e($singleSupplierRule['title']) ?></h2>
        </div>
        <div class="card-body">
            <p><?= e($singleSupplierRule['summary']) ?></p>
            <ul class="feature-list">
                <?php foreach ($singleSupplierRule['points'] as $point): ?>
                    <li><?= e($point) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><?= e($batchLockRule['title']) ?></h2>
        </div>
        <div class="card-body">
            <p><?= e($batchLockRule['summary']) ?></p>
            <ul class="feature-list">
                <?php foreach ($batchLockRule['points'] as $point): ?>
                    <li><?= e($point) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><?= e($batchReferenceRule['title']) ?></h2>
        </div>
        <div class="card-body">
            <p><?= e($batchReferenceRule['summary']) ?></p>
            <ul class="feature-list">
                <?php foreach ($batchReferenceRule['points'] as $point): ?>
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
            <h2 class="card-title"><?= e($deliveryStopRule['title']) ?></h2>
        </div>
        <div class="card-body">
            <p><?= e($deliveryStopRule['summary']) ?></p>
            <ul class="feature-list">
                <?php foreach ($deliveryStopRule['points'] as $point): ?>
                    <li><?= e($point) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Future Report Output Plan</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($reportOutputPlan as $item): ?>
                    <li><?= e($item) ?></li>
                <?php endforeach; ?>
            </ul>
            <p class="page-description">See <a href="<?= e(url('/invoice-printing')) ?>">Invoice Printing planning foundation</a> for packing slip, dispatch batch report, supplier product summary, and print log rules.</p>
        </div>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Future Performance Rules</h2>
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
            <h2 class="card-title">Launch Cutover Payable Boundary</h2>
        </div>
        <div class="card-body">
            <p>Dispatch payable begins only after the ERP launch cut-off date.</p>
            <p class="page-description">Old supplier payable before launch belongs in Supplier Opening Balance planning, not dispatch batches.</p>
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
            <p class="page-description">Owner and admin can view the Dispatch Report planning foundation now. Staff may view/manage later based on permission. Supplier role should only see supplier-specific dispatch later, not all dispatch reports.</p>
        </div>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Planned Dispatch Report Fields</h2>
        </div>
        <div class="card-body">
            <p class="page-description">Documented only. No dispatch tables are created automatically and no records are written.</p>
            <ul class="feature-list">
                <?php foreach ($plannedReportFields as $field): ?>
                    <li><code><?= e($field) ?></code></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Planned Dispatch Report Item Fields</h2>
        </div>
        <div class="card-body">
            <p class="page-description">Documented only. No dispatch tables are created automatically and no records are written.</p>
            <ul class="feature-list">
                <?php foreach ($plannedItemFields as $field): ?>
                    <li><code><?= e($field) ?></code></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>
