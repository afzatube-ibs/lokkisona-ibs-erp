<?php
$costLabel = !empty($isSupplierView) ? 'Sale' : 'Cost';
$avgLabel = !empty($isSupplierView) ? 'Average Sale' : 'Average Cost';
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

        <div class="pcc-tabs" role="tablist">
            <button type="button" class="pcc-tab is-active" data-pcc-tab="details" role="tab" aria-selected="true">Product Details</button>
            <button type="button" class="pcc-tab" data-pcc-tab="history" role="tab" aria-selected="false"><?= e($costLabel) ?> / Stock History</button>
        </div>

        <form method="post" action="<?= e(url('/product-control/workspace/save')) ?>" id="productControlCenterForm">
            <?= $csrfField ?? '' ?>
            <input type="hidden" name="product_id" id="pccProductId" value="">
            <?php if (!empty($isSupplierView) && !empty($boundSupplierId)): ?>
            <input type="hidden" name="supplier_id" value="<?= e((string) $boundSupplierId) ?>">
            <?php endif; ?>
            <input type="hidden" name="business_source_id" id="pccBusinessSourceId" value="<?= e((string) ($defaultBusinessSourceId ?? 1)) ?>">
            <input type="hidden" name="variants" id="pccVariantsJson" value="">

            <div class="pcc-tab-panel is-active" data-pcc-panel="details">
                <div class="pcc-main-card">
                    <div class="pcc-main-visual" id="pccMainImageWrap">
                        <div class="pcc-image-placeholder" id="pccImagePlaceholder">No image</div>
                        <img src="" alt="" class="pcc-product-image" id="pccProductImage" hidden>
                    </div>
                    <div class="pcc-main-body">
                        <span class="pcc-eyebrow">Main product</span>
                        <h3 class="pcc-product-title" id="pccProductTitle">—</h3>
                        <p class="pcc-product-meta" id="pccProductMeta">—</p>
                    </div>
                    <div class="pcc-main-fields">
                        <p class="pcc-section-label">OpenCart / platform (read-only)</p>
                        <label class="pcc-field">ERP Product ID
                            <input type="text" id="pccErpProductIdDisplay" class="form-input" readonly>
                        </label>
                        <label class="pcc-field">OpenCart product name
                            <input type="text" id="pccProductNameDisplay" class="form-input" readonly>
                        </label>
                        <label class="pcc-field">OpenCart model
                            <input type="text" id="pccSourceModelDisplay" class="form-input" readonly>
                        </label>
                        <label class="pcc-field">OpenCart stock
                            <input type="text" id="pccOwnerStockReadonlyTop" class="form-input" readonly>
                        </label>
                        <label class="pcc-field">OpenCart source product ID
                            <input type="text" id="pccSourceProductIdDisplay" class="form-input" readonly>
                        </label>
                        <label class="pcc-field">OpenCart product status
                            <input type="text" id="pccOcStatusDisplay" class="form-input" readonly value="Not stored in ERP">
                        </label>
                        <div class="pcc-sync-info-card">
                            <p class="pcc-section-label">Sync information</p>
                            <dl class="info-list pcc-sync-info-list">
                                <div class="info-row">
                                    <dt>Last synced</dt>
                                    <dd id="pccSyncLastSynced">—</dd>
                                </div>
                                <div class="info-row">
                                    <dt>Source product ID</dt>
                                    <dd id="pccSyncSourceId">—</dd>
                                </div>
                                <div class="info-row">
                                    <dt>Source sync status</dt>
                                    <dd id="pccSyncStatus">—</dd>
                                </div>
                            </dl>
                        </div>
                        <p class="pcc-section-label">Supplier / ERP fields (editable)</p>
                        <label class="pcc-field">Main vendor model
                            <input type="text" name="supplier_model" id="pccSupplierModel" class="form-input">
                        </label>
                        <label class="pcc-field">Supplier category
                            <input type="text" name="supplier_product_category" id="pccCategory" class="form-input">
                        </label>
                        <label class="pcc-field">Low warning
                            <input type="number" name="low_warning_threshold" id="pccLowWarning" min="0" class="form-input">
                        </label>
                        <label class="pcc-field">Supplier active/inactive
                            <select name="status" id="pccStatus" class="form-input">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </label>
                        <?php if (empty($isSupplierView) && !empty($supplierSelectOptions)): ?>
                        <label class="pcc-field">Supplier assignment (owner)
                            <select name="supplier_id" id="pccSupplierId" class="form-input">
                                <option value="">— Unassigned —</option>
                                <?php foreach ($supplierSelectOptions as $supplier): ?>
                                <option value="<?= e((string) ($supplier['supplier_id'] ?? '')) ?>"><?= e($supplier['label'] ?? '') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <?php endif; ?>
                        <?php if (!empty($writeGateSupplierNoteReady)): ?>
                        <label class="pcc-field">Supplier note
                            <textarea name="supplier_note" id="pccSupplierNote" class="form-input" rows="2" placeholder="Internal supplier note (ERP only)"></textarea>
                        </label>
                        <?php elseif (!empty($writeGateSupplierNote)): ?>
                        <p class="page-description pcc-field-hint"><?= e($writeGateSupplierNote['message'] ?? 'Supplier note unavailable until migration 0012 is applied manually.') ?></p>
                        <?php endif; ?>
                        <p class="page-description pcc-field-hint">Only supplier/ERP fields are saved. OpenCart defaults are never overwritten from this page.</p>
                    </div>
                </div>

                <div class="pcc-simple-product" id="pccSimpleProductFields" hidden>
                    <div class="form-grid">
                        <label><?= e($avgLabel) ?>
                            <input type="number" name="product_cost" id="pccProductCost" step="0.01" min="0" class="form-input">
                        </label>
                        <label>Vendor stock
                            <input type="number" name="vendor_stock" id="pccProductVendorStock" min="0" class="form-input">
                        </label>
                    </div>
                </div>

                <div class="pcc-variant-section" id="pccVariantSection" hidden>
                    <p class="page-description pcc-no-options-notice" id="pccNoOptionsNotice" hidden>No option synced from Lokkisona for this variable product. Parent row is kept; option lines will appear after the next warehouse pull returns options.</p>
                    <div id="pccVariantAccordion"></div>
                </div>

                <?php if (!empty($canManage) && !empty($writeGateProductEditReady)): ?>
                <div class="pcc-save-bar">
                    <p class="page-description">Save supplier vendor model, <?= strtolower($costLabel) ?>, stock, and warnings only.</p>
                    <button type="submit" class="btn btn-primary">Save All Changes</button>
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
            </div>
        </form>

        <div class="pcc-modal-footer">
            <button type="button" class="btn btn-secondary" data-modal-close="productControlCenterModal">Close</button>
        </div>
    </div>
</div>
