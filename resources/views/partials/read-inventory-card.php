<?php
$inventory = $readInventory ?? [];
$recordLabel = $recordLabel ?? 'records';
$cardTitle = $cardTitle ?? 'Read-Only Inventory';
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
            <p class="page-description" style="margin-top: 1rem;"><strong>Model columns:</strong> <?= e(implode(', ', $inventory['columns'])) ?></p>
        <?php endif; ?>

        <?php if (!empty($inventory['rows'])): ?>
            <table class="data-table" style="margin-top: 1rem; width: 100%;">
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
                                <td><?= e((string) ($row[$column] ?? '')) ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p class="page-description" style="margin-top: 0.75rem;"><?= e($inventory['status_message'] ?? '') ?></p>
        <?php else: ?>
            <p class="page-description" style="margin-top: 1rem;">
                <span class="badge badge-warn">Empty state</span>
                <?= e($inventory['status_message'] ?? 'Read inventory unavailable.') ?>
            </p>
        <?php endif; ?>

        <p class="page-description" style="margin-top: 0.75rem;">SELECT only. No database writes in this release. See <a href="<?= e(url('/database-safety')) ?>">Database Safety</a> for repository inventory and manual migration rules.</p>
    </div>
</div>
