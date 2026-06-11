<?php
$costLabel = !empty($isSupplierView) ? 'Sale' : 'Cost';
$rateLabel = !empty($isSupplierView) ? 'Sale' : 'Rate';
$adjustPriceLabel = !empty($isSupplierView) ? 'Adjust Sale' : 'Adjust Price';
$adjustQuantityLabel = 'Adjust Quantity';
$defaultSupplierId = (int) (config('auth.supplier_id', 1));
$categoryOptions = $categoryOptions ?? [];
?>
<div class="modal-overlay" id="productControlCenterModal" hidden aria-hidden="true">
    <div class="modal-panel modal-panel-product-control pcc-v202-panel" role="dialog" aria-labelledby="pccModalTitle" aria-modal="true">
        <div class="pcc-v202-header">
            <h2 class="pcc-modal-title" id="pccModalTitle">Product Control Center</h2>
            <button type="button" class="modal-close" data-modal-close="productControlCenterModal" aria-label="Close product workspace">&times;</button>
        </div>

        <p class="pcc-modal-error page-description" id="pccModalError" hidden role="alert"></p>
        <p class="pcc-modal-loading page-description" id="pccModalLoading" hidden>Loading product workspace…</p>

        <div class="pcc-tabs pcc-v202-tabs" role="tablist">
            <button type="button" class="pcc-tab is-active" data-pcc-tab="details" role="tab" aria-selected="true">Product Details</button>
            <button type="button" class="pcc-tab" data-pcc-tab="history" role="tab" aria-selected="false"><?= e($costLabel) ?> / Stock History</button>
        </div>

        <form method="post" action="<?= e(url('/product-control/workspace/save')) ?>" id="productControlCenterForm" class="pcc-v202-form">
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
            <input type="hidden" name="cost_meta" id="pccCostMeta" value="">
            <input type="hidden" name="stock_meta" id="pccStockMeta" value="">

            <div class="pcc-tab-panel is-active pcc-v202-tab-panel" data-pcc-panel="details">
                <div class="pcc-v202-workspace">
                    <div class="pcc-v202-top-split">
                        <section class="pcc-v202-snapshot" aria-label="Product information">
                            <div class="pcc-v202-snapshot-image" id="pccMainImageWrap">
                                <div class="pcc-image-placeholder-card pcc-v202-img-placeholder" id="pccImagePlaceholder"><span>No image</span></div>
                                <img src="" alt="" class="pcc-product-image pcc-v202-product-image" id="pccProductImage" hidden>
                            </div>
                            <div class="pcc-v202-snapshot-body">
                                <h3 class="pcc-v202-section-title">Product Information</h3>
                                <dl class="pcc-v202-facts">
                                    <div class="pcc-v202-fact pcc-v202-fact-full"><dt>Product Name</dt><dd id="pccProductNameMuted">—</dd></div>
                                    <div class="pcc-v202-fact"><dt>Product ID</dt><dd id="pccProductIdDisplay">—</dd></div>
                                    <div class="pcc-v202-fact"><dt>Main Model</dt><dd id="pccMainModel">—</dd></div>
                                    <div class="pcc-v202-fact"><dt>Type</dt><dd id="pccProductType">—</dd></div>
                                    <div class="pcc-v202-fact"><dt>Live Stock</dt><dd id="pccTotalStock">—</dd></div>
                                    <div class="pcc-v202-fact"><dt>Variants</dt><dd id="pccTotalVariants">—</dd></div>
                                    <div class="pcc-v202-fact"><dt>Last Sync</dt><dd id="pccLastSynced">—</dd></div>
                                </dl>
                            </div>
                        </section>

                        <section class="pcc-v202-supplier" id="pccVendorMappingCard" aria-label="IBS Details">
                            <h3 class="pcc-v202-section-title">IBS Details</h3>
                            <p class="pcc-v202-supplier-name">Iqbal &amp; Brothers (IBS)</p>
                            <div class="pcc-v202-supplier-fields" id="pccSupplierFields">
                                <div class="pcc-v202-field-row" data-supplier-field="ibs_category">
                                    <span class="pcc-v202-field-label">IBS Category</span>
                                    <span class="pcc-v202-field-value pcc-dblclick-edit pcc-category-display" data-field="ibs_category" data-input="pccCategory" tabindex="0" id="pccIbsCategoryDisplay" title="Double-click to edit">—</span>
                                </div>
                                <div class="pcc-v202-field-row" data-supplier-field="supplier_model">
                                    <span class="pcc-v202-field-label">Main IBS Model</span>
                                    <span class="pcc-v202-field-value pcc-dblclick-edit" data-field="supplier_model" data-input="pccSupplierModel" tabindex="0">—</span>
                                    <input type="hidden" name="supplier_model" id="pccSupplierModel" value="">
                                </div>
                                <div class="pcc-v202-field-row" data-supplier-field="low_warning">
                                    <span class="pcc-v202-field-label">Low Warning</span>
                                    <span class="pcc-v202-field-value pcc-dblclick-edit" data-field="low_warning" data-input="pccLowWarning" tabindex="0">—</span>
                                    <input type="hidden" name="low_warning_threshold" id="pccLowWarning" value="">
                                </div>
                                <div class="pcc-v202-field-row pcc-simple-supplier-field" data-supplier-field="product_cost">
                                    <span class="pcc-v202-field-label"><?= e($rateLabel) ?></span>
                                    <span class="pcc-v202-field-value pcc-dblclick-edit" data-field="product_cost" data-input="pccProductCost" tabindex="0">—</span>
                                    <input type="hidden" name="product_cost" id="pccProductCost" value="">
                                </div>
                                <div class="pcc-v202-field-row pcc-simple-supplier-field" data-supplier-field="vendor_stock">
                                    <span class="pcc-v202-field-label">IBS Stock</span>
                                    <span class="pcc-v202-field-value pcc-dblclick-edit" data-field="vendor_stock" data-input="pccProductVendorStock" tabindex="0">—</span>
                                    <input type="hidden" name="vendor_stock" id="pccProductVendorStock" value="">
                                </div>
                                <div class="pcc-v202-adjust-pair pcc-simple-supplier-field" id="pccSimpleAdjustPair">
                                    <button type="button" class="pcc-v202-adjust-trigger" data-adjust-scope="product" data-adjust-mode="price"><?= e($adjustPriceLabel) ?></button>
                                    <button type="button" class="pcc-v202-adjust-trigger" data-adjust-scope="product" data-adjust-mode="quantity"><?= e($adjustQuantityLabel) ?></button>
                                </div>
                                <div class="pcc-v202-field-row" data-supplier-field="status">
                                    <span class="pcc-v202-field-label">Status</span>
                                    <span class="pcc-v202-field-value pcc-status-readonly" id="pccStatusDisplay">—</span>
                                    <input type="hidden" name="status" id="pccStatus" value="active">
                                </div>
                            </div>
                            <p class="page-description pcc-simple-no-options-hint pcc-simple-supplier-field" id="pccSimpleNoOptionsHint" hidden>No options synced yet. Refresh Products to pull option lines.</p>
                            <?php if (!empty($writeGateSupplierNoteReady)): ?>
                            <label class="pcc-v202-note-field">
                                <textarea name="supplier_note" id="pccSupplierNote" class="form-input form-input-compact" rows="1" placeholder="Internal note (ERP only)"></textarea>
                            </label>
                            <?php endif; ?>
                        </section>
                    </div>

                    <section class="pcc-v202-options" id="pccVariantSection" hidden>
                        <div class="pcc-v202-options-head">
                            <h3 class="pcc-v202-options-title">Option Rows</h3>
                            <span class="pcc-v202-options-hint">Double-click cell to edit</span>
                        </div>
                        <p class="page-description pcc-no-options-notice" id="pccNoOptionsNotice" hidden>No options synced yet. Refresh Products to pull option lines.</p>
                        <div class="pcc-v202-table-wrap">
                            <table class="data-table pcc-variant-lines-table pcc-v202-table" id="pccVariantLinesTable">
                                <thead>
                                    <tr>
                                        <th class="pcc-vcol-image">Image</th>
                                        <th class="pcc-vcol-line">Option Value</th>
                                        <th class="pcc-vcol-model">Model</th>
                                        <th class="pcc-vcol-vendor">IBS Model</th>
                                        <th class="pcc-vcol-cost"><?= e($rateLabel) ?></th>
                                        <th class="pcc-vcol-stock">Live Stock</th>
                                        <th class="pcc-vcol-vstock">IBS Stock</th>
                                        <th class="pcc-vcol-health">Health</th>
                                        <th class="pcc-vcol-history">History</th>
                                    </tr>
                                </thead>
                                <tbody id="pccVariantLinesBody"></tbody>
                            </table>
                        </div>
                    </section>
                </div>

                <?php if (!empty($canManage) && !empty($writeGateProductEditReady)): ?>
                <div class="pcc-v202-footer">
                    <button type="button" class="btn btn-secondary btn-sm" id="pccCancelBtn" hidden>Cancel Changes</button>
                    <button type="submit" class="btn btn-primary" id="pccSaveBtn" disabled>Save All Changes</button>
                    <button type="button" class="btn btn-secondary btn-sm" data-modal-close="productControlCenterModal">Close</button>
                </div>
                <?php else: ?>
                <div class="pcc-v202-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-modal-close="productControlCenterModal">Close</button>
                </div>
                <?php endif; ?>
            </div>

            <div class="pcc-tab-panel pcc-v202-tab-panel" data-pcc-panel="history" hidden>
                <div class="pcc-v202-history-wrap">
                    <table class="data-table pcc-history-table">
                        <thead>
                            <tr>
                                <th>Date / Time</th>
                                <th>Field</th>
                                <th>Old Value</th>
                                <th>New Value</th>
                                <th>Change Type</th>
                                <th>Delta</th>
                                <th>User</th>
                                <th>Note</th>
                            </tr>
                        </thead>
                        <tbody id="pccHistoryRows">
                            <tr><td colspan="8" class="page-description">Select a product to view history.</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="pcc-v202-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-modal-close="productControlCenterModal">Close</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="pcc-adjust-popover pcc-v202-adjust-modal" id="pccAdjustPopover" hidden>
    <div class="pcc-adjust-popover-inner">
        <p class="pcc-adjust-title" id="pccAdjustTitle">Adjust Price</p>
        <label class="pcc-field pcc-field-compact" id="pccAdjustChangeTypeWrap">Change Type
            <select id="pccAdjustChangeType" class="form-input">
                <option value="increase">Increase</option>
                <option value="decrease">Decrease</option>
            </select>
        </label>
        <label class="pcc-field pcc-field-compact" id="pccAdjustMethodWrap">Method
            <select id="pccAdjustMethod" class="form-input">
                <option value="fixed">Fixed Amount</option>
                <option value="percent">Percentage</option>
            </select>
        </label>
        <label class="pcc-field pcc-field-compact" id="pccAdjustAmountWrap"><span id="pccAdjustAmountLabel">Amount</span> <span class="pcc-required">*</span>
            <input type="number" id="pccAdjustAmount" class="form-input" min="0" step="any" placeholder="Enter amount" required>
        </label>
        <label class="pcc-field pcc-field-compact" id="pccAdjustReasonWrap">Reason <span class="pcc-required">*</span>
            <select id="pccAdjustReason" class="form-input" required>
                <option value="">Select reason</option>
                <option value="Forward to Wholesale">Forward to Wholesale</option>
                <option value="Manual Correction">Manual Correction</option>
            </select>
        </label>
        <p class="pcc-adjust-preview" id="pccAdjustPreview"></p>
        <div class="pcc-adjust-actions">
            <button type="button" class="btn btn-primary btn-sm" id="pccAdjustApply">Apply</button>
            <button type="button" class="btn btn-ghost btn-sm" id="pccAdjustCancel">Cancel</button>
        </div>
    </div>
</div>
