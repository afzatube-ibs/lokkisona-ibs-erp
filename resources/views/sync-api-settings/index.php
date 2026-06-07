<?php
$headerBadge = $connectionSummary['header_badge'] ?? ['label' => 'Demo Mode', 'class' => 'badge-info'];
$storage = $connectionSummary['storage'] ?? ['label' => 'Local config file', 'class' => 'badge-muted'];
$modeLabel = ucfirst((string) ($connectionSummary['source_mode'] ?? 'demo'));
?>
<div class="page-header page-header-compact sync-settings-header">
    <div class="sync-settings-header-row">
        <div class="sync-settings-header-main">
            <div class="sync-settings-title-row">
                <h1 class="page-title">Sync/API Settings</h1>
                <span class="badge <?= e($headerBadge['class'] ?? 'badge-info') ?> sync-settings-mode-badge"><?= e($headerBadge['label'] ?? '') ?></span>
            </div>
            <p class="ops-page-subtitle">Connect Lokkisona product/order sync in read-only mode.</p>
        </div>
        <div class="sync-settings-header-actions">
            <a href="<?= e(url('/sync-preview')) ?>" class="btn btn-primary btn-sm">Open Sync Preview</a>
            <a href="<?= e(url('/product-control')) ?>" class="btn btn-secondary btn-sm">Product Control</a>
        </div>
    </div>
</div>

<?php
view('partials.ops-safety-strip', [
    'message' => 'Read-only OpenCart sync only · Max 20 rows per page · API key stored in local config file · No OpenCart writes',
]);
?>

<?php view('partials.flash-messages', ['flashSuccess' => $flashSuccess ?? null, 'flashError' => $flashError ?? null]); ?>

<?php if (!empty($settings['warnings'])): ?>
<div class="card card-warn-border mb-15">
    <div class="card-header"><h2 class="card-title">Attention</h2></div>
    <div class="card-body">
        <ul class="sync-settings-note-list">
            <?php foreach ($settings['warnings'] as $warning): ?>
            <li><?= e($warning) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
<?php endif; ?>

<div class="kpi-grid kpi-grid-inline mb-15 sync-settings-kpi-row">
    <div class="kpi-card kpi-accent-info">
        <span class="kpi-label">Mode</span>
        <span class="kpi-value kpi-value-sm"><?= e($headerBadge['label'] ?? $modeLabel) ?></span>
    </div>
    <div class="kpi-card <?= !empty($connectionSummary['connection_ok']) ? 'kpi-accent-success' : 'kpi-accent-warn' ?>">
        <span class="kpi-label">Connection</span>
        <span class="kpi-value kpi-value-sm"><?= !empty($connectionSummary['connection_ok']) ? 'OK' : 'Not ready' ?></span>
        <span class="kpi-sub"><?= e($connectionSummary['connection_message'] ?? '') ?></span>
    </div>
    <div class="kpi-card <?= ($connectionSummary['api_key_status'] ?? '') === 'Configured' ? 'kpi-accent-success' : 'kpi-accent-warn' ?>">
        <span class="kpi-label">API Key</span>
        <span class="kpi-value kpi-value-sm"><?= e($connectionSummary['api_key_status'] ?? 'Not configured') ?></span>
    </div>
    <div class="kpi-card kpi-accent-muted">
        <span class="kpi-label">Storage</span>
        <span class="kpi-value kpi-value-sm"><?= e($storage['label'] ?? '') ?></span>
    </div>
</div>

<div class="sync-settings-layout mb-15">
    <div class="card sync-settings-status-card">
        <div class="card-header sync-settings-status-header">
            <div>
                <h2 class="card-title">Current Connection</h2>
                <p class="page-description mb-0">Live read-only sync status from saved settings.</p>
            </div>
            <div class="workflow-chip-row sync-settings-chip-row">
                <span class="workflow-chip is-active">Read-only sync</span>
                <span class="workflow-chip">Max 20 / page</span>
                <span class="workflow-chip">Local config file</span>
            </div>
        </div>
        <div class="card-body">
            <dl class="info-list sync-settings-info-list">
                <div class="info-row">
                    <dt>Source Mode</dt>
                    <dd><span class="badge badge-info"><?= e($modeLabel) ?></span></dd>
                </div>
                <div class="info-row">
                    <dt>Connection</dt>
                    <dd>
                        <span class="badge <?= !empty($connectionSummary['connection_ok']) ? 'badge-ok' : 'badge-warn' ?>"><?= !empty($connectionSummary['connection_ok']) ? 'OK' : 'Not ready' ?></span>
                        <span class="sync-settings-kv-meta"><?= e($connectionSummary['connection_message'] ?? '') ?></span>
                    </dd>
                </div>
                <div class="info-row">
                    <dt>Source URL</dt>
                    <dd><code class="sync-settings-code"><?= e(($connectionSummary['api_base_url'] ?? '') !== '' ? $connectionSummary['api_base_url'] : '—') ?></code></dd>
                </div>
                <div class="info-row">
                    <dt>Product API Route</dt>
                    <dd><code class="sync-settings-code"><?= e(($connectionSummary['product_route'] ?? '') !== '' ? $connectionSummary['product_route'] : '—') ?></code></dd>
                </div>
                <div class="info-row">
                    <dt>Order API Route</dt>
                    <dd><code class="sync-settings-code"><?= e(($connectionSummary['order_api_route'] ?? '') !== '' ? $connectionSummary['order_api_route'] : '—') ?></code></dd>
                </div>
                <div class="info-row">
                    <dt>Product Sync</dt>
                    <dd><span class="badge <?= !empty($connectionSummary['product_sync_enabled']) ? 'badge-ok' : 'badge-muted' ?>"><?= !empty($connectionSummary['product_sync_enabled']) ? 'Enabled' : 'Off' ?></span></dd>
                </div>
                <div class="info-row">
                    <dt>Order Sync</dt>
                    <dd><span class="badge <?= !empty($connectionSummary['order_sync_enabled']) ? 'badge-ok' : 'badge-muted' ?>"><?= !empty($connectionSummary['order_sync_enabled']) ? 'Enabled' : 'Off' ?></span></dd>
                </div>
                <div class="info-row">
                    <dt>Dispatch Bridge</dt>
                    <dd><span class="badge <?= !empty($connectionSummary['dispatch_bridge_required']) ? 'badge-ok' : 'badge-muted' ?>"><?= !empty($connectionSummary['dispatch_bridge_required']) ? 'Required' : 'Off' ?></span></dd>
                </div>
                <div class="info-row">
                    <dt>API Key</dt>
                    <dd><span class="badge <?= ($connectionSummary['api_key_status'] ?? '') === 'Configured' ? 'badge-ok' : 'badge-warn' ?>"><?= e($connectionSummary['api_key_status'] ?? 'Not configured') ?></span></dd>
                </div>
                <div class="info-row">
                    <dt>Storage</dt>
                    <dd><span class="badge <?= e($storage['class'] ?? 'badge-muted') ?>"><?= e($storage['label'] ?? '') ?></span></dd>
                </div>
                <div class="info-row">
                    <dt>Read-Only Lock</dt>
                    <dd><span class="badge badge-ok">Enabled</span></dd>
                </div>
                <div class="info-row">
                    <dt>Max Rows / Page</dt>
                    <dd><span class="badge badge-muted">20 fixed</span></dd>
                </div>
            </dl>
        </div>
    </div>

    <?php if (!empty($canManage)): ?>
    <div class="card sync-settings-form-card">
        <div class="card-header"><h2 class="card-title">Edit Settings</h2></div>
        <div class="card-body">
            <?php if (empty($settings['local_file_writable'])): ?>
            <div class="sync-settings-manual-inline card-warn-border">
                <p class="page-description"><strong>Manual setup required.</strong> Save Settings is disabled on this server. Copy <code><?= e($settings['example_file_path'] ?? '') ?></code> to <code><?= e($settings['local_file_path'] ?? '') ?></code>, edit via SSH/FTP, then use Test Connection.</p>
            </div>
            <?php endif; ?>

            <form method="post" action="<?= e(url('/sync-api-settings/save')) ?>" id="sync-api-settings-form">
                <?= $csrfField ?? '' ?>

                <p class="sync-settings-section-label">Connection</p>
                <div class="sync-settings-form-grid">
                    <div class="form-group">
                        <label for="source_mode">Source Mode</label>
                        <select id="source_mode" name="source_mode" class="form-input" data-staging-url="<?= e($settings['default_urls']['staging'] ?? '') ?>" data-live-url="<?= e($settings['default_urls']['live'] ?? '') ?>">
                            <?php foreach (['demo' => 'Demo — local sample data', 'staging' => 'Staging — read-only', 'live' => 'Live — read-only'] as $value => $label): ?>
                            <option value="<?= e($value) ?>" <?= ($settings['source_mode'] ?? '') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="form-help">Demo uses local sample data with no live OpenCart connection.</p>
                    </div>
                    <div class="form-group">
                        <label for="api_base_url">Source URL</label>
                        <input id="api_base_url" name="api_base_url" type="url" class="form-input" value="<?= e($settings['api_base_url'] ?? '') ?>" placeholder="<?= e($settings['default_urls']['staging'] ?? '') ?>">
                        <p class="form-help">Required for Staging and Live modes.</p>
                    </div>
                    <div class="form-group">
                        <label for="product_api_route">Product API Route</label>
                        <input id="product_api_route" name="product_api_route" type="text" class="form-input" value="<?= e($settings['product_api_route'] ?? '') ?>" placeholder="OpenCart warehouse product route">
                    </div>
                    <div class="form-group">
                        <label for="order_api_route">Order API Route</label>
                        <input id="order_api_route" name="order_api_route" type="text" class="form-input" value="<?= e($settings['order_api_route'] ?? '') ?>" placeholder="api/order">
                    </div>
                </div>

                <p class="sync-settings-section-label">API credentials</p>
                <div class="sync-settings-form-grid">
                    <div class="form-group sync-settings-form-span-2">
                        <label for="api_key">API Key / Token</label>
                        <?php if (($settings['api_key_status'] ?? '') === 'Configured'): ?>
                        <p class="sync-settings-inline-status"><span class="badge badge-ok">Configured</span></p>
                        <?php endif; ?>
                        <input id="api_key" name="api_key" type="password" class="form-input" value="" autocomplete="new-password" placeholder="Leave blank to keep current key">
                    </div>
                    <div class="form-group">
                        <label>Max Rows Per Page</label>
                        <input type="text" class="form-input" value="20" readonly disabled>
                        <p class="form-help">Fixed at 20 for preview and import safety.</p>
                    </div>
                    <div class="form-group">
                        <label>Read-Only Mode Lock</label>
                        <div class="sync-settings-lock-row">
                            <span class="badge badge-ok">Enabled</span>
                            <span class="form-help">OpenCart writes are always blocked.</span>
                        </div>
                    </div>
                </div>

                <p class="sync-settings-section-label">Sync toggles</p>
                <div class="sync-settings-switch-grid">
                    <label class="sync-settings-switch">
                        <input type="checkbox" name="product_sync_enabled" value="1" <?= !empty($settings['product_sync_enabled']) ? 'checked' : '' ?>>
                        <span>Product Sync Enabled</span>
                    </label>
                    <label class="sync-settings-switch">
                        <input type="checkbox" name="order_sync_enabled" value="1" <?= !empty($settings['order_sync_enabled']) ? 'checked' : '' ?>>
                        <span>Order Sync Enabled</span>
                    </label>
                    <label class="sync-settings-switch">
                        <input type="checkbox" name="dispatch_bridge_required" value="1" <?= !empty($settings['dispatch_bridge_required']) ? 'checked' : '' ?>>
                        <span>Dispatch Bridge Required</span>
                    </label>
                </div>
                <p class="form-help">Bridge filter keeps <code>from_warehouse = 1</code> products only.</p>

                <div class="sync-settings-actions">
                    <button type="submit" class="btn btn-primary" <?= empty($settings['local_file_writable']) ? 'disabled' : '' ?>>Save Settings</button>
                    <button type="submit" formaction="<?= e(url('/sync-api-settings/test-connection')) ?>" formmethod="post" class="btn btn-secondary">Test Connection</button>
                    <button type="submit" formaction="<?= e(url('/sync-api-settings/reset-demo')) ?>" formmethod="post" class="btn btn-outline sync-settings-reset-btn" <?= empty($settings['local_file_writable']) ? 'disabled' : '' ?>>Reset to Demo</button>
                </div>
                <p class="form-help sync-settings-actions-help">Test Connection is read-only — no product or order import.</p>
            </form>

            <div class="sync-settings-security-inline">
                <strong>Security:</strong>
                API key stored in <code>config/opencart.local.php</code> only · never displayed after save · no OpenCart write access · do not commit local config to Git
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="card">
        <div class="card-body">
            <p class="page-description">You have view-only access. An owner can change Sync/API settings.</p>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php
view('partials.product-sync-reset-form', [
    'canManage' => !empty($canResetProductSync),
    'productWriteGateReady' => $productWriteGateReady ?? false,
    'csrfField' => $csrfField ?? '',
    'redirectTo' => '/sync-api-settings',
]);
?>

<details class="planning-collapsible mb-15">
    <summary class="planning-collapsible-summary">Setup Guide</summary>
    <div class="planning-collapsible-body">
        <ul class="sync-settings-note-list">
            <li><strong>Demo:</strong> no setup required — uses local sample products and orders.</li>
            <li><strong>Staging:</strong> use <code><?= e($settings['default_urls']['staging'] ?? '') ?></code></li>
            <li><strong>Live:</strong> use <code><?= e($settings['default_urls']['live'] ?? '') ?></code></li>
            <li>Product route and Order route are required for sync preview.</li>
            <li>Run <strong>Test Connection</strong> before using Sync Preview.</li>
        </ul>
    </div>
</details>

<details class="planning-collapsible mb-15">
    <summary class="planning-collapsible-summary">Developer Notes</summary>
    <div class="planning-collapsible-body">
        <p class="page-description"><?= e($settings['future_plan']['summary'] ?? '') ?></p>
        <ul class="sync-settings-note-list">
            <?php foreach (($settings['future_plan']['points'] ?? []) as $point): ?>
            <li><?= e($point) ?></li>
            <?php endforeach; ?>
        </ul>
        <p class="page-description">Example template: <code><?= e($settings['example_file_path'] ?? '') ?></code></p>
    </div>
</details>

<script>
(function () {
    var mode = document.getElementById('source_mode');
    var url = document.getElementById('api_base_url');
    if (mode && url) {
        mode.addEventListener('change', function () {
            var v = mode.value;
            if (v === 'staging' && mode.dataset.stagingUrl) {
                url.value = mode.dataset.stagingUrl;
            } else if (v === 'live' && mode.dataset.liveUrl) {
                url.value = mode.dataset.liveUrl;
            } else if (v === 'demo') {
                url.value = '';
            }
        });
    }

    var resetBtn = document.querySelector('.sync-settings-reset-btn');
    if (resetBtn) {
        resetBtn.addEventListener('click', function (e) {
            if (!window.confirm('Switch back to Demo mode with local sample data?')) {
                e.preventDefault();
            }
        });
    }
})();
</script>
