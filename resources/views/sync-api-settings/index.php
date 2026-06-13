<?php
$headerBadge = $connectionSummary['header_badge'] ?? ['label' => 'Demo Mode', 'class' => 'badge-info'];
$activeTab = $activeTab ?? 'connection';
$mappingAttentionCount = (int) ($mappingAttentionCount ?? 0);
$mappingQueueLoaded = !empty($mappingQueueLoaded);
$connectionChip = is_array($connectionChip ?? null) ? $connectionChip : ['label' => 'Not tested', 'class' => 'is-untested'];
$lastTestAt = trim((string) ($connectionSummary['last_connection_test_at'] ?? ''));

$hubTabs = [
    'connection' => 'Connection',
    'mapping' => 'Status Mapping',
    'products' => 'Products',
    'orders' => 'Order Sync',
    'maintenance' => 'Maintenance',
    'activity' => 'Recent Activity',
];
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
    </div>

    <div class="sync-hub-layout">
        <nav class="sync-hub-side-tabs" role="tablist" aria-label="Sync settings sections">
            <ul class="sync-hub-side-tabs-list">
                <?php foreach ($hubTabs as $tabKey => $tabLabel): ?>
                <li>
                    <button type="button"
                        class="sync-hub-tab sync-hub-side-tab <?= $activeTab === $tabKey ? 'is-active' : '' ?>"
                        data-sync-hub-tab="<?= e($tabKey) ?>"
                        role="tab"
                        aria-selected="<?= $activeTab === $tabKey ? 'true' : 'false' ?>">
                        <?= e($tabLabel) ?>
                        <?php if ($tabKey === 'mapping' && $mappingAttentionCount > 0): ?>
                        <span class="sync-hub-tab-badge"><?= e((string) $mappingAttentionCount) ?></span>
                        <?php elseif ($tabKey === 'mapping' && $mappingQueueLoaded): ?>
                        <span class="sync-hub-tab-badge is-done" aria-label="All queue statuses mapped">✓</span>
                        <?php endif; ?>
                    </button>
                </li>
                <?php endforeach; ?>
            </ul>
        </nav>

        <div class="sync-hub-content">
            <?php view('partials.ops-safety-strip', [
                'message' => 'Read-only OpenCart sync · Max 20 rows · Entry = NEW only · No OpenCart writes',
            ]); ?>

            <?php if (!empty($mappingConfigAlertNeeded)): ?>
            <div class="alert alert-error mb-15 sync-hub-mapping-alert" data-sync-hub-alert-tabs="mapping,products,orders" hidden>
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
            <div class="sync-hub-panel" data-sync-hub-panel="orders" role="tabpanel" <?= $activeTab !== 'orders' ? 'hidden' : '' ?>>
                <?php view('sync-api-settings.tabs.order-sync', get_defined_vars()); ?>
            </div>
            <div class="sync-hub-panel" data-sync-hub-panel="maintenance" role="tabpanel" <?= $activeTab !== 'maintenance' ? 'hidden' : '' ?>>
                <?php view('sync-api-settings.tabs.maintenance', get_defined_vars()); ?>
            </div>
            <div class="sync-hub-panel" data-sync-hub-panel="activity" role="tabpanel" <?= $activeTab !== 'activity' ? 'hidden' : '' ?>>
                <?php view('sync-api-settings.tabs.activity', get_defined_vars()); ?>
            </div>
        </div>
    </div>
</div>

<script src="<?= e(asset('js/sync-hub.js')) ?>"></script>
