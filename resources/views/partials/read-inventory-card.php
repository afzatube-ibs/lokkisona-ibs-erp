<?php
$inventory = $readInventory ?? [];
$recordLabel = $recordLabel ?? 'records';
$cardTitle = $cardTitle ?? 'Read-Only Inventory';
$shouldRedactColumn = static function (string $column, array $inventory): bool {
    if (empty($inventory['redact_sensitive_fields'])) {
        return false;
    }

    $lower = strtolower($column);
    $exactMatches = ['password', 'password_hash', 'remember_token', 'reset_token', 'api_key', 'secret'];

    if (in_array($lower, $exactMatches, true)) {
        return true;
    }

    foreach (['password', 'token', 'secret', 'key'] as $needle) {
        if (str_contains($lower, $needle)) {
            return true;
        }
    }

    return false;
};
?>
<div class="card">
    <div class="card-header">
        <h2 class="card-title"><?= e($cardTitle) ?></h2>
    </div>
    <div class="card-body">
        <p class="page-description">Live Read Inventory (SELECT only). No database writes, no sync, and no migration apply from this page.</p>
        <dl class="info-list">
            <div class="info-row">
                <dt>Database Connection</dt>
                <dd>
                    <span class="badge badge-<?= !empty($inventory['database_connected']) ? 'ok' : 'warn' ?>">
                        <?= !empty($inventory['database_connected']) ? 'Connected' : 'Not connected' ?>
                    </span>
                </dd>
            </div>
            <div class="info-row">
                <dt>Read Service</dt>
                <dd>
                    <span class="badge badge-<?= !empty($inventory['service_ready']) ? 'ok' : 'warn' ?>">
                        <?= !empty($inventory['service_ready']) ? 'Ready' : 'Unavailable' ?>
                    </span>
                    <?php if (!empty($inventory['read_service']) && !empty($inventory['read_repository'])): ?>
                        <span class="page-description"><?= e($inventory['read_service']) ?> → <?= e($inventory['read_repository']) ?></span>
                    <?php endif; ?>
                </dd>
            </div>
            <div class="info-row">
                <dt>Logical Table</dt>
                <dd><code><?= e($inventory['logical_table'] ?? '') ?></code></dd>
            </div>
            <div class="info-row">
                <dt>Prefixed Table</dt>
                <dd><code><?= e($inventory['prefixed_table'] ?? '') ?></code></dd>
            </div>
            <div class="info-row">
                <dt>Table Readiness</dt>
                <dd>
                    <span class="badge badge-<?= !empty($inventory['table_exists']) ? 'ok' : 'warn' ?>">
                        <?= !empty($inventory['table_exists']) ? 'Ready' : 'Not applied' ?>
                    </span>
                </dd>
            </div>
            <div class="info-row">
                <dt>Model Contract</dt>
                <dd><code><?= e($inventory['model_class'] ?? '') ?></code> — primary key <code><?= e($inventory['primary_key'] ?? '') ?></code></dd>
            </div>
            <div class="info-row">
                <dt>Row Count</dt>
                <dd><?= e((string) ($inventory['row_count'] ?? 0)) ?></dd>
            </div>
        </dl>

    <?php if (!empty($inventory['columns'])): ?>
        <p class="page-description mt-1"><strong>Model columns:</strong> <?= e(implode(', ', $inventory['columns'])) ?></p>
    <?php endif; ?>

    <?php if (!empty($inventory['rows'])): ?>
        <table class="data-table mt-1">
                <thead>
                    <tr>
                        <?php foreach ($inventory['columns'] as $column): ?>
                            <th><?= e($column) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($inventory['rows'] as $row): ?>
                        <tr>
                            <?php foreach ($inventory['columns'] as $column): ?>
                                <?php
                                $cellValue = $shouldRedactColumn($column, $inventory)
                                    ? '[redacted]'
                                    : (string) ($row[$column] ?? '');
                                ?>
                                <td><?= e($cellValue) ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <p class="page-description mt-075"><?= e($inventory['status_message'] ?? '') ?></p>
    <?php else: ?>
        <div class="empty-state">
            <svg class="empty-state-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/></svg>
            <span class="empty-state-text"><span class="badge badge-warn">Empty state</span></span>
            <span class="empty-state-sub"><?= e($inventory['status_message'] ?? 'Read inventory unavailable.') ?></span>
        </div>
    <?php endif; ?>

    <p class="page-description mt-075">SELECT only. No database writes in this release. See <a href="<?= e(url('/database-safety')) ?>">Database Safety</a> for repository inventory and manual migration rules.</p>
    </div>
</div>
