<?php
$settings = $settings ?? [];
$connectionSummary = $connectionSummary ?? [];
$apiKeyMask = (string) ($connectionSummary['api_key_mask'] ?? $settings['api_key_mask'] ?? '');
$sourceMode = (string) ($settings['source_mode'] ?? 'demo');
$sourceModeLabel = match ($sourceMode) {
    'staging' => 'Staging',
    'live' => 'Live',
    default => 'Demo',
};
$sourceUrl = trim((string) ($settings['api_base_url'] ?? ''));
$displayUrl = $sourceUrl !== '' ? $sourceUrl : '—';
$tokenDisplay = $apiKeyMask !== '' ? $apiKeyMask : 'Not configured';
?>
<div class="card sync-hub-card">
    <div class="card-body">
        <?php if (!empty($canSyncHub)): ?>
        <?php if (empty($settings['local_file_writable'])): ?>
        <p class="page-description card-warn-border p-10 mb-15"><strong>Manual setup required.</strong> Copy <code><?= e($settings['example_file_path'] ?? '') ?></code> to <code><?= e($settings['local_file_path'] ?? '') ?></code> on the server.</p>
        <?php endif; ?>

        <div class="sync-hub-connection-view" data-sync-hub-connection-view>
            <dl class="sync-hub-connection-summary">
                <div class="sync-hub-summary-row">
                    <dt>Source Mode</dt>
                    <dd><?= e($sourceModeLabel) ?></dd>
                </div>
                <div class="sync-hub-summary-row">
                    <dt>Source URL</dt>
                    <dd><?= e($displayUrl) ?></dd>
                </div>
                <div class="sync-hub-summary-row">
                    <dt>API Token</dt>
                    <dd><?= e($tokenDisplay) ?></dd>
                </div>
                <div class="sync-hub-summary-row">
                    <dt>Product API</dt>
                    <dd><?= e((string) ($connectionSummary['product_api_status'] ?? '—')) ?></dd>
                </div>
                <div class="sync-hub-summary-row">
                    <dt>Order API</dt>
                    <dd><?= e((string) ($connectionSummary['order_api_status'] ?? '—')) ?></dd>
                </div>
                <div class="sync-hub-summary-row">
                    <dt>Queue API</dt>
                    <dd><?= e((string) ($connectionSummary['queue_api_status'] ?? '—')) ?></dd>
                </div>
                <div class="sync-hub-summary-row">
                    <dt>Dispatch Bridge</dt>
                    <dd><?= e((string) ($connectionSummary['bridge_status'] ?? '—')) ?></dd>
                </div>
                <div class="sync-hub-summary-row">
                    <dt>Max Rows</dt>
                    <dd>20</dd>
                </div>
            </dl>
            <div class="sync-settings-actions">
                <button type="button" class="btn btn-primary" data-sync-hub-connection-edit <?= empty($settings['local_file_writable']) ? 'disabled' : '' ?>>Edit Settings</button>
                <form method="post" action="<?= e(url('/sync-api-settings/test-connection')) ?>" class="sync-hub-inline-form">
                    <?= $csrfField ?? '' ?>
                    <input type="hidden" name="tab" value="connection">
                    <button type="submit" class="btn btn-secondary">Test Connection</button>
                </form>
            </div>
        </div>

        <div class="sync-hub-connection-edit" data-sync-hub-connection-panel hidden>
            <form method="post" action="<?= e(url('/sync-api-settings/save')) ?>" id="sync-hub-connection-form">
                <?= $csrfField ?? '' ?>
                <input type="hidden" name="tab" value="connection">
                <input type="hidden" name="product_api_route" value="<?= e($settings['product_api_route'] ?? '') ?>">
                <input type="hidden" name="order_api_route" value="<?= e($settings['order_api_route'] ?? '') ?>">
                <?php if (!empty($settings['product_sync_enabled'])): ?><input type="hidden" name="product_sync_enabled" value="1"><?php endif; ?>
                <?php if (!empty($settings['order_sync_enabled'])): ?><input type="hidden" name="order_sync_enabled" value="1"><?php endif; ?>
                <?php if (!empty($settings['dispatch_bridge_required'])): ?><input type="hidden" name="dispatch_bridge_required" value="1"><?php endif; ?>
                <div class="sync-settings-form-grid">
                    <div class="form-group">
                        <label for="source_mode">Source Mode</label>
                        <select id="source_mode" name="source_mode" class="form-input" data-staging-url="<?= e($settings['default_urls']['staging'] ?? '') ?>" data-live-url="<?= e($settings['default_urls']['live'] ?? '') ?>">
                            <?php foreach (['demo' => 'Demo', 'staging' => 'Staging', 'live' => 'Live'] as $value => $label): ?>
                            <option value="<?= e($value) ?>" <?= ($settings['source_mode'] ?? '') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="api_base_url">Source URL</label>
                        <input id="api_base_url" name="api_base_url" type="url" class="form-input" value="<?= e($settings['api_base_url'] ?? '') ?>" placeholder="<?= e($settings['default_urls']['staging'] ?? '') ?>">
                    </div>
                    <div class="form-group sync-settings-form-span-2">
                        <label for="api_key">API Token</label>
                        <?php if ($apiKeyMask !== ''): ?><p class="sync-settings-saved-hint"><?= e($apiKeyMask) ?></p><?php endif; ?>
                        <input id="api_key" name="api_key" type="password" class="form-input" value="" autocomplete="new-password" placeholder="<?= e($apiKeyMask !== '' ? 'Leave blank to keep current token' : 'Enter API token') ?>">
                    </div>
                </div>
                <div class="sync-hub-readonly-fields mb-15">
                    <p class="form-help mb-0"><strong>Read-only:</strong> Product route <code><?= e($settings['product_api_route'] ?? '') ?></code> · Order route <code><?= e($settings['order_api_route'] ?? '') ?></code> · Queue route <code><?= e($settings['order_queue_api_route'] ?? 'api/ibs/order_queue_statuses') ?></code> · Max rows 20 · Dispatch bridge <?= !empty($settings['dispatch_bridge_required']) ? 'required' : 'off' ?></p>
                </div>
                <div class="sync-settings-actions">
                    <button type="submit" class="btn btn-primary" <?= empty($settings['local_file_writable']) ? 'disabled' : '' ?>>Save Connection</button>
                    <button type="button" class="btn btn-secondary" data-sync-hub-connection-cancel>Cancel</button>
                </div>
            </form>
        </div>
        <?php else: ?>
        <p class="page-description">View-only. Sync Hub permission required to edit connection settings.</p>
        <?php endif; ?>
    </div>
</div>
