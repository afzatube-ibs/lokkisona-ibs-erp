<div class="page-header">
    <h1 class="page-title">Status Mapping</h1>
    <p class="page-description">Lokkisona OpenCart order status mapping — v<?= e($appVersion) ?> — <?= e($appReleaseLabel ?? '') ?>. Import orders by <strong>mapped OC status only</strong> — not by product mapping. First import sets the mapped SFM status (New, Accepted, Packed, etc.). Re-sync updates snapshot fields only and never overwrites SFM workflow status. Apply migrations 0004 and 0015 manually first.</p>
</div>

<?php view('partials.flash-messages', ['flashSuccess' => $flashSuccess ?? null, 'flashError' => $flashError ?? null]); ?>

<?php
view('partials.write-gate-warning', [
    'writeGateReady' => $writeGateReady ?? false,
    'writeGate' => $writeGate ?? [],
    'writeGateMessage' => null,
]);
?>

<?php if (!empty($syncSummary)): ?>
<div class="kpi-grid kpi-grid-inline mb-15">
    <div class="kpi-card kpi-accent-muted">
        <span class="kpi-label">Connection</span>
        <span class="kpi-value kpi-value-sm"><?= !empty($syncSummary['connection_ok']) ? 'Connected' : 'Not tested' ?></span>
    </div>
    <div class="kpi-card kpi-accent-info">
        <span class="kpi-label">Products API</span>
        <span class="kpi-value kpi-value-sm"><?= e((string) ($syncSummary['product_api_status'] ?? '—')) ?></span>
    </div>
    <div class="kpi-card kpi-accent-primary">
        <span class="kpi-label">Orders API</span>
        <span class="kpi-value kpi-value-sm"><?= e((string) ($syncSummary['order_api_status'] ?? '—')) ?></span>
    </div>
    <div class="kpi-card kpi-accent-success">
        <span class="kpi-label">Mapped Statuses</span>
        <span class="kpi-value"><?= e((string) ($mappedStatusCount ?? 0)) ?></span>
    </div>
    <div class="kpi-card kpi-accent-warn">
        <span class="kpi-label">Last Order Sync</span>
        <span class="kpi-value kpi-value-sm"><?= e((string) ($syncSummary['last_order_sync_at'] ?? '—')) ?></span>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($canManage) && !empty($writeGateReady)): ?>
<div class="card mb-15">
    <div class="card-header"><h2 class="card-title">Status Mapping Writes</h2></div>
    <div class="card-body">
        <form method="post" action="<?= e(url('/status-mapping/create')) ?>" class="form-grid">
            <?= $csrfField ?? '' ?>
            <label>Business Source ID<input type="number" name="business_source_id" value="<?= e((string) ($defaultBusinessSourceId ?? 1)) ?>" min="1" required></label>
            <label>Origin / OpenCart Order Status<input type="text" name="source_status" placeholder="Follow Up or status id" required></label>
            <label>IBS Initial Fulfillment Status
                <select name="ibs_status" required>
                    <?php foreach ($initialStatusOptions ?? [] as $option): ?>
                    <option value="<?= e((string) ($option['code'] ?? '')) ?>"><?= e((string) ($option['label'] ?? '')) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Notes<textarea name="notes" rows="2" placeholder="e.g. Follow Up imports as New Order only at first sync"></textarea></label>
            <label>Workflow Group<input type="text" name="workflow_group" value="workflow"></label>
            <button type="submit" class="btn btn-primary">Save Mapping</button>
        </form>
        <form method="post" action="<?= e(url('/status-mapping/seed-defaults')) ?>" style="margin-top:1rem;">
            <?= $csrfField ?? '' ?>
            <input type="hidden" name="business_source_id" value="<?= e((string) ($defaultBusinessSourceId ?? 1)) ?>">
            <button type="submit" class="btn btn-secondary">Seed Lokkisona Defaults</button>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($mappingRows)): ?>
<div class="card mb-15">
    <div class="card-header"><h2 class="card-title">Active Mappings</h2></div>
    <div class="card-body card-body-flush">
        <div class="table-scroll">
            <table class="data-table">
                <thead><tr><th>ID</th><th>Source</th><th>Origin Status</th><th>IBS Initial</th><th>Enabled</th><th>Notes</th><th>Last Match</th><th>Last Synced</th><th>Action</th></tr></thead>
                <tbody>
                    <?php foreach ($mappingRows as $row): ?>
                    <tr>
                        <td><?= e((string) ($row['status_mapping_id'] ?? '')) ?></td>
                        <td><?= e((string) ($row['business_source_id'] ?? '')) ?></td>
                        <td><?= e((string) ($row['source_status'] ?? '')) ?></td>
                        <td><?= e((string) ($row['ibs_status'] ?? '')) ?></td>
                        <td><?= !empty($row['is_active']) ? 'Yes' : 'No' ?></td>
                        <td class="cell-detail"><?= e((string) ($row['notes'] ?? '—')) ?></td>
                        <td><?= e((string) ($row['last_matched_count'] ?? '0')) ?></td>
                        <td><?= e((string) ($row['last_synced_at'] ?? '—')) ?></td>
                        <td>
                            <?php if (!empty($canManage) && !empty($writeGateReady)): ?>
                            <form method="post" action="<?= e(url('/status-mapping/toggle')) ?>" style="display:inline;">
                                <?= $csrfField ?? '' ?>
                                <input type="hidden" name="status_mapping_id" value="<?= e((string) ($row['status_mapping_id'] ?? '')) ?>">
                                <input type="hidden" name="is_active" value="<?= !empty($row['is_active']) ? '0' : '1' ?>">
                                <button type="submit" class="btn btn-sm"><?= !empty($row['is_active']) ? 'Deactivate' : 'Activate' ?></button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (empty($writeGateReady)): ?>
<div class="card mb-15">
    <div class="card-header"><h2 class="card-title">Migration Required — Status Mapping</h2></div>
    <div class="card-body">
        <p class="page-description">Apply <strong>migration 0004</strong> manually in your SQL client (backup first). The <code>ibs_status_mappings</code> table is defined in <code>database/migrations/0004_status_mapping_sync_preview.sql</code> (uses <code>IF NOT EXISTS</code> — safe to run when the table is missing).</p>
        <p class="page-description">For <a href="<?= e(url('/sync-preview')) ?>">Sync Preview</a>, apply the full 0004 file (sync preview, import, and log tables). Copy SQL from <a href="<?= e(url('/migration-files')) ?>">Migration Files</a>.</p>
        <p class="page-description"><a href="<?= e(url('/dev-db-activation')) ?>">Open Dev DB Activation</a> · <a href="<?= e(url('/migration-files')) ?>">Migration Files</a></p>
    </div>
</div>
<?php endif; ?>

<?php if (empty($writeGateReady) && ($appEnv ?? 'local') !== 'production'): ?>
<details class="planning-collapsible">
    <summary class="planning-collapsible-summary">Mapping Planning Foundation (developer reference)</summary>
    <div class="planning-collapsible-body">
<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Current Mapping Context</h2>
        </div>
        <div class="card-body">
            <dl class="info-list">
                <div class="info-row">
                    <dt>Primary Supplier</dt>
                    <dd><?= e($currentContext['supplier']) ?></dd>
                </div>
                <div class="info-row">
                    <dt>Primary Source</dt>
                    <dd><?= e($currentContext['source']) ?></dd>
                </div>
            </dl>
            <p class="page-description"><?= e($currentContext['summary']) ?></p>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Status Mapping Purpose</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($purpose as $item): ?>
                    <li><?= e($item) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Planned Mapping Types</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($mappingTypes as $type): ?>
                    <li><?= e($type) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">IBS Workflow Mapping Examples</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($ibsWorkflowExamples as $example): ?>
                    <li><?= e($example) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Return Mapping Examples</h2>
    </div>
    <div class="card-body card-body-flush">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Return Type</th>
                    <th>Rule</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($returnMappingExamples as $example): ?>
                <tr>
                    <td class="cell-name"><?= e($example['type']) ?></td>
                    <td class="cell-detail"><?= e($example['rule']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card-grid">
    <?php foreach ([$workflowMappingRule, $supplierReturnRule, $lokkisonaReturnRule, $courierMappingRule] as $section): ?>
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><?= e($section['title']) ?></h2>
        </div>
        <div class="card-body">
            <p><?= e($section['summary']) ?></p>
            <ul class="feature-list">
                <?php foreach ($section['points'] as $point): ?>
                    <li><?= e($point) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="card-grid">
    <?php foreach ([$independentWorkflowRule, $skipMissingRule, $unmappedSafetyRule, $testSyncPreviewRule] as $section): ?>
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><?= e($section['title']) ?></h2>
        </div>
        <div class="card-body">
            <p><?= e($section['summary']) ?></p>
            <ul class="feature-list">
                <?php foreach ($section['points'] as $point): ?>
                    <li><?= e($point) ?></li>
                <?php endforeach; ?>
            </ul>
            <?php if ($section['title'] === 'Unmapped Status Safety Rule'): ?>
            <p class="page-description">Sync Preview must read mapping first and block unmapped statuses. See <a href="<?= e(url('/sync-preview')) ?>">Sync Preview planning foundation</a>.</p>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Performance / Sync Safety Rules</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($performanceSyncRules as $rule): ?>
                    <li><?= e($rule) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><?= e($manualOfflineRule['title']) ?></h2>
        </div>
        <div class="card-body">
            <p><?= e($manualOfflineRule['summary']) ?></p>
            <ul class="feature-list">
                <?php foreach ($manualOfflineRule['points'] as $point): ?>
                    <li><?= e($point) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Future Mapping Settings Plan</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($futureMappingSettings as $item): ?>
                    <li><?= e($item) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Planned Order / Sync List Columns</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($plannedOrderSyncColumns as $column): ?>
                    <li><?= e($column) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Planned Status Mapping Fields</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($plannedMappingFields as $field): ?>
                    <li><?= e($field) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Planned Sync Preview Fields</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($plannedSyncPreviewFields as $field): ?>
                    <li><?= e($field) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Planned Sync Log Fields</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($plannedSyncLogFields as $field): ?>
                    <li><?= e($field) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Current Access Mode</h2>
        </div>
        <div class="card-body">
            <dl class="info-list">
                <div class="info-row">
                    <dt>Mode</dt>
                    <dd><?= e($accessMode['mode']) ?></dd>
                </div>
                <div class="info-row">
                    <dt>Current Role</dt>
                    <dd><?= e($accessMode['role']) ?></dd>
                </div>
            </dl>
            <p class="page-description">Owner and admin can view Status Mapping planning now. Staff may view later based on permission. Supplier role does not manage global status mapping.</p>
        </div>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Manual Migration Required</h2>
        </div>
        <div class="card-body">
            <p>No status mapping, sync preview, or sync log tables are created automatically and no mapping/sync records are written in this release.</p>
            <p class="page-description">Real status mapping and sync data requires an owner/admin-reviewed manual migration before activation. No table creation, alteration, or schema repair runs on page load. OpenCart is not connected in this release.</p>
        </div>
    </div>
</div>

    </div>
</details>
<?php endif; ?>
