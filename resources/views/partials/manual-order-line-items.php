<?php
$productOptions = $productOptions ?? [];
$rowIndex = 0;
?>
<div class="manual-order-line-items">
    <h3 class="section-subtitle">Order Lines</h3>
    <div class="table-scroll">
        <table class="data-table" id="manualOrderItemsTable">
            <thead>
                <tr>
                    <th>Product</th>
                    <th style="width:160px;">Variant</th>
                    <th style="width:90px;">Qty</th>
                    <th style="width:120px;">Selling Price</th>
                    <th style="width:120px;">Line Total</th>
                    <th style="width:60px;"></th>
                </tr>
            </thead>
            <tbody id="manualOrderItemsBody">
                <tr class="mo-item-row" data-row-index="<?= (int) $rowIndex ?>">
                    <td>
                        <select name="items[<?= (int) $rowIndex ?>][product_id]" class="form-input mo-product" required>
                            <option value="">Select product…</option>
                            <?php foreach ($productOptions as $opt): ?>
                                <option value="<?= (int) $opt['id'] ?>"><?= e($opt['label']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td>
                        <select name="items[<?= (int) $rowIndex ?>][product_variant_id]" class="form-input mo-variant">
                            <option value="">—</option>
                        </select>
                    </td>
                    <td>
                        <input type="number" name="items[<?= (int) $rowIndex ?>][quantity]" class="form-input mo-qty" min="1" value="1" required>
                    </td>
                    <td>
                        <input type="number" name="items[<?= (int) $rowIndex ?>][selling_price]" class="form-input mo-price" min="0" step="0.01" value="0" required>
                    </td>
                    <td class="mo-line-total">0.00</td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    </div>
    <button type="button" class="btn btn-secondary btn-sm" id="moAddRow" style="margin-top:0.5rem;">+ Add Product Row</button>

    <div class="manual-order-summary-panel">
        <div class="invoice-total-row"><span>Line count</span><strong id="moLineCount">1</strong></div>
        <div class="invoice-total-row"><span>Total quantity</span><strong id="moTotalQty">1</strong></div>
        <div class="invoice-total-row"><span>Selling subtotal</span><strong id="moSellingSubtotal">0.00</strong></div>
        <div class="invoice-total-row invoice-total-due"><span>Cost snapshot subtotal</span><strong id="moCostSubtotal">0.00</strong></div>
        <p class="page-description" style="margin:0.5rem 0 0;font-size:0.8125rem;">Cost snapshot is display-only at entry time. No stock or payable is created on save.</p>
    </div>
</div>
