<?php
$headerBadge = $connectionSummary['header_badge'] ?? ['label' => 'Demo Mode', 'class' => 'badge-info'];
$storage = $connectionSummary['storage'] ?? ['label' => 'Local config file', 'class' => 'badge-muted'];
$modeLabel = ucfirst((string) ($connectionSummary['source_mode'] ?? 'demo'));
?>
<div class="page-header page-header-compact sync-settings-header">
    <div class="sync-settings-header-main">
        <h1 class="page-title">Sync/API Settings</h1>
        <span class="badge <?= e($headerBadge['class'] ?? 'badge-info') ?> sync-settings-mode-badge"><?= e($headerBadge['label'] ?? '') ?></span>
    </div>
    <p class="ops-page-subtitle">Connect Lokkisona product/order sync in read-only mode.</p>
</div>

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

<?php if (empty($settings['local_file_writable']) && !empty($canManage)): ?>
<div class="card card-warn-border mb-15">
    <div class="card-header"><h2 class="card-title">Manual setup required</h2></div>
    <div class="card-body">
        <p class="page-description">Save Settings is unavailable on this server. Copy the example file and edit it manually:</p>
        <ol class="sync-settings-note-list">
            <?php foreach (($settings['manual_setup']['steps'] ?? []) as $step): ?>
            <li><?= e($step) ?></li>
            <?php endforeach; ?>
        </ol>
    </div>
</div>
<?php endif; ?>

<div class="card mb-15">
    <div class="card-header"><h2 class="card-title">Current Connection</h2></div>
    <div class="card-body">
        <div class="sync-settings-kv-grid">
            <div class="sync-settings-kv">
                <span class="sync-settings-kv-label">Source Mode</span>
                <span class="sync-settings-kv-value"><span class="badge badge-info"><?= e($modeLabel) ?></span></span>
            </div>
            <div class="sync-settings-kv">
                <span class="sync-settings-kv-label">Connection</span>
                <span class="sync-settings-kv-value">
                    <span class="badge <?= !empty($connectionSummary['connection_ok']) ? 'badge-ok' : 'badge-warn' ?>"><?= !empty($connectionSummary['connection_ok']) ? 'OK' : 'Not ready' ?></span>
                    <span class="sync-settings-kv-meta"><?= e($connectionSummary['connection_message'] ?? '') ?></span>
                </span>
            </div>
            <div class="sync-settings-kv">
                <span class="sync-settings-kv-label">Source URL</span>
                <span class="sync-settings-kv-value"><code class="sync-settings-code"><?= e(($connectionSummary['api_base_url'] ?? '') !== '' ? $connectionSummary['api_base_url'] : '—') ?></code></span>
            </div>
            <div class="sync-settings-kv">
                <span class="sync-settings-kv-label">Product API Route</span>
                <span class="sync-settings-kv-value"><code class="sync-settings-code"><?= e(($connectionSummary['product_route'] ?? '') !== '' ? $connectionSummary['product_route'] : '—') ?></code></span>
            </div>
            <div class="sync-settings-kv">
                <span class="sync-settings-kv-label">Order API Route</span>
                <span class="sync-settings-kv-value"><code class="sync-settings-code"><?= e(($connectionSummary['order_api_route'] ?? '') !== '' ? $connectionSummary['order_api_route'] : '—') ?></code></span>
            </div>
            <div class="sync-settings-kv">
                <span class="sync-settings-kv-label">Product Sync Enabled</span>
                <span class="sync-settings-kv-value"><span class="badge <?= !empty($connectionSummary['product_sync_enabled']) ? 'badge-ok' : 'badge-muted' ?>"><?= !empty($connectionSummary['product_sync_enabled']) ? 'Yes' : 'No' ?></span></span>
            </div>
            <div class="sync-settings-kv">
                <span class="sync-settings-kv-label">Order Sync Enabled</span>
                <span class="sync-settings-kv-value"><span class="badge <?= !empty($connectionSummary['order_sync_enabled']) ? 'badge-ok' : 'badge-muted' ?>"><?= !empty($connectionSummary['order_sync_enabled']) ? 'Yes' : 'No' ?></span></span>
            </div>
            <div class="sync-settings-kv">
                <span class="sync-settings-kv-label">Dispatch Bridge Required</span>
                <span class="sync-settings-kv-value"><span class="badge <?= !empty($connectionSummary['dispatch_bridge_required']) ? 'badge-ok' : 'badge-muted' ?>"><?= !empty($connectionSummary['dispatch_bridge_required']) ? 'Yes' : 'No' ?></span></span>
            </div>
            <div class="sync-settings-kv">
                <span class="sync-settings-kv-label">API Key</span>
                <span class="sync-settings-kv-value">
                    <span class="badge <?= ($connectionSummary['api_key_status'] ?? '') === 'Configured' ? 'badge-ok' : 'badge-warn' ?>"><?= e($connectionSummary['api_key_status'] ?? 'Not configured') ?></span>
                </span>
            </div>
            <div class="sync-settings-kv">
                <span class="sync-settings-kv-label">Storage</span>
                <span class="sync-settings-kv-value"><span class="badge <?= e($storage['class'] ?? 'badge-muted') ?>"><?= e($storage['label'] ?? '') ?></span></span>
            </div>
            <div class="sync-settings-kv">
                <span class="sync-settings-kv-label">Read-Only Lock</span>
                <span class="sync-settings-kv-value"><span class="badge badge-ok">Enabled</span></span>
            </div>
            <div class="sync-settings-kv">
                <span class="sync-settings-kv-label">Max Rows / Page</span>
                <span class="sync-settings-kv-value"><span class="badge badge-muted">20 fixed</span></span>
            </div>
        </div>
    </div>
</div>

<div class="card mb-15 sync-settings-security-card">
    <div class="card-header"><h2 class="card-title">Security</h2></div>
    <div class="card-body">
        <ul class="sync-settings-note-list sync-settings-security-list">
            <li>API key is stored only in <code>config/opencart.local.php</code></li>
            <li>API key is never displayed after save</li>
            <li>No OpenCart write access is used</li>
            <li>Do not commit the local config file to Git</li>
        </ul>
    </div>
</div>

<?php if (!empty($canManage)): ?>
<div class="card mb-15">
    <div class="card-header"><h2 class="card-title">Edit Settings</h2></div>
    <div class="card-body">
        <form method="post" action="<?= e(url('/sync-api-settings/save')) ?>" id="sync-api-settings-form">
            <?= $csrfField ?? '' ?>
            <div class="sync-settings-form-grid">
                <div class="form-group">
                    <label for="source_mode">Source Mode</label>
                    <select id="source_mode" name="source_mode" class="form-control" data-staging-url="<?= e($settings['default_urls']['staging'] ?? '') ?>" data-live-url="<?= e($settings['default_urls']['live'] ?? '') ?>">
                        <?php foreach (['demo' => 'Demo — local sample data', 'staging' => 'Staging — read-only', 'live' => 'Live — read-only'] as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= ($settings['source_mode'] ?? '') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="form-help">Choose Demo for local testing without a live OpenCart connection.</p>
                </div>

                <div class="form-group">
                    <label for="api_base_url">Source URL</label>
                    <input id="api_base_url" name="api_base_url" type="url" class="form-control" value="<?= e($settings['api_base_url'] ?? '') ?>" placeholder="<?= e($settings['default_urls']['staging'] ?? '') ?>">
                    <p class="form-help">Required for Staging/Live modes only.</p>
                </div>

                <div class="form-group sync-settings-form-span-2">
                    <label for="api_key">API Key / Token</label>
                    <?php if (($settings['api_key_status'] ?? '') === 'Configured'): ?>
                    <p class="sync-settings-inline-status"><span class="badge badge-ok">Configured</span></p>
                    <?php endif; ?>
                    <input id="api_key" name="api_key" type="password" class="form-control" value="" autocomplete="new-password" placeholder="Leave blank to keep current key">
                </div>

                <div class="form-group">
                    <label for="product_api_route">Product API Route</label>
                    <input id="product_api_route" name="product_api_route" type="text" class="form-control" value="<?= e($settings['product_api_route'] ?? '') ?>" placeholder="OpenCart warehouse product route">
                </div>

                <div class="form-group">
                    <label for="order_api_route">Order API Route</label>
                    <input id="order_api_route" name="order_api_route" type="text" class="form-control" value="<?= e($settings['order_api_route'] ?? '') ?>" placeholder="api/order">
                </div>

                <div class="form-group">
                    <label>Max Rows Per Page</label>
                    <input type="text" class="form-control" value="20" readonly disabled>
                    <p class="form-help">Fixed at 20 for preview and import safety.</p>
                </div>

                <div class="form-group">
                    <label>Read-Only Mode Lock</label>
                    <div class="sync-settings-lock-row">
                        <span class="badge badge-ok">Enabled</span>
                        <span class="form-help">OpenCart writes are always blocked.</span>
                    </div>
                </div>

                <div class="form-group sync-settings-form-span-2">
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
                </div>
            </div>

            <div class="sync-settings-actions">
                <button type="submit" class="btn btn-primary" <?= empty($settings['local_file_writable']) ? 'disabled' : '' ?>>Save Settings</button>
                <button type="submit" formaction="<?= e(url('/sync-api-settings/test-connection')) ?>" formmethod="post" class="btn btn-secondary">Test Connection</button>
                <button type="submit" formaction="<?= e(url('/sync-api-settings/reset-demo')) ?>" formmethod="post" class="btn btn-outline" <?= empty($settings['local_file_writable']) ? 'disabled' : '' ?>>Reset to Demo</button>
            </div>
            <p class="form-help sync-settings-actions-help">Test Connection is read-only — no product or order import.</p>
        </form>
    </div>
</div>
<?php else: ?>
<div class="card mb-15">
    <div class="card-body">
        <p class="page-description">You have view-only access. An owner can change Sync/API settings.</p>
    </div>
</div>
<?php endif; ?>

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
    if (!mode || !url) return;
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
})();
</script>
