<?php
$invoiceRefPreview = 'SQI-' . date('Ymd') . '-preview';
$invoiceDate = date('Y-m-d');
$supplierLabel = 'Iqbal & Brothers';
?>
<div class="modal-overlay" id="supplierQuickInvoiceModal" hidden aria-hidden="true">
    <div class="modal-panel modal-panel-wide" role="dialog" aria-labelledby="invoiceModalTitle" aria-modal="true">
        <div class="modal-header">
            <h2 class="modal-title" id="invoiceModalTitle">Quick Invoice Generator</h2>
            <button type="button" class="modal-close" data-modal-close="supplierQuickInvoiceModal" aria-label="Close invoice generator">&times;</button>
        </div>
        <div class="modal-body">
            <?php if (empty($quickInvoiceGateReady)): ?>
                <div class="alert alert-error">
                    <?= e($writeGateMessage ?? 'Required tables are not applied yet.') ?>
                    <br>Apply migrations 0007 and 0010 manually before generating invoices.
                </div>
            <?php else: ?>
            <form method="post" action="<?= e(url('/supplier-tools/quick-invoice')) ?>" id="quickInvoiceForm" class="quick-invoice-form">
                <?= $csrfField ?? '' ?>
                <div class="invoice-form-header">
                    <div class="form-group">
                        <label>Supplier</label>
                        <input type="text" class="form-input" value="<?= e($supplierLabel) ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Invoice Date</label>
                        <input type="date" name="invoice_date" class="form-input" value="<?= e($invoiceDate) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Reference (preview)</label>
                        <input type="text" class="form-input" id="invoiceRefPreview" value="<?= e($invoiceRefPreview) ?>" readonly>
                    </div>
                </div>

                <h3 class="section-subtitle">Customer</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Customer Name</label>
                        <input type="text" name="customer_name" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="customer_phone" class="form-input">
                    </div>
                </div>
                <div class="form-group">
                    <label>Address</label>
                    <textarea name="customer_address" class="form-input" rows="2"></textarea>
                </div>

                <h3 class="section-subtitle">Products</h3>
                <div class="table-scroll">
                    <table class="data-table" id="quickInvoiceItemsTable">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th style="width:90px;">Qty</th>
                                <th style="width:120px;">Unit Price</th>
                                <th style="width:120px;">Line Total</th>
                                <th style="width:60px;"></th>
                            </tr>
                        </thead>
                        <tbody id="quickInvoiceItemsBody">
                            <tr class="qi-item-row">
                                <td><input type="text" name="items[0][name]" class="form-input" required placeholder="Product name"></td>
                                <td><input type="number" name="items[0][qty]" class="form-input qi-qty" min="1" value="1" required></td>
                                <td><input type="number" name="items[0][unit_price]" class="form-input qi-price" min="0" step="0.01" value="0" required></td>
                                <td class="qi-line-total">0.00</td>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-secondary btn-sm" id="qiAddRow" style="margin-top:0.5rem;">+ Add Product Row</button>

                <div class="invoice-totals-panel">
                    <div class="invoice-total-row"><span>Subtotal</span><strong id="qiSubtotal">0.00</strong></div>
                    <div class="invoice-total-row">
                        <label for="qiDiscount">Discount</label>
                        <input type="number" name="discount_amount" id="qiDiscount" class="form-input" min="0" step="0.01" value="0">
                    </div>
                    <div class="invoice-total-row"><span>After Discount</span><strong id="qiAfterDiscount">0.00</strong></div>
                    <div class="invoice-total-row">
                        <label for="qiAdvance">Advance Paid</label>
                        <input type="number" name="advance_amount" id="qiAdvance" class="form-input" min="0" step="0.01" value="0">
                    </div>
                    <div class="invoice-total-row invoice-total-due"><span>Balance Due</span><strong id="qiBalanceDue">0.00</strong></div>
                </div>

                <div class="form-group">
                    <label>Notes</label>
                    <textarea name="notes" class="form-input" rows="2" placeholder="Optional note for print"></textarea>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" id="qiClearForm">Clear</button>
                    <button type="submit" class="btn btn-primary">Generate &amp; Print</button>
                </div>
                <p class="modal-hint">Independent supplier invoice — no ERP payable, order, or stock impact.</p>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>
