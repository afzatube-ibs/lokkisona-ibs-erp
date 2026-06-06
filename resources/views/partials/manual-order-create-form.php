<?php
$formAction = $formAction ?? url('/manual-orders/create');
$formId = $formId ?? 'manualOrderForm';
$submitLabel = $submitLabel ?? 'Create Manual Order';
$showSubmitButton = $showSubmitButton ?? true;
?>
<form method="post" action="<?= e($formAction) ?>" id="<?= e($formId) ?>" class="manual-order-form">
    <?= $csrfField ?? '' ?>

    <h3 class="section-subtitle">Source &amp; References</h3>
    <div class="form-grid form-grid-wide">
        <label>
            Business source *
            <select name="business_source_id" class="form-input" required>
                <option value="">Select source…</option>
                <?php foreach ($businessSourceOptions ?? [] as $opt): ?>
                    <option value="<?= (int) $opt['id'] ?>"><?= e($opt['label']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            Supplier (optional)
            <select name="supplier_id" class="form-input">
                <option value="">—</option>
                <?php foreach ($supplierOptions ?? [] as $opt): ?>
                    <option value="<?= (int) $opt['id'] ?>"><?= e($opt['label']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Manual order reference (optional)<input type="text" name="manual_order_reference" class="form-input" placeholder="Auto-generated if blank"></label>
        <label>External order reference (optional)<input type="text" name="external_order_reference" class="form-input"></label>
        <label>External invoice reference (optional)<input type="text" name="external_invoice_reference" class="form-input"></label>
    </div>

    <h3 class="section-subtitle">Customer</h3>
    <div class="form-grid form-grid-wide manual-order-customer-grid">
        <label>Customer name *<input type="text" name="customer_name" required class="form-input"></label>
        <label>Customer phone<input type="text" name="customer_phone" class="form-input"></label>
        <label class="manual-order-address-field">Customer address<textarea name="customer_address" class="form-input" style="min-height:72px;"></textarea></label>
    </div>

    <?php view('partials.manual-order-line-items', ['productOptions' => $productOptions ?? []]); ?>

    <div class="form-grid" style="margin-top:1rem;">
        <label>Confirmation note *<textarea name="confirmation_note" required class="form-input" style="min-height:72px;" placeholder="Why this order is being entered manually"></textarea></label>
        <label style="display:flex;gap:0.5rem;align-items:flex-start;">
            <input type="checkbox" name="dev_test_confirmation" value="1" required>
            <span>I confirm this manual order entry. It must not create payable, stock deduction, invoice, or live channel sync.</span>
        </label>
    </div>
    <?php if ($showSubmitButton): ?>
    <button type="submit" class="btn btn-primary" style="margin-top:0.75rem;"><?= e($submitLabel) ?></button>
    <?php endif; ?>
</form>
