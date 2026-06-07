<?php if (empty($canManage) || empty($productWriteGateReady)): ?>
<?php return; ?>
<?php endif; ?>
<div class="card mb-15 card-warn-border product-sync-reset-card">
    <div class="card-header">
        <h2 class="card-title">Reset Product Sync Data</h2>
    </div>
    <div class="card-body">
        <p class="page-description">Clears ERP-side synced/test product catalog rows, variants, related cost/stock audit rows for those products, and the product preview session. Does not touch orders, payables, API settings, or OpenCart.</p>
        <form method="post" action="<?= e(url('/sync-preview/reset-product-sync')) ?>" class="product-sync-reset-form">
            <?= $csrfField ?? '' ?>
            <?php if (!empty($redirectTo)): ?>
            <input type="hidden" name="redirect_to" value="<?= e((string) $redirectTo) ?>">
            <?php endif; ?>
            <label class="sync-settings-switch product-sync-reset-confirm">
                <input type="checkbox" name="reset_confirmation" value="1">
                <span>I understand this removes synced/test product data from ERP only.</span>
            </label>
            <div class="product-sync-reset-actions">
                <button type="submit" class="btn btn-danger">Reset Product Sync Data</button>
            </div>
        </form>
    </div>
</div>
