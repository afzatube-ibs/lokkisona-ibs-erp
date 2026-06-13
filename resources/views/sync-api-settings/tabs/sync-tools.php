<?php
$orderSync = $orderSync ?? [];
$orderPreview = $orderSync['preview'] ?? [];
$syncHistory = $syncHistory ?? [];
$connectionSummary = $connectionSummary ?? [];
$sourceId = (int) ($entryMapping['business_source_id'] ?? config('opencart.business_source_id', 1));
$hasOrderPreview = is_array($orderPreview) && !empty($orderPreview['display_rows']);
?>
<div class="card sync-hub-card-wide mb-15">
    <div class="card-header">
        <h2 class="card-title">Order Sync</h2>
        <p class="page-description mb-0">Queue-mapped orders with warehouse lines only. Max 20 per test.</p>
    </div>
    <div class="card-body">
        <?php if (!empty($canSyncHub) && !empty($orderWriteGateReady)): ?>
        <form method="post" action="<?= e(url('/sync-api-settings/run-order-preview')) ?>" class="mb-15">
            <?= $csrfField ?? '' ?>
            <input type="hidden" name="tab" value="sync">
            <input type="hidden" name="business_source_id" value="<?= e((string) $sourceId) ?>">
            <input type="hidden" name="page" value="1">
            <button type="submit" class="btn btn-secondary">Run Order Sync Test</button>
        </form>

        <?php if ($hasOrderPreview): ?>
        <?php if (!empty($orderPreview['preview_counts'])): ?>
        <div class="workflow-chip-row mb-15">
            <?php foreach ($orderPreview['preview_counts'] as $label => $count): ?>
            <span class="workflow-chip"><?= e((string) $label) ?>: <?= e((string) $count) ?></span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <div class="sync-hub-table-wrap mb-15">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>OC Status</th>
                        <th>Mapping</th>
                        <th>Result</th>
                        <th>Phone</th>
                        <th>Product</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orderPreview['display_rows'] as $row): if (!is_array($row)) continue; ?>
                    <tr>
                        <td><?= e((string) ($row['order_no'] ?? '')) ?></td>
                        <td><?= e((string) ($row['origin_oc_status'] ?? '')) ?></td>
                        <td><?= e((string) ($row['mapped_ibs_status'] ?? '')) ?></td>
                        <td><span title="<?= e((string) ($row['import_result_detail'] ?? '')) ?>"><?= e((string) ($row['import_result'] ?? '')) ?></span></td>
                        <td><?= e((string) ($row['customer_phone'] ?? '')) ?></td>
                        <td><?= e((string) ($row['product_card'] ?? '')) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <form method="post" action="<?= e(url('/sync-api-settings/import-orders')) ?>">
            <?= $csrfField ?? '' ?>
            <input type="hidden" name="tab" value="sync">
            <input type="hidden" name="business_source_id" value="<?= e((string) $sourceId) ?>">
            <input type="hidden" name="sync_preview_id" value="<?= e((string) ($orderPreview['active_preview_id'] ?? '')) ?>">
            <input type="hidden" name="page" value="<?= e((string) ($orderPreview['pagination']['page'] ?? 1)) ?>">
            <div class="sync-hub-import-row">
                <label class="sync-hub-import-confirm">
                    <input type="checkbox" name="import_confirmation" value="1">
                    <span>I confirm order import from preview</span>
                </label>
                <button type="submit" class="btn btn-primary">Import Eligible Orders</button>
            </div>
        </form>
        <?php else: ?>
        <div class="sync-hub-empty">Click <strong>Run Order Sync Test</strong> to preview eligible orders.</div>
        <?php endif; ?>
        <?php else: ?>
        <p class="page-description"><?= empty($orderWriteGateReady) ? 'Order sync tables not ready.' : 'View-only or missing Sync Hub permission.' ?></p>
        <?php endif; ?>
    </div>
</div>

<div class="card sync-hub-card-wide mb-15">
    <div class="card-header"><h2 class="card-title">Reset Tools</h2></div>
    <div class="card-body">
        <?php if (!empty($canSyncHub)): ?>
        <div class="sync-hub-reset-grid">
            <?php
            $resetForms = [
                ['/sync-api-settings/reset/clear-product-preview', 'Clear product preview', 'Clears the in-memory product preview session only.'],
                ['/sync-api-settings/reset/product-data', 'Reset synced product data', 'Removes synced products from ERP. Does not touch orders.'],
                ['/sync-api-settings/reset/clear-order-preview', 'Clear order preview', 'Clears the in-memory order preview session only.'],
                ['/sync-api-settings/reset/demo-orders', 'Clean demo/test orders', 'Deletes demo synced orders not yet dispatched.'],
                ['/sync-api-settings/reset/entry-mappings', 'Clear entry mappings', 'Deactivates all Import-as-NEW entry mappings.'],
                ['/sync-api-settings/reset/final-result-mappings', 'Clear final result mappings', 'Removes Delivered / Returned OC status pickers.'],
            ];
            foreach ($resetForms as [$action, $title, $desc]):
            ?>
            <form method="post" action="<?= e(url($action)) ?>" class="sync-hub-reset-card is-danger">
                <?= $csrfField ?? '' ?>
                <input type="hidden" name="tab" value="sync">
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
        <p class="page-description">Reset tools require Sync Hub permission.</p>
        <?php endif; ?>
    </div>
</div>

<div class="card sync-hub-card-wide">
    <div class="card-header">
        <h2 class="card-title">Sync History</h2>
        <a href="<?= e(url('/activity-log')) ?>" class="btn btn-secondary btn-sm">View full activity log</a>
    </div>
    <div class="card-body">
        <?php if ($syncHistory !== []): ?>
        <div class="table-scroll">
            <table class="data-table">
                <thead><tr><th>Time</th><th>User</th><th>Action</th><th>Summary</th></tr></thead>
                <tbody>
                    <?php foreach ($syncHistory as $entry): ?>
                    <tr>
                        <td><?= e((string) ($entry['time'] ?? '')) ?></td>
                        <td><?= e((string) ($entry['user'] ?? '')) ?></td>
                        <td><code><?= e((string) ($entry['action'] ?? '')) ?></code></td>
                        <td><?= e((string) ($entry['message'] ?? '')) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="sync-hub-empty">No sync activity logged yet.</div>
        <?php endif; ?>
        <p class="form-help mt-15">Last product sync: <?= e((string) ($connectionSummary['last_product_sync_at'] ?? '—')) ?> · Last order sync: <?= e((string) ($connectionSummary['last_order_sync_at'] ?? '—')) ?></p>
    </div>
</div>
