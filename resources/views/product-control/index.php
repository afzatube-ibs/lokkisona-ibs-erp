<div class="page-header">
    <h1 class="page-title">Product Control</h1>
    <p class="page-description">ERP internal supplier product and variant/option setup (v0.4.2.8). Product create, variant/option entry, and cost/stock history when Group B tables are ready.</p>
</div>

<?php view('partials.flash-messages', ['flashSuccess' => $flashSuccess ?? null, 'flashError' => $flashError ?? null]); ?>

<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header"><h2 class="card-title">Product / Variant Setup Notes</h2></div>
    <div class="card-body">
        <ul class="feature-list">
            <li>Product and variant/option entry is ERP internal supplier product setup ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â not OpenCart or WooCommerce sync.</li>
            <li>Vendor stock is dev ERP stock only for now. It does not sync to any sales channel yet.</li>
            <li>Product cost changes do not rewrite cost snapshots on existing orders or dispatch batches.</li>
            <li>Opening balance remains draft/test only until launch cut-off ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â do not approve or finalize real opening balance yet.</li>
            <li>Example: Product <strong>Baby Stroller</strong> ÃƒÂ¢Ã¢â‚¬Â Ã¢â‚¬â„¢ Option <strong>Color</strong> / <strong>Black</strong> ÃƒÂ¢Ã¢â‚¬Â Ã¢â‚¬â„¢ Supplier Model <strong>IBS-STROLLER-BLACK</strong> ÃƒÂ¢Ã¢â‚¬Â Ã¢â‚¬â„¢ Cost <strong>6500</strong> ÃƒÂ¢Ã¢â‚¬Â Ã¢â‚¬â„¢ Stock <strong>100</strong> ÃƒÂ¢Ã¢â‚¬Â Ã¢â‚¬â„¢ Low Warning <strong>5</strong>.</li>
        </ul>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header"><h2 class="card-title">Create Product</h2></div>
        <div class="card-body">
            <?php if (!empty($writeGateProductCreateReady)): ?>
            <form method="post" action="<?= e(url('/product-control/product/create')) ?>">
                <?= $csrfField ?? '' ?>
                <div class="form-grid" style="display: grid; gap: 0.75rem; max-width: 640px;">
                    <label>Product name *<input type="text" name="product_name" required class="form-input" style="width:100%"></label>
                    <label>Supplier model<input type="text" name="supplier_model" class="form-input" style="width:100%" placeholder="e.g. IBS-STROLLER"></label>
                    <label>Product cost<input type="number" name="product_cost" step="0.01" min="0" class="form-input" style="width:100%"></label>
                    <label>Vendor stock<input type="number" name="vendor_stock" min="0" value="0" class="form-input" style="width:100%"></label>
                    <label>Low warning<input type="number" name="low_warning_threshold" min="0" class="form-input" style="width:100%" placeholder="Warning only"></label>
                    <button type="submit" class="btn btn-primary">Create product</button>
                </div>
            </form>
            <?php else: ?>
            <?php view('partials.write-gate-warning', ['writeGateReady' => false, 'writeGate' => $writeGateProductCreate ?? []]); ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h2 class="card-title">Add Variant / Option</h2></div>
        <div class="card-body">
            <?php if (!empty($writeGateVariantFormReady)): ?>
            <form method="post" action="<?= e(url('/product-control/variant/create')) ?>">
                <?= $csrfField ?? '' ?>
                <div class="form-grid" style="display: grid; gap: 0.75rem; max-width: 640px;">
                    <label>Product *
                        <?php if (!empty($productSelectOptions)): ?>
                        <select name="product_id" required class="form-input" style="width:100%">
                            <option value="">ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â Select product ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â</option>
                            <?php foreach ($productSelectOptions as $option): ?>
                            <option value="<?= e((string) $option['id']) ?>"><?= e($option['label']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php else: ?>
                        <input type="number" name="product_id" required min="1" class="form-input" style="width:100%" placeholder="Product ID (create a product first)">
                        <span class="page-description">No products loaded ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â enter Product ID manually or create a product first.</span>
                        <?php endif; ?>
                    </label>
                    <label>Option Name *<input type="text" name="option_name" required class="form-input" style="width:100%" placeholder="e.g. Color"></label>
                    <label>Option Value *<input type="text" name="option_value" required class="form-input" style="width:100%" placeholder="e.g. Black"></label>
                    <label>Supplier Model<input type="text" name="supplier_model" class="form-input" style="width:100%" placeholder="Recommended ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â e.g. IBS-STROLLER-BLACK"></label>
                    <label>Product Cost *<input type="number" name="product_cost" step="0.01" min="0" required class="form-input" style="width:100%"></label>
                    <label>Vendor Stock *<input type="number" name="vendor_stock" min="0" value="0" required class="form-input" style="width:100%"></label>
                    <label>Low Warning<input type="number" name="low_warning_threshold" min="0" class="form-input" style="width:100%" placeholder="Saved on parent product"></label>
                    <label>Status
                        <select name="status" class="form-input" style="width:100%">
                            <option value="active">active</option>
                            <option value="inactive">inactive</option>
                        </select>
                    </label>
                    <button type="submit" class="btn btn-primary">Save variant / option</button>
                </div>
            </form>
            <?php else: ?>
            <?php view('partials.write-gate-warning', ['writeGateReady' => false, 'writeGate' => $writeGateVariantForm ?? []]); ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h2 class="card-title">Cost / Stock History Change</h2></div>
        <div class="card-body">
            <?php if (!empty($writeGateCostStockReady)): ?>
            <form method="post" action="<?= e(url('/product-control/cost-stock')) ?>">
                <?= $csrfField ?? '' ?>
                <div class="form-grid" style="display: grid; gap: 0.75rem; max-width: 640px;">
                    <label>Product ID *<input type="number" name="product_id" required min="1" class="form-input" style="width:100%"></label>
                    <label>Variant ID <input type="number" name="product_variant_id" min="1" class="form-input" style="width:100%" placeholder="Optional — use for variant/option cost-stock change"></label>
                    <label>New cost<input type="number" name="product_cost" step="0.01" min="0" class="form-input" style="width:100%"></label>
                    <label>New stock<input type="number" name="vendor_stock" min="0" class="form-input" style="width:100%"></label>
                    <label>Note<input type="text" name="note" class="form-input" style="width:100%"></label>
                    <button type="submit" class="btn btn-primary">Save cost/stock with history</button>
                </div>
            </form>
            <?php else: ?>
            <?php view('partials.write-gate-warning', ['writeGateReady' => false, 'writeGate' => $writeGateCostStock ?? []]); ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header"><h2 class="card-title">Audit Confirmation: Cost / Stock History Notes (latest 20)</h2></div>
    <div class="card-body">
        <p class="page-description"><?= e($costStockHistoryDisplay['status_message'] ?? 'Cost/stock audit history is not fully ready yet.') ?></p>
        <?php if (!empty($costStockHistoryDisplay['rows'])): ?>
        <table class="data-table" style="width:100%; margin-top: 1rem;">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Variant / Level</th>
                    <th>Cost Old → New</th>
                    <th>Stock Old → New</th>
                    <th>Change Type</th>
                    <th>Note</th>
                    <th>Changed At</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($costStockHistoryDisplay['rows'] as $row): ?>
                <?php
                $oldCost = (string) ($row['old_cost'] ?? '');
                $newCost = (string) ($row['new_cost'] ?? '');
                $oldStock = (string) ($row['old_stock'] ?? '');
                $newStock = (string) ($row['new_stock'] ?? '');
                $costTransition = ($oldCost !== '' || $newCost !== '') ? $oldCost . ' → ' . $newCost : '—';
                $stockTransition = ($oldStock !== '' || $newStock !== '') ? $oldStock . ' → ' . $newStock : '—';
                ?>
                <tr>
                    <td><?= e((string) ($row['product_name'] ?? '')) ?> <code>#<?= e((string) ($row['product_id'] ?? '')) ?></code></td>
                    <td><?= e((string) ($row['variant_label'] ?? 'Product level')) ?></td>
                    <td><?= e($costTransition) ?></td>
                    <td><?= e($stockTransition) ?></td>
                    <td><?= e((string) ($row['change_type'] ?? '')) ?></td>
                    <td><strong class="audit-note"><?= e((string) ($row['note'] ?? '')) ?></strong></td>
                    <td><?= e((string) ($row['created_at'] ?? '')) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php elseif (!empty($costStockHistoryDisplay['cost_table_exists']) || !empty($costStockHistoryDisplay['stock_table_exists'])): ?>
        <p class="page-description" style="margin-top: 1rem;">No audit history rows yet. Save cost/stock with a note using the form above.</p>
        <?php endif; ?>
    </div>
</div>

<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header"><h2 class="card-title">Product Variants / Options (up to 50 rows)</h2></div>
    <div class="card-body">
        <p class="page-description"><?= e($variantDisplay['status_message'] ?? '') ?></p>
        <?php if (!empty($variantDisplay['rows'])): ?>
        <table class="data-table" style="width:100%; margin-top: 1rem;">
            <thead>
                <tr>
                    <th>Variant ID</th>
                    <th>Product</th>
                    <th>Option Name</th>
                    <th>Option Value</th>
                    <th>Supplier Model</th>
                    <th>Cost</th>
                    <th>Vendor Stock</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($variantDisplay['rows'] as $row): ?>
                <tr>
                    <td><?= e((string) $row['product_variant_id']) ?></td>
                    <td><?= e($row['product_name']) ?> <code>#<?= e((string) $row['product_id']) ?></code></td>
                    <td><?= e((string) $row['option_name']) ?></td>
                    <td><?= e((string) $row['option_value']) ?></td>
                    <td><?= e((string) $row['supplier_model']) ?></td>
                    <td><?= e((string) $row['product_cost']) ?></td>
                    <td><?= e((string) $row['vendor_stock']) ?></td>
                    <td><span class="badge badge-ok"><?= e((string) $row['status']) ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php elseif (!empty($variantDisplay['table_exists'])): ?>
        <p class="page-description" style="margin-top: 1rem;">No variant/option rows yet. Add a variant using the form above.</p>
        <?php endif; ?>
    </div>
</div>


<h2 class="section-heading" style="margin: 0 0 0.75rem;">Read-Only Product Inventory</h2>
<p class="page-description" style="margin-bottom: 1rem;">Live Read Inventory (SELECT only). No sync, no migration apply from this page.</p>

<?php view('partials.read-inventory-card', ['readInventory' => $productReadInventory, 'cardTitle' => 'Products']); ?>
<?php view('partials.read-inventory-card', ['readInventory' => $productVariantReadInventory, 'cardTitle' => 'Product Variants (raw read inventory)']); ?>
<?php view('partials.read-inventory-card', ['readInventory' => $productCostHistoryReadInventory, 'cardTitle' => 'Product Cost History (raw read inventory)']); ?>
<?php view('partials.read-inventory-card', ['readInventory' => $productStockHistoryReadInventory, 'cardTitle' => 'Product Stock History (raw read inventory)']); ?>

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
