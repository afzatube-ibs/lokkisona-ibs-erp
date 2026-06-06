<?php
$isSupplierReturn = !empty($isSupplierReturn);
$isLokkisonaReturn = !empty($isLokkisonaReturn);
$returnTypeLabel = (string) ($order['return_type_label'] ?? '');
$destinationLabel = \App\Domain\ReturnReceiveType::destinationLabel((string) ($order['return_type'] ?? ''));
?>
<form method="post" action="<?= e(url('/return-receive/confirm')) ?>" class="js-return-receive-form return-receive-confirm-form" data-confirm-label="Confirm Return Received">
    <?= $csrfField ?? '' ?>
    <input type="hidden" name="order_id" value="<?= e((string) ($order['erp_order_id'] ?? $order['order_id'] ?? '')) ?>">
    <input type="hidden" name="return_type" value="<?= e((string) ($order['return_type'] ?? '')) ?>">
    <input type="hidden" name="receive_confirmed" value="0" class="js-receive-confirmed">

    <div class="return-receive-meta" style="margin-bottom: 1rem;">
        <span class="badge badge-warn"><?= e($destinationLabel) ?></span>
        <span class="badge"><?= e($returnTypeLabel) ?></span>
    </div>

    <?php view('partials.choice-cards', [
        'name' => 'return_reason',
        'legend' => 'Return reason / source',
        'options' => $reasonOptions ?? [],
        'required' => true,
    ]); ?>

    <?php view('partials.choice-cards', [
        'name' => 'received_confirmation',
        'legend' => 'Received confirmation',
        'options' => $receivedConfirmationOptions ?? [],
        'required' => true,
    ]); ?>

    <?php if ($isSupplierReturn): ?>
    <?php view('partials.choice-cards', [
        'name' => 'supplier_condition',
        'legend' => 'Supplier condition',
        'options' => $conditionOptions ?? [],
        'required' => true,
    ]); ?>

    <label style="margin-top: 1rem; display: block;">
        Supplier received note *
        <textarea name="supplier_note" class="form-input" required placeholder="What supplier received and observed"></textarea>
    </label>
    <label style="margin-top: 0.75rem; display: block;">
        Owner note (optional)
        <textarea name="owner_note" class="form-input" placeholder="Optional owner-side note"></textarea>
    </label>
    <?php endif; ?>

    <?php if ($isLokkisonaReturn): ?>
    <label style="margin-top: 1rem; display: block;">
        Lokkisona / owner note *
        <textarea name="owner_note" class="form-input" required placeholder="Owner warehouse receive note"></textarea>
    </label>
    <?php endif; ?>

    <label style="margin-top: 1rem; display: block;">
        Verification note (optional)
        <textarea name="verification_note" class="form-input" placeholder="Optional note after checking displayed order, consignment, and product details"></textarea>
    </label>

    <label class="workflow-confirm-checkbox" style="margin-top: 1rem;">
        <input type="checkbox" name="staff_confirmation" value="1" required>
        <span>I confirm this returned order/product was checked against ERP order details and received.</span>
    </label>
    <button type="submit" class="btn btn-primary" style="margin-top: 0.75rem;">Confirm Return Received</button>
</form>
