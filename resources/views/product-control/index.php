<div class="page-header">
    <h1 class="page-title">Product Control</h1>
    <p class="page-description">Product Control with live read-only product and variant inventory in v0.2.4. Planning foundation content remains below. No OpenCart sync, no stock changes, no product cost changes, and no database writes in this release.</p>
</div>

<h2 class="section-heading" style="margin: 0 0 0.75rem;">Read-Only Product Inventory (v0.2.4)</h2>
<p class="page-description" style="margin-bottom: 1rem;">Live Read Inventory (SELECT only). No database writes, no sync, no stock change, no product cost change, and no migration apply from this page.</p>

<?php view('partials.read-inventory-card', ['readInventory' => $productReadInventory, 'cardTitle' => 'Products']); ?>
<?php view('partials.read-inventory-card', ['readInventory' => $productVariantReadInventory, 'cardTitle' => 'Product Variants']); ?>

<h2 class="section-heading" style="margin: 1.5rem 0 1rem;">Planning Foundation</h2>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Current Supplier Context</h2>
        </div>
        <div class="card-body">
            <dl class="info-list">
                <div class="info-row">
                    <dt>Supplier</dt>
                    <dd><?= e($currentSupplier['name']) ?></dd>
                </div>
                <div class="info-row">
                    <dt>Context</dt>
                    <dd><?= e($currentSupplier['role']) ?></dd>
                </div>
            </dl>
            <p class="page-description"><?= e($currentSupplier['summary']) ?></p>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Product Control Purpose</h2>
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
    <?php foreach ($futureSyncedStructure as $section): ?>
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><?= e($section['title']) ?></h2>
        </div>
        <div class="card-body">
            <p><?= e($section['description']) ?></p>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Supplier Editable Fields</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($editableFields as $field): ?>
                    <li><?= e($field) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Read-Only Platform Fields</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($readOnlyFields as $field): ?>
                    <li><?= e($field) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Business Rules</h2>
        </div>
        <div class="card-body">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Field</th>
                        <th>Rule</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($businessRules as $rule): ?>
                    <tr>
                        <td><?= e($rule['field']) ?></td>
                        <td><?= e($rule['rule']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Cost / Stock History Rules</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($historyRules as $rule): ?>
                    <li><?= e($rule) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Low Stock Warning Rules</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($lowStockRules as $rule): ?>
                    <li><?= e($rule) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Option / Image Reference Rules</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($optionImageRules as $rule): ?>
                    <li><?= e($rule) ?></li>
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
            <h2 class="card-title"><?= e($sharedStockRule['title']) ?></h2>
        </div>
        <div class="card-body">
            <p><?= e($sharedStockRule['summary']) ?></p>
            <ul class="feature-list">
                <?php foreach ($sharedStockRule['points'] as $point): ?>
                    <li><?= e($point) ?></li>
                <?php endforeach; ?>
            </ul>
            <p class="page-description">Manual/external order items must map to internal ERP product/variant for shared supplier stock and cost. See <a href="<?= e(url('/manual-orders')) ?>">Manual Orders planning foundation</a> and <a href="<?= e(url('/sync-preview')) ?>">Sync Preview planning foundation</a> for multi-source stock impact.</p>
        </div>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Planned Product Fields</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($plannedProductFields as $field): ?>
                    <li><?= e($field) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Planned Variant / Option Fields</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($plannedVariantFields as $field): ?>
                    <li><?= e($field) ?></li>
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
            <p class="page-description">Owner, admin, and staff can view the Product Control foundation now. Supplier role does not include product control access yet.</p>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Manual Migration Required</h2>
        </div>
        <div class="card-body">
            <p>No product, variant, cost, or stock history tables are created automatically and no database records are written in this release.</p>
            <p class="page-description">Real product control data requires an owner/admin-reviewed manual migration before activation. No table creation, alteration, or schema repair runs on page load. OpenCart sync is not connected in this release.</p>
        </div>
    </div>
</div>
