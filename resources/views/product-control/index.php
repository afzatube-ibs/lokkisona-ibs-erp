<div class="page-header page-header-compact">
    <h1 class="page-title">Product Control</h1>
    <p class="ops-page-subtitle"><?= !empty($isSupplierView) ? 'Your product catalog, sale amounts, and stock — supplier-owned data, not default channel sync.' : 'ERP internal supplier product and variant/option setup — v' . e($appVersion) . ' — ' . e($appReleaseLabel ?? '') . '. Product create, variant/option entry, and cost/stock history when Group B tables are ready.' ?></p>
</div>

<?php view('partials.flash-messages', ['flashSuccess' => $flashSuccess ?? null, 'flashError' => $flashError ?? null]); ?>

<div class="card mb-15">
    <div class="card-header"><h2 class="card-title">Product / Variant Setup Notes</h2></div>
    <div class="card-body">
        <ul class="feature-list">
            <li>ERP supplier catalog is manual today. Map each row to OpenCart with <strong>source product ID</strong> so order import resolves cost correctly.</li>
            <li>Only OpenCart products marked <strong>From Warehouse = Yes</strong> (Dispatch Location) belong in this catalog. Shop-only products never sync here.</li>
            <li>OpenCart sync is <strong>order-only</strong> (Test Sync + status mapping). Optional warehouse product pull runs from Sync Preview when <code>product_api_route</code> is configured.</li>
            <li>Vendor stock is ERP supplier stock — not pushed back to sales channels yet.</li>
            <li><?= !empty($isSupplierView) ? 'Sale amount changes do not rewrite locked sale snapshots on existing orders or dispatch batches.' : 'Product cost changes do not rewrite cost snapshots on existing orders or dispatch batches.' ?></li>
            <li>Example: Product <strong>Baby Stroller</strong> → OC ID <strong>501</strong> → Supplier Model <strong>IBS-STROLLER</strong> → <?= !empty($isSupplierView) ? 'Sale' : 'Cost' ?> <strong>6500</strong> → Stock <strong>100</strong>.</li>
        </ul>
    </div>
</div>

<div class="card mb-15">
    <div class="card-header"><h2 class="card-title">Products (supplier catalog)</h2></div>
    <div class="card-body">
        <p class="page-description"><?= e($productDisplay['status_message'] ?? 'Product catalog unavailable.') ?></p>
        <?php if (!empty($productDisplay['rows'])): ?>
        <div class="table-scroll">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Supplier model</th>
                    <th>Category</th>
                    <th><?= !empty($isSupplierView) ? 'Sale' : 'Cost' ?></th>
                    <th>Vendor stock</th>
                    <th>OC source ID</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($productDisplay['rows'] as $row): ?>
                <tr>
                    <td><code>#<?= e((string) $row['product_id']) ?></code></td>
                    <td><?= e($row['product_name']) ?></td>
                    <td><?= e($row['supplier_model'] !== '' ? $row['supplier_model'] : '—') ?></td>
                    <td><?= e($row['supplier_product_category'] !== '' ? $row['supplier_product_category'] : '—') ?></td>
                    <td><?= e((string) ($row['product_cost'] !== '' && $row['product_cost'] !== null ? $row['product_cost'] : '—')) ?></td>
                    <td><?= e((string) $row['vendor_stock']) ?></td>
                    <td><?= e($row['source_product_id'] !== '' ? $row['source_product_id'] : '—') ?></td>
                    <td><span class="badge badge-ok"><?= e($row['status']) ?></span></td>
                    <td>
                        <?php if (!empty($writeGateProductEditReady)): ?>
                        <details class="product-edit-details">
                            <summary class="btn btn-secondary btn-sm">Edit</summary>
                            <form method="post" action="<?= e(url('/product-control/product/edit')) ?>" class="form-grid product-edit-form" style="margin-top:0.75rem;">
                                <?= $csrfField ?? '' ?>
                                <input type="hidden" name="product_id" value="<?= e((string) $row['product_id']) ?>">
                                <?php if (!empty($isSupplierView) && !empty($boundSupplierId)): ?>
                                <input type="hidden" name="supplier_id" value="<?= e((string) $boundSupplierId) ?>">
                                <?php endif; ?>
                                <?php if (empty($isSupplierView)): ?>
                                <label>Business source ID<input type="number" name="business_source_id" min="1" class="form-input" value="<?= e((string) ($row['business_source_id'] !== '' && $row['business_source_id'] !== null ? $row['business_source_id'] : ($defaultBusinessSourceId ?? 1))) ?>"></label>
                                <?php else: ?>
                                <input type="hidden" name="business_source_id" value="<?= e((string) ($row['business_source_id'] !== '' && $row['business_source_id'] !== null ? $row['business_source_id'] : ($defaultBusinessSourceId ?? 1))) ?>">
                                <?php endif; ?>
                                <label>Product name *<input type="text" name="product_name" required class="form-input" value="<?= e($row['product_name']) ?>"></label>
                                <label>Supplier model<input type="text" name="supplier_model" class="form-input" value="<?= e($row['supplier_model']) ?>"></label>
                                <label>Supplier category<input type="text" name="supplier_product_category" class="form-input" value="<?= e($row['supplier_product_category']) ?>"></label>
                                <label>OpenCart source product ID<input type="text" name="source_product_id" class="form-input" value="<?= e($row['source_product_id']) ?>" placeholder="OC product_id for order mapping"></label>
                                <label><?= !empty($isSupplierView) ? 'Sale amount' : 'Product cost' ?><input type="number" name="product_cost" step="0.01" min="0" class="form-input" value="<?= e((string) ($row['product_cost'] !== '' && $row['product_cost'] !== null ? $row['product_cost'] : '')) ?>"></label>
                                <label>Vendor stock<input type="number" name="vendor_stock" min="0" class="form-input" value="<?= e((string) $row['vendor_stock']) ?>"></label>
                                <label>Low warning<input type="number" name="low_warning_threshold" min="0" class="form-input" value="<?= e((string) ($row['low_warning_threshold'] !== '' && $row['low_warning_threshold'] !== null ? $row['low_warning_threshold'] : '')) ?>"></label>
                                <label>Status
                                    <select name="status" class="form-input">
                                        <option value="active"<?= ($row['status'] ?? '') === 'active' ? ' selected' : '' ?>>active</option>
                                        <option value="inactive"<?= ($row['status'] ?? '') === 'inactive' ? ' selected' : '' ?>>inactive</option>
                                    </select>
                                </label>
                                <?php if ($row['source_product_id'] !== '' || $row['source_model'] !== '' || $row['last_synced_at'] !== ''): ?>
                                <div class="form-grid-span-all platform-readonly-fields">
                                    <p class="page-description"><strong>Platform read-only</strong> (updated by warehouse pull or future sync — not editable here)</p>
                                    <?php if ($row['source_model'] !== ''): ?><span>OC model: <code><?= e($row['source_model']) ?></code></span><?php endif; ?>
                                    <?php if ($row['source_stock'] !== '' && $row['source_stock'] !== null): ?><span>OC stock: <code><?= e((string) $row['source_stock']) ?></code></span><?php endif; ?>
                                    <?php if ($row['last_synced_at'] !== ''): ?><span>Last synced: <?= e($row['last_synced_at']) ?></span><?php endif; ?>
                                </div>
                                <?php endif; ?>
                                <button type="submit" class="btn btn-primary">Save product</button>
                            </form>
                        </details>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php elseif (!empty($productDisplay['table_exists'])): ?>
        <p class="page-description mt-1">No products yet. Create one below or run warehouse product pull from Sync Preview when configured.</p>
        <?php endif; ?>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header"><h2 class="card-title">Create Product</h2></div>
        <div class="card-body">
            <?php if (!empty($writeGateProductCreateReady)): ?>
            <form method="post" action="<?= e(url('/product-control/product/create')) ?>">
                <?= $csrfField ?? '' ?>
                <?php if (!empty($isSupplierView) && !empty($boundSupplierId)): ?>
                <input type="hidden" name="supplier_id" value="<?= e((string) $boundSupplierId) ?>">
                <?php endif; ?>
        <div class="form-grid">
            <label>Product name *<input type="text" name="product_name" required class="form-input"></label>
            <label>Supplier model<input type="text" name="supplier_model" class="form-input" placeholder="e.g. IBS-STROLLER"></label>
            <label>Supplier category<input type="text" name="supplier_product_category" class="form-input" placeholder="ERP reporting category (not OpenCart)"></label>
            <label>OpenCart source product ID<input type="text" name="source_product_id" class="form-input" placeholder="OC product_id — maps order line items"></label>
            <?php if (empty($isSupplierView)): ?>
            <label>Business source ID<input type="number" name="business_source_id" min="1" class="form-input" value="<?= e((string) ($defaultBusinessSourceId ?? 1)) ?>"></label>
            <?php else: ?>
            <input type="hidden" name="business_source_id" value="<?= e((string) ($defaultBusinessSourceId ?? 1)) ?>">
            <?php endif; ?>
            <label><?= !empty($isSupplierView) ? 'Sale amount' : 'Product cost' ?><input type="number" name="product_cost" step="0.01" min="0" class="form-input"></label>
            <label>Vendor stock<input type="number" name="vendor_stock" min="0" value="0" class="form-input"></label>
            <label>Low warning<input type="number" name="low_warning_threshold" min="0" class="form-input" placeholder="Warning only"></label>
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
                <div class="form-grid">
                    <label>Product *
                        <?php if (!empty($productSelectOptions)): ?>
                        <select name="product_id" required class="form-input">
                            <option value="">— Select product —</option>
                            <?php foreach ($productSelectOptions as $option): ?>
                            <option value="<?= e((string) $option['id']) ?>"><?= e($option['label']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php else: ?>
                        <input type="number" name="product_id" required min="1" class="form-input" placeholder="Product ID (create a product first)">
                        <span class="page-description">No products loaded — enter Product ID manually or create a product first.</span>
                        <?php endif; ?>
                    </label>
                    <label>Option Name *<input type="text" name="option_name" required class="form-input" placeholder="e.g. Color"></label>
                    <label>Option Value *<input type="text" name="option_value" required class="form-input" placeholder="e.g. Black"></label>
                    <label>Supplier Model<input type="text" name="supplier_model" class="form-input" placeholder="Recommended — e.g. IBS-STROLLER-BLACK"></label>
                    <label><?= !empty($isSupplierView) ? 'Sale Amount *' : 'Product Cost *' ?><input type="number" name="product_cost" step="0.01" min="0" required class="form-input"></label>
                    <label>Vendor Stock *<input type="number" name="vendor_stock" min="0" value="0" required class="form-input"></label>
                    <label>Low Warning<input type="number" name="low_warning_threshold" min="0" class="form-input" placeholder="Saved on parent product"></label>
                    <label>Status
                        <select name="status" class="form-input">
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
        <div class="card-header"><h2 class="card-title"><?= !empty($isSupplierView) ? 'Sale Amount / Stock History Change' : 'Cost / Stock History Change' ?></h2></div>
        <div class="card-body">
            <?php if (!empty($writeGateCostStockReady)): ?>
            <form method="post" action="<?= e(url('/product-control/cost-stock')) ?>">
                <?= $csrfField ?? '' ?>
                <div class="form-grid">
                    <label>Product ID *<input type="number" name="product_id" required min="1" class="form-input"></label>
                    <label>Variant ID <input type="number" name="product_variant_id" min="1" class="form-input" placeholder="Optional — use for variant/option cost-stock change"></label>
                    <label><?= !empty($isSupplierView) ? 'New sale amount' : 'New cost' ?><input type="number" name="product_cost" step="0.01" min="0" class="form-input"></label>
                    <label>New stock<input type="number" name="vendor_stock" min="0" class="form-input"></label>
                    <label>Note<input type="text" name="note" class="form-input"></label>
                    <button type="submit" class="btn btn-primary">Save cost/stock with history</button>
                </div>
            </form>
            <?php else: ?>
            <?php view('partials.write-gate-warning', ['writeGateReady' => false, 'writeGate' => $writeGateCostStock ?? []]); ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card mb-15">
    <div class="card-header"><h2 class="card-title">Audit Confirmation: <?= !empty($isSupplierView) ? 'Sale / Stock' : 'Cost / Stock' ?> History Notes (latest 20)</h2></div>
    <div class="card-body">
        <p class="page-description"><?= e($costStockHistoryDisplay['status_message'] ?? 'Cost/stock audit history is not fully ready yet.') ?></p>
        <?php if (!empty($costStockHistoryDisplay['rows'])): ?>
        <div class="table-scroll">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Variant / Level</th>
                    <th><?= !empty($isSupplierView) ? 'Sale Old → New' : 'Cost Old → New' ?></th>
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
        </div>
        <?php elseif (!empty($costStockHistoryDisplay['cost_table_exists']) || !empty($costStockHistoryDisplay['stock_table_exists'])): ?>
        <p class="page-description mt-1">No audit history rows yet. Save cost/stock with a note using the form above.</p>
        <?php endif; ?>
    </div>
</div>

<div class="card mb-15">
    <div class="card-header"><h2 class="card-title">Product Variants / Options (up to 50 rows)</h2></div>
    <div class="card-body">
        <p class="page-description"><?= e($variantDisplay['status_message'] ?? '') ?></p>
        <?php if (!empty($variantDisplay['rows'])): ?>
        <div class="table-scroll">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Variant ID</th>
                    <th>Product</th>
                    <th>Option Name</th>
                    <th>Option Value</th>
                    <th>Supplier Model</th>
                    <th>Category</th>
                    <th><?= !empty($isSupplierView) ? 'Sale' : 'Cost' ?></th>
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
                    <td><?= e((string) ($row['supplier_product_category'] ?? '—')) ?></td>
                    <td><?= e((string) $row['product_cost']) ?></td>
                    <td><?= e((string) $row['vendor_stock']) ?></td>
                    <td><span class="badge badge-ok"><?= e((string) $row['status']) ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php elseif (!empty($variantDisplay['table_exists'])): ?>
        <p class="page-description mt-1">No variant/option rows yet. Add a variant using the form above.</p>
        <?php endif; ?>
    </div>
</div>


<?php if (empty($isSupplierView)): ?>
<details class="planning-collapsible">
    <summary class="planning-collapsible-summary">Read-Only Product Inventory (developer reference)</summary>
    <div class="planning-collapsible-body">
        <p class="page-description mb-1">Live Read Inventory (SELECT only). No sync, no migration apply from this page.</p>
        <?php view('partials.read-inventory-card', ['readInventory' => $productReadInventory, 'cardTitle' => 'Products']); ?>
        <?php view('partials.read-inventory-card', ['readInventory' => $productVariantReadInventory, 'cardTitle' => 'Product Variants (raw read inventory)']); ?>
        <?php view('partials.read-inventory-card', ['readInventory' => $productCostHistoryReadInventory, 'cardTitle' => 'Product Cost History (raw read inventory)']); ?>
        <?php view('partials.read-inventory-card', ['readInventory' => $productStockHistoryReadInventory, 'cardTitle' => 'Product Stock History (raw read inventory)']); ?>
    </div>
</details>

<details class="planning-collapsible">
    <summary class="planning-collapsible-summary">Planning Foundation (reference)</summary>
    <div class="planning-collapsible-body">

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
            <div class="table-scroll">
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
            <p class="page-description">Owner, admin, and staff see the full catalog. Supplier role (v1.3.0+) can view and edit their own supplier-scoped products.</p>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Manual Migration Required</h2>
        </div>
        <div class="card-body">
            <p>Apply migrations 0003 and 0011 manually before production catalog use. No schema repair runs on page load.</p>
            <p class="page-description">OpenCart order sync uses the existing Test Sync path. Product catalog mapping uses <code>source_product_id</code> on <code>ibs_products</code>. Optional warehouse product pull requires <code>product_api_route</code> in config/opencart.php.</p>
        </div>
    </div>
</div>

    </div>
</details>
<?php endif; ?>
