<div class="page-header page-header-compact">
    <h1 class="page-title">Sync Preview</h1>
    <p class="ops-page-subtitle">Order sync preview — max 20 rows per page, preview before import, supplier-handled orders only. Product sync has moved to Product Control.</p>
</div>

<div class="ops-safety-strip mb-15">
    <strong>Product sync relocated.</strong> Load, preview, and import warehouse products from
    <a href="<?= e(url('/product-control')) ?>">Fulfillment → Product Control → Product Sync / Import</a>.
    This page remains for order sync preview until order import is embedded in Orders.
</div>

<?php view('partials.flash-messages', ['flashSuccess' => $flashSuccess ?? null, 'flashError' => $flashError ?? null]); ?>

<?php
view('partials.write-gate-warning', [
    'writeGateReady' => $writeGateReady ?? false,
    'writeGate' => $writeGate ?? [],
    'writeGateMessage' => null,
]);
?>

<?php if (!empty($canManage) && !empty($writeGateReady)): ?>
<div id="order-sync" class="card mb-15">
    <div class="card-header"><h2 class="card-title">Order Sync Preview</h2></div>
    <div class="card-body">
        <p class="page-description">Supplier-handled orders only (status mapping). Product mapping alone does not import orders. Skip Missing / status 0.</p>
        <div class="sync-action-bar">
            <div class="sync-action">
                <form method="post" action="<?= e(url('/sync-preview/run-test-sync')) ?>">
                    <?= $csrfField ?? '' ?>
                    <input type="hidden" name="business_source_id" value="<?= e((string) ($defaultBusinessSourceId ?? 1)) ?>">
                    <input type="hidden" name="page" value="<?= e((string) ($orderPage ?? 1)) ?>">
                    <button type="submit" class="btn btn-primary btn-block">Load order preview (page <?= e((string) ($orderPage ?? 1)) ?>)</button>
                </form>
            </div>
        </div>
        <?php
        view('partials.sync-pagination', [
            'page' => $orderPage ?? 1,
            'pageParam' => 'order_page',
            'baseUrl' => url('/sync-preview'),
            'pagination' => $testSyncPreview['pagination'] ?? ['has_previous' => ($orderPage ?? 1) > 1, 'has_next' => false, 'per_page' => 20],
            'otherPageQuery' => [],
        ]);
        ?>
        <?php if (!empty($testSyncPreview['preview_counts'])): ?>
        <div class="kpi-grid kpi-grid-inline" style="margin-top:1rem;">
            <?php foreach (($testSyncPreview['preview_counts'] ?? []) as $label => $count): ?>
            <div class="kpi-card kpi-accent-muted">
                <span class="kpi-label"><?= e(str_replace('_', ' ', $label)) ?></span>
                <span class="kpi-value"><?= e((string) $count) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php if (!empty($testSyncPreview['display_rows'])): ?>
        <div class="table-scroll" style="margin-top:1rem;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>OC Order</th>
                        <th>Customer</th>
                        <th>Phone</th>
                        <th>Product</th>
                        <th>Qty</th>
                        <th>Source status</th>
                        <th>Courier</th>
                        <th>Consignment</th>
                        <th>Supplier?</th>
                        <th>IBS status</th>
                        <th>Imported?</th>
                        <th>Preview</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($testSyncPreview['display_rows'] as $row): ?>
                    <tr>
                        <td><code><?= e($row['source_order_reference'] ?? '') ?></code></td>
                        <td><?= e($row['customer_name'] ?? '') ?></td>
                        <td><?= e($row['customer_phone'] ?? '') ?></td>
                        <td><?= e($row['product_card'] ?? '') ?></td>
                        <td><?= e((string) ($row['total_quantity'] ?? 0)) ?></td>
                        <td><?= e($row['source_status'] ?? '') ?></td>
                        <td><?= e($row['courier_status'] ?? '—') ?></td>
                        <td><?= e($row['consignment_id'] ?? '—') ?></td>
                        <td><?= e($row['supplier_handled'] ?? '') ?><br><small><?= e($row['supplier_handled_reason'] ?? '') ?></small></td>
                        <td><?= e($row['mapped_status'] ?? '—') ?></td>
                        <td><?= e($row['already_imported'] ?? 'No') ?></td>
                        <td><?= e($row['preview_status'] ?? '') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <form method="post" action="<?= e(url('/sync-preview/import')) ?>" style="margin-top:1rem;">
            <?= $csrfField ?? '' ?>
            <input type="hidden" name="business_source_id" value="<?= e((string) ($defaultBusinessSourceId ?? 1)) ?>">
            <input type="hidden" name="page" value="<?= e((string) ($orderPage ?? 1)) ?>">
            <input type="hidden" name="sync_preview_id" value="<?= e((string) ($testSyncPreview['latest_preview']['sync_preview_id'] ?? '')) ?>">
            <label class="sync-import-confirm">
                <input type="checkbox" name="import_confirmation" value="1" required>
                <span>Owner confirms import of eligible orders on preview page <?= e((string) ($orderPage ?? 1)) ?></span>
            </label>
            <button type="submit" class="btn btn-success">Import Eligible Orders</button>
        </form>
        <?php else: ?>
        <p class="page-description" style="margin-top:1rem;">Load order preview to classify supplier-handled rows.</p>
        <?php endif; ?>
        <p class="page-description" style="margin-top:1rem;">One request per button — no background sync. IBS workflow is not changed on re-import.</p>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($testSyncPreview)): ?>
<div class="card mb-15">
    <div class="card-header"><h2 class="card-title">Sync Rules Summary</h2></div>
    <div class="card-body">
        <p><strong>Source:</strong> <?= e($testSyncPreview['source'] ?? '') ?></p>
        <p><strong>Status:</strong> <?= e($testSyncPreview['status'] ?? '') ?> — <?= e($testSyncPreview['message'] ?? '') ?></p>
        <ul class="feature-list">
            <?php foreach (($testSyncPreview['rules'] ?? []) as $rule): ?>
                <li><?= e($rule) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
<?php endif; ?>
