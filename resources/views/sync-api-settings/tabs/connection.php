<?php
$settings = $settings ?? [];
$connectionSummary = $connectionSummary ?? [];
$apiKeyMask = (string) ($connectionSummary['api_key_mask'] ?? $settings['api_key_mask'] ?? '');
?>
<div class="card sync-hub-card">
    <div class="card-body">
        <?php if (!empty($canSyncHub)): ?>
        <?php if (empty($settings['local_file_writable'])): ?>
        <p class="page-description card-warn-border p-10 mb-15"><strong>Manual setup required.</strong> Copy <code><?= e($settings['example_file_path'] ?? '') ?></code> to <code><?= e($settings['local_file_path'] ?? '') ?></code> on the server.</p>
        <?php endif; ?>
        <form method="post" action="<?= e(url('/sync-api-settings/save')) ?>" id="sync-hub-connection-form">
            <?= $csrfField ?? '' ?>
            <input type="hidden" name="tab" value="connection">
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
                <div class="form-group">
                    <label for="product_api_route">Product API Route</label>
                    <input id="product_api_route" name="product_api_route" type="text" class="form-input" value="<?= e($settings['product_api_route'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="order_api_route">Order API Route</label>
                    <input id="order_api_route" name="order_api_route" type="text" class="form-input" value="<?= e($settings['order_api_route'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="order_queue_api_route">Queue Status API Route</label>
                    <input id="order_queue_api_route" type="text" class="form-input" value="<?= e($settings['order_queue_api_route'] ?? 'api/ibs/order_queue_statuses') ?>" readonly disabled>
                </div>
                <div class="form-group">
                    <label>Max Rows</label>
                    <input type="text" class="form-input" value="20" readonly disabled>
                </div>
                <div class="form-group sync-settings-form-span-2">
                    <label for="api_key">API Token</label>
                    <?php if ($apiKeyMask !== ''): ?><p class="sync-settings-saved-hint"><?= e($apiKeyMask) ?></p><?php endif; ?>
                    <input id="api_key" name="api_key" type="password" class="form-input" value="" autocomplete="new-password" placeholder="<?= e($apiKeyMask !== '' ? 'Leave blank to keep current token' : 'Enter API token') ?>">
                </div>
            </div>
            <div class="sync-settings-switch-grid mb-15">
                <label class="sync-settings-switch"><input type="checkbox" name="product_sync_enabled" value="1" <?= !empty($settings['product_sync_enabled']) ? 'checked' : '' ?>><span>Product Sync</span></label>
                <label class="sync-settings-switch"><input type="checkbox" name="order_sync_enabled" value="1" <?= !empty($settings['order_sync_enabled']) ? 'checked' : '' ?>><span>Order Sync</span></label>
                <label class="sync-settings-switch"><input type="checkbox" name="dispatch_bridge_required" value="1" <?= !empty($settings['dispatch_bridge_required']) ? 'checked' : '' ?>><span>Dispatch Bridge</span></label>
            </div>
            <div class="sync-settings-actions">
                <button type="submit" class="btn btn-primary" <?= empty($settings['local_file_writable']) ? 'disabled' : '' ?>>Save Connection</button>
                <button type="submit" formaction="<?= e(url('/sync-api-settings/test-connection')) ?>" formmethod="post" class="btn btn-secondary">Test Connection</button>
            </div>
        </form>
        <?php else: ?>
        <p class="page-description">View-only. Sync Hub permission required to edit connection settings.</p>
        <?php endif; ?>
    </div>
</div>

<div class="sync-hub-dirty-bar" data-sync-hub-dirty-bar hidden>
    <span class="form-help">Unsaved connection changes</span>
    <button type="submit" form="sync-hub-connection-form" class="btn btn-primary btn-sm">Save</button>
    <button type="button" class="btn btn-secondary btn-sm" data-sync-hub-discard>Discard</button>
</div>
