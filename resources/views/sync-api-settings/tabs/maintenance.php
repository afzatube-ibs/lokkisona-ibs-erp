<?php
$sourceId = (int) ($entryMapping['business_source_id'] ?? config('opencart.business_source_id', 1));
?>
<div class="card sync-hub-card-wide">
    <div class="card-header">
        <h2 class="card-title">Maintenance Tools</h2>
        <p class="page-description mb-0">Session clears and selective ERP resets. Each action requires confirmation.</p>
    </div>
    <div class="card-body">
        <?php if (!empty($canSyncHub)): ?>
        <div class="sync-hub-reset-grid">
            <?php
            $resetForms = [
                ['/sync-api-settings/reset/clear-product-preview', 'Clear Product Preview', 'Clears the in-memory product preview session only.'],
                ['/sync-api-settings/reset/product-data', 'Reset Imported Products', 'Removes synced products from ERP. Does not touch orders.'],
                ['/sync-api-settings/reset/clear-order-preview', 'Clear Order Preview', 'Clears the in-memory order preview session only.'],
                ['/sync-api-settings/reset/demo-orders', 'Clean Test Orders', 'Deletes demo synced orders not yet dispatched.'],
                ['/sync-api-settings/reset/entry-mappings', 'Reset Entry Mapping', 'Deactivates all Import-as-NEW entry mappings.'],
                ['/sync-api-settings/reset/final-result-mappings', 'Reset Final Mapping', 'Removes Delivered / Returned OC status pickers.'],
            ];
            foreach ($resetForms as [$action, $title, $desc]):
            ?>
            <form method="post" action="<?= e(url($action)) ?>" class="sync-hub-reset-card is-danger">
                <?= $csrfField ?? '' ?>
                <input type="hidden" name="tab" value="maintenance">
                <input type="hidden" name="business_source_id" value="<?= e((string) $sourceId) ?>">
                <h3><?= e($title) ?></h3>
                <p><?= e($desc) ?></p>
                <label class="sync-hub-import-confirm">
                    <input type="checkbox" name="reset_confirmation" value="1">
                    <span>Confirm</span>
                </label>
                <button type="submit" class="btn btn-outline btn-sm mt-10">Run</button>
            </form>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p class="page-description">Maintenance tools require Sync Hub permission.</p>
        <?php endif; ?>
    </div>
</div>
