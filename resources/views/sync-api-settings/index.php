<?php
$headerBadge = $connectionSummary['header_badge'] ?? ['label' => 'Demo Mode', 'class' => 'badge-info'];
$activeTab = $activeTab ?? 'connection';
$mappingAttentionCount = (int) ($mappingAttentionCount ?? 0);
$mappingQueueLoaded = !empty($mappingQueueLoaded);
$connectionChip = is_array($connectionChip ?? null) ? $connectionChip : ['label' => 'Not tested', 'class' => 'is-untested'];
$lastTestAt = trim((string) ($connectionSummary['last_connection_test_at'] ?? ''));
?>
<div>
    <div class="sync-hub-sticky-header">
        <div class="sync-hub-title-row">
            <h1 class="page-title">Sync & Mapping</h1>
            <span class="badge <?= e($headerBadge['class'] ?? 'badge-info') ?>"><?= e($headerBadge['label'] ?? '') ?></span>
            <div class="sync-hub-meta">
                <span class="sync-hub-connection-chip <?= e((string) ($connectionChip['class'] ?? '')) ?>">
                    <span class="sync-hub-connection-chip-label"><?= e((string) ($connectionChip['label'] ?? '')) ?></span>
                </span>
                <?php if ($lastTestAt !== ''): ?>
                <span class="form-help">Last tested: <?= e($lastTestAt) ?></span>
                <?php endif; ?>
            </div>
        </div>
        <p class="ops-page-subtitle mb-0">OpenCart sends queue orders. Map which statuses create a <strong>New</strong> order. After <strong>Dispatched</strong>, OpenCart can close as <strong>Delivered</strong> or <strong>Returned</strong>.</p>

        <ul class="sync-hub-tabs" role="tablist">
            <li><button type="button" class="sync-hub-tab <?= $activeTab === 'connection' ? 'is-active' : '' ?>" data-sync-hub-tab="connection" role="tab" aria-selected="<?= $activeTab === 'connection' ? 'true' : 'false' ?>">Connection</button></li>
            <li>
                <button type="button" class="sync-hub-tab <?= $activeTab === 'mapping' ? 'is-active' : '' ?>" data-sync-hub-tab="mapping" role="tab" aria-selected="<?= $activeTab === 'mapping' ? 'true' : 'false' ?>">
                    Status Mapping
                    <?php if ($mappingAttentionCount > 0): ?>
                    <span class="sync-hub-tab-badge"><?= e((string) $mappingAttentionCount) ?></span>
                    <?php elseif ($mappingQueueLoaded): ?>
                    <span class="sync-hub-tab-badge is-done" aria-label="All queue statuses mapped">✓</span>
                    <?php endif; ?>
                </button>
            </li>
            <li><button type="button" class="sync-hub-tab <?= $activeTab === 'products' ? 'is-active' : '' ?>" data-sync-hub-tab="products" role="tab" aria-selected="<?= $activeTab === 'products' ? 'true' : 'false' ?>">Products</button></li>
            <li><button type="button" class="sync-hub-tab <?= $activeTab === 'sync' ? 'is-active' : '' ?>" data-sync-hub-tab="sync" role="tab" aria-selected="<?= $activeTab === 'sync' ? 'true' : 'false' ?>">Sync & Tools</button></li>
        </ul>
    </div>

    <?php view('partials.ops-safety-strip', [
        'message' => 'Read-only OpenCart sync · Max 20 rows · Entry = NEW only · No OpenCart writes',
    ]); ?>

    <?php if (!empty($mappingConfigAlertNeeded)): ?>
    <div class="alert alert-error mb-15 sync-hub-mapping-alert" data-sync-hub-alert-tabs="mapping,products" hidden>
        Configure Supplier Order Queue mappings in Status Mapping before order sync.
    </div>
    <?php endif; ?>

    <?php view('partials.flash-messages', ['flashSuccess' => $flashSuccess ?? null, 'flashError' => $flashError ?? null]); ?>

    <?php if (!empty($settings['warnings'])): ?>
    <div class="card card-warn-border mb-15">
        <div class="card-body">
            <ul class="sync-settings-note-list">
                <?php foreach ($settings['warnings'] as $warning): ?>
                <li><?= e($warning) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php endif; ?>

    <div class="sync-hub-panel" data-sync-hub-panel="connection" role="tabpanel" <?= $activeTab !== 'connection' ? 'hidden' : '' ?>>
        <?php view('sync-api-settings.tabs.connection', get_defined_vars()); ?>
    </div>
    <div class="sync-hub-panel" data-sync-hub-panel="mapping" role="tabpanel" <?= $activeTab !== 'mapping' ? 'hidden' : '' ?>>
        <?php view('sync-api-settings.tabs.status-mapping', get_defined_vars()); ?>
    </div>
    <div class="sync-hub-panel" data-sync-hub-panel="products" role="tabpanel" <?= $activeTab !== 'products' ? 'hidden' : '' ?>>
        <?php view('sync-api-settings.tabs.products', get_defined_vars()); ?>
    </div>
    <div class="sync-hub-panel" data-sync-hub-panel="sync" role="tabpanel" <?= $activeTab !== 'sync' ? 'hidden' : '' ?>>
        <?php view('sync-api-settings.tabs.sync-tools', get_defined_vars()); ?>
    </div>
</div>

<script src="<?= e(asset('js/sync-hub.js')) ?>"></script>
