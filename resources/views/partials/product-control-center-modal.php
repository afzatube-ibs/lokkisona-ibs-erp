<?php
$costLabel = !empty($isSupplierView) ? 'Sale' : 'Cost';
$supplierCostLabel = !empty($isSupplierView) ? 'Supplier Sale' : 'Supplier Cost';
$defaultSupplierId = (int) (config('auth.supplier_id', 1));
?>
<div class="modal-overlay" id="productControlCenterModal" hidden aria-hidden="true">
    <div class="modal-panel modal-panel-product-control" role="dialog" aria-labelledby="pccModalTitle" aria-modal="true">
        <div class="pcc-modal-header">
            <div>
                <h2 class="pcc-modal-title" id="pccModalTitle">Product Control Center</h2>
                <p class="pcc-modal-subtitle" id="pccModalSubtitle">Controlled product workspace</p>
                <div class="pcc-badge-row" id="pccCompletenessBadges"></div>
            </div>
            <button type="button" class="modal-close" data-modal-close="productControlCenterModal" aria-label="Close product workspace">&times;</button>
        </div>

        <p class="pcc-modal-error page-description" id="pccModalError" hidden role="alert"></p>
        <p class="pcc-modal-loading page-description" id="pccModalLoading" hidden>Loading product workspace…</p>

        <div class="pcc-tabs" role="tablist">
            <button type="button" class="pcc-tab is-active" data-pcc-tab="details" role="tab" aria-selected="true">Product Details</button>
            <button type="button" class="pcc-tab" data-pcc-tab="history" role="tab" aria-selected="false"><?= e($costLabel) ?> / Stock History</button>
        </div>

        <form method="post" action="<?= e(url('/product-control/workspace/save')) ?>" id="productControlCenterForm">
            <?= $csrfField ?? '' ?>
            <input type="hidden" name="product_id" id="pccProductId" value="">
            <?php if (!empty($isSupplierView) && !empty($boundSupplierId)): ?>
            <input type="hidden" name="supplier_id" value="<?= e((string) $boundSupplierId) ?>">
            <?php else: ?>
            <input type="hidden" name="supplier_id" id="pccSupplierId" value="<?= e((string) $defaultSupplierId) ?>">
            <?php endif; ?>
            <input type="hidden" name="business_source_id" id="pccBusinessSourceId" value="<?= e((string) ($defaultBusinessSourceId ?? 1)) ?>">
            <input type="hidden" name="supplier_product_category" id="pccCategory" value="">
            <input type="hidden" name="variants" id="pccVariantsJson" value="">

            <div class="pcc-tab-panel is-active" data-pcc-panel="details">
                <div class="pcc-modal-hero-split">
                    <div class="pcc-main-product-card">
                        <div class="pcc-main-product-thumb" id="pccMainImageWrap">
                            <div class="pcc-image-placeholder" id="pccImagePlaceholder">—</div>
                            <img src="" alt="" class="pcc-product-image" id="pccProductImage" hidden>
                        </div>
                        <dl class="pcc-oc-facts-grid">
                            <div class="pcc-oc-fact"><dt>OC ID</dt><dd id="pccOcProductId">—</dd></div>
                            <div class="pcc-oc-fact"><dt>OC Model</dt><dd id="pccOcModel">—</dd></div>
                            <div class="pcc-oc-fact"><dt>OC Stock</dt><dd id="pccOcStock">—</dd></div>
                            <div class="pcc-oc-fact"><dt>OC Price</dt><dd id="pccOcPrice">Not stored in ERP</dd></div>
                            <div class="pcc-oc-fact pcc-oc-fact-wide"><dt>Last synced</dt><dd id="pccLastSynced">—</dd></div>
                        </dl>
                    </div>

                    <div class="pcc-vendor-mapping-card" id="pccVendorMappingCard">
                        <div class="pcc-vendor-mapping-header">
                            <h3 class="pcc-vendor-mapping-title">Iqbal &amp; Brothers (IBS)</h3>
                            <?php if (!empty($canManage) && !empty($writeGateProductEditReady)): ?>
                            <button type="button" class="btn btn-secondary btn-sm" id="pccSupplierEditBtn">Edit</button>
                            <?php endif; ?>
                        </div>

                        <div class="pcc-supplier-view" id="pccSupplierView">
                            <div class="pcc-supplier-field-row">
                                <span class="pcc-field-label">Supplier Product Name</span>
                                <span class="pcc-field-chip" id="pccSupplierProductNameView">—</span>
                            </div>
                            <div class="pcc-supplier-field-row">
                                <span class="pcc-field-label">Main Vendor Model</span>
                                <span class="pcc-field-chip" id="pccSupplierModelView">—</span>
                            </div>
                            <div class="pcc-supplier-field-row">
                                <span class="pcc-field-label">Low Stock</span>
                                <span class="pcc-field-chip" id="pccLowWarningView">—</span>
                            </div>
                            <div class="pcc-supplier-field-row pcc-simple-supplier-view" id="pccSimpleCostViewRow" hidden>
                                <span class="pcc-field-label"><?= e($supplierCostLabel) ?></span>
                                <span class="pcc-field-chip" id="pccProductCostView">—</span>
                            </div>
                            <div class="pcc-supplier-field-row pcc-simple-supplier-view" id="pccSimpleStockViewRow" hidden>
                                <span class="pcc-field-label">Vendor stock</span>
                                <span class="pcc-field-chip" id="pccProductVendorStockView">—</span>
                            </div>
                        </div>

                        <div class="pcc-supplier-edit" id="pccSupplierEdit" hidden>
                            <div class="pcc-supplier-field-row">
                                <span class="pcc-field-label">Supplier Product Name</span>
                                <span class="pcc-field-chip pcc-field-chip-muted" id="pccSupplierProductNameEdit">—</span>
                            </div>
                            <label class="pcc-field pcc-field-compact">Main Vendor Model
                                <input type="text" name="supplier_model" id="pccSupplierModel" class="form-input" placeholder="Vendor model">
                            </label>
                            <label class="pcc-field pcc-field-compact">Low Stock
                                <input type="number" name="low_warning_threshold" id="pccLowWarning" min="0" class="form-input">
                            </label>
                            <label class="pcc-field pcc-field-compact pcc-simple-supplier-edit" id="pccSimpleCostEditWrap"> <?= e($supplierCostLabel) ?>
                                <input type="number" name="product_cost" id="pccProductCost" step="0.01" min="0" class="form-input">
                            </label>
                            <label class="pcc-field pcc-field-compact pcc-simple-supplier-edit" id="pccSimpleStockEditWrap">Vendor stock
                                <input type="number" name="vendor_stock" id="pccProductVendorStock" min="0" class="form-input">
                            </label>
                            <label class="pcc-field pcc-field-compact">Status
                                <select name="status" id="pccStatus" class="form-input">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </label>
                            <?php if (!empty($writeGateSupplierNoteReady)): ?>
                            <label class="pcc-field pcc-field-compact">Supplier note
                                <textarea name="supplier_note" id="pccSupplierNote" class="form-input" rows="2" placeholder="Internal supplier note (ERP only)"></textarea>
                            </label>
                            <?php elseif (!empty($writeGateSupplierNote)): ?>
                            <p class="page-description pcc-field-hint"><?= e($writeGateSupplierNote['message'] ?? 'Supplier note unavailable until migration 0012 is applied manually.') ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="pcc-variant-section" id="pccVariantSection" hidden>
                    <p class="page-description pcc-no-options-notice" id="pccNoOptionsNotice" hidden>No options synced yet. Refresh Products to pull option lines from Dispatch Location.</p>
                    <div class="table-scroll pcc-variant-scroll">
                        <table class="data-table pcc-variant-lines-table pcc-table-tight" id="pccVariantLinesTable">
                            <thead>
                                <tr>
                                    <th class="pcc-vcol-line">Line</th>
                                    <th class="pcc-vcol-image">Image</th>
                                    <th class="pcc-vcol-model">Model</th>
                                    <th class="pcc-vcol-vendor">Vendor Model</th>
                                    <th class="pcc-vcol-price">OC Price</th>
                                    <th class="pcc-vcol-cost">Average Cost</th>
                                    <th class="pcc-vcol-owner">Owner Stock</th>
                                    <th class="pcc-vcol-vstock">Vendor Stock</th>
                                    <th class="pcc-vcol-warn">Warning</th>
                                    <th class="pcc-vcol-health">Health</th>
                                </tr>
                            </thead>
                            <tbody id="pccVariantLinesBody"></tbody>
                        </table>
                    </div>
                </div>

                <?php if (!empty($canManage) && !empty($writeGateProductEditReady)): ?>
                <div class="pcc-save-bar pcc-save-bar-v874">
                    <button type="submit" class="btn btn-primary" id="pccSaveBtn">Save All Changes</button>
                    <button type="button" class="btn btn-secondary" data-modal-close="productControlCenterModal">Close</button>
                </div>
                <?php else: ?>
                <div class="pcc-save-bar pcc-save-bar-v874">
                    <button type="button" class="btn btn-secondary" data-modal-close="productControlCenterModal">Close</button>
                </div>
                <?php endif; ?>
            </div>

            <div class="pcc-tab-panel" data-pcc-panel="history" hidden>
                <div class="table-scroll">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Variant / Level</th>
                                <th><?= e($costLabel) ?> Old → New</th>
                                <th>Stock Old → New</th>
                                <th>Note</th>
                                <th>Changed At</th>
                            </tr>
                        </thead>
                        <tbody id="pccHistoryRows">
                            <tr><td colspan="5" class="page-description">Select a product to view history.</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="pcc-save-bar pcc-save-bar-v874">
                    <button type="button" class="btn btn-secondary" data-modal-close="productControlCenterModal">Close</button>
                </div>
            </div>
        </form>
    </div>
</div>
