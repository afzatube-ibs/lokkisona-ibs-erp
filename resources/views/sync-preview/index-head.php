<div class="page-header page-header-compact">

    <h1 class="page-title">Sync Preview</h1>

    <p class="ops-page-subtitle">Order sync preview — status mapping controls import eligibility. Product cost, vendor stock, and Product Control health never block order import.</p>

</div>



<div class="ops-safety-strip mb-15">

    <strong>Status Mapping → Eligible / Blocked.</strong> Product sync lives on

    <a href="<?= e(url('/product-control')) ?>">Product Control</a> (cost, payables, warnings only).

    Map OpenCart statuses at <a href="<?= e(url('/status-mapping')) ?>">Status Mapping</a> before preview.

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

        <p class="page-description">Import decision flow: <strong>Origin OC Status</strong> → <strong>Status Mapping</strong> → <strong>Import Result</strong>. Missing product cost or incomplete product mapping does not block import.</p>

        <div class="sync-action-bar">

            <div class="sync-action">

                <form method="post" action="<?= e(url('/sync-preview/run-test-sync')) ?>">

                    <?= $csrfField ?? '' ?>

                    <input type="hidden" name="business_source_id" value="<?= e((string) ($defaultBusinessSourceId ?? 1)) ?>">

                    <input type="hidden" name="page" value="<?= e((string) ($orderPage ?? 1)) ?>">

                    <button type="submit" class="btn btn-primary btn-block">Test Order Sync / Preview Orders (page <?= e((string) ($orderPage ?? 1)) ?>)</button>

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

                <span class="kpi-label"><?= e((string) $label) ?></span>

                <span class="kpi-value"><?= e((string) $count) ?></span>

            </div>

            <?php endforeach; ?>

        </div>

        <?php endif; ?>

        <?php

        $importableCount = (int) ($testSyncPreview['importable_count'] ?? 0);

        $activePreviewId = (int) ($testSyncPreview['active_preview_id'] ?? ($testSyncPreview['latest_preview']['sync_preview_id'] ?? 0));

        ?>

        <?php if (!empty($testSyncPreview['display_rows'])): ?>

        <div class="table-scroll" style="margin-top:1rem;">

            <table class="data-table">

                <thead>

                    <tr>

                        <th>Order No</th>

                        <th>Origin Status ID</th>

                        <th>Origin Status Name</th>

                        <th>Matched Mapping?</th>

                        <th>Mapped IBS Status</th>

                        <th>Import Result</th>

                    </tr>

                </thead>

                <tbody>

                    <?php foreach ($testSyncPreview['display_rows'] as $row): ?>

                    <tr>

                        <td><code>#<?= e(ltrim((string) ($row['order_no'] ?? $row['source_order_id'] ?? $row['source_order_reference'] ?? ''), '#')) ?></code></td>

                        <td><?= e((string) ($row['origin_status_id'] ?? '—')) ?></td>

                        <td><?= e((string) ($row['origin_status_name'] ?? $row['source_status'] ?? '—')) ?></td>

                        <td><?= e((string) ($row['mapping_matched'] ?? 'NO')) ?></td>

                        <td><?= e($row['mapped_ibs_status'] ?? 'No Mapping') ?></td>

                        <td title="<?= e($row['import_result_detail'] ?? '') ?>"><?= e($row['import_result'] ?? '') ?></td>

                    </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

        <?php if ($importableCount > 0 && $activePreviewId > 0): ?>

        <form method="post" action="<?= e(url('/sync-preview/import')) ?>" style="margin-top:1rem;">

            <?= $csrfField ?? '' ?>

            <input type="hidden" name="business_source_id" value="<?= e((string) ($defaultBusinessSourceId ?? 1)) ?>">

            <input type="hidden" name="page" value="<?= e((string) ($orderPage ?? 1)) ?>">

            <input type="hidden" name="sync_preview_id" value="<?= e((string) $activePreviewId) ?>">

            <label class="sync-import-confirm">

                <input type="checkbox" name="import_confirmation" value="1" required>

                <span>Owner confirms import of <?= e((string) $importableCount) ?> eligible order(s) on preview page <?= e((string) ($orderPage ?? 1)) ?></span>

            </label>

            <button type="submit" class="btn btn-success">Import Mapped Orders</button>

        </form>

        <?php else: ?>

        <div class="card card-warn-border" style="margin-top:1rem;padding:0.75rem 1rem;">

            <p class="page-description" style="margin:0;"><strong>No importable rows on this preview.</strong> Check <em>Import Result</em> — only <strong>Eligible</strong> or <strong>Eligible (Existing Order)</strong> will import. <strong>Blocked</strong> with <em>No Mapping</em> means add a row at <a href="<?= e(url('/status-mapping')) ?>">Status Mapping</a> (e.g. Follow Up → New Order). Product issues are handled in Product Control, not here.</p>

        </div>

        <?php endif; ?>

        <?php else: ?>

        <p class="page-description" style="margin-top:1rem;">Run Test Order Sync to classify each order by status mapping.</p>

        <?php endif; ?>

        <p class="page-description" style="margin-top:1rem;">One request per button — max 50 orders, no background sync. Existing ERP orders refresh OpenCart snapshot fields only; IBS fulfillment status is never reset on re-sync.</p>

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


