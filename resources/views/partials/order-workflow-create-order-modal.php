<?php
$manualOrderGateReady = $manualOrderGateReady ?? false;
$writeGateMessage = $writeGateMessage ?? \App\ReadFoundation\WriteGate::WARNING_MESSAGE;
?>
<div class="modal-overlay" id="workflowCreateOrderModal" hidden aria-hidden="true">
    <div class="modal-panel modal-panel-wide" role="dialog" aria-labelledby="workflowCreateOrderTitle" aria-modal="true">
        <div class="modal-header">
            <h2 class="modal-title" id="workflowCreateOrderTitle">Create New Order</h2>
            <button type="button" class="modal-close" data-modal-close="workflowCreateOrderModal" aria-label="Close create order form">&times;</button>
        </div>
        <div class="modal-body">
            <?php if (empty($manualOrderGateReady)): ?>
                <div class="alert alert-error">
                    <?= e($writeGateMessage) ?>
                    <br>Apply migrations 0005 (Group C) and Group B product/source tables before creating orders.
                </div>
            <?php else: ?>
                <p class="page-description" style="margin-top:0;">Offline, Sonamoni reference, or manual channel entry. Order enters workflow at <strong>New Order</strong>. No payable, stock, invoice, or sync on save.</p>
                <?php view('partials.manual-order-create-form', [
                    'formAction' => url('/order-workflow/create'),
                    'formId' => 'workflowCreateOrderForm',
                    'submitLabel' => 'Create Order',
                    'showSubmitButton' => true,
                    'csrfField' => $csrfField ?? '',
                    'businessSourceOptions' => $businessSourceOptions ?? [],
                    'supplierOptions' => $supplierOptions ?? [],
                    'productOptions' => $productOptions ?? [],
                ]); ?>
                <script type="application/json" id="moVariantMap"><?= json_encode($variantOptionsByProduct ?? [], JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
                <script type="application/json" id="moProductCosts"><?= json_encode($productCostById ?? [], JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
                <template id="moProductOptionsTemplate"><?php foreach ($productOptions ?? [] as $opt): ?><option value="<?= (int) $opt['id'] ?>"><?= e($opt['label']) ?></option><?php endforeach; ?></template>
                <script src="<?= e(asset('js/manual-orders.js')) ?>"></script>
            <?php endif; ?>
        </div>
    </div>
</div>
