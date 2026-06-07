<?php if (!empty($productSyncDiagnostics)): ?>
<?php
$rows = $productSyncDiagnostics['rows'] ?? [];
$ready = !empty($productSyncDiagnostics['ready']);
?>
<div class="card mb-15 sync-diagnostics-card <?= $ready ? '' : 'card-warn-border' ?>">
    <div class="card-header sync-diagnostics-header">
        <div>
            <h2 class="card-title">Product Sync Help</h2>
            <p class="page-description mb-0"><?= $ready ? 'All checks passed for preview and import.' : 'Fix the items below, then reload product preview.' ?></p>
        </div>
        <span class="badge <?= $ready ? 'badge-ok' : 'badge-warn' ?>"><?= $ready ? 'Ready' : 'Needs attention' ?></span>
    </div>
    <div class="card-body">
        <?php if ($rows !== []): ?>
        <div class="table-scroll">
            <table class="data-table data-table-compact sync-diagnostics-table">
                <thead>
                    <tr>
                        <th>Check</th>
                        <th>Status</th>
                        <th>Fix</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?= e($row['label'] ?? '') ?></td>
                        <td><span class="badge <?= !empty($row['ok']) ? 'badge-ok' : 'badge-warn' ?>"><?= e($row['status'] ?? '') ?></span></td>
                        <td class="sync-diagnostics-fix"><?= e($row['fix'] ?? '—') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <p class="form-help sync-diagnostics-footer">
            Open <a href="<?= e($productSyncDiagnostics['settings_path'] ?? url('/sync-api-settings')) ?>">Sync/API Settings</a> to adjust source URL, routes, and API key. Read-only sync only.
        </p>
    </div>
</div>
<?php endif; ?>
