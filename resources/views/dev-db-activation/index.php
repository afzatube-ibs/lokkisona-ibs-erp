<div class="page-header">
    <h1 class="page-title">Dev Database Activation Helper</h1>
    <p class="page-description">Manual-only migration apply guide and read-only table verification for dev/staging. No SQL execution, no automatic apply, no schema changes from this page.</p>
</div>

<?php if (!empty($activationStatus['prefix_mismatch'])): ?>
<div class="card" style="margin-bottom: 1.5rem; border-left: 4px solid var(--color-warn, #d97706);">
    <div class="card-header">
        <h2 class="card-title">Table Prefix Mismatch Warning</h2>
    </div>
    <div class="card-body">
        <p class="page-description"><?= e($activationStatus['prefix_mismatch_message'] ?? \App\Migration\DevDatabaseActivation::PREFIX_MISMATCH_MESSAGE) ?></p>
        <p class="page-description">Configured prefix: <code><?= e($activationStatus['table_prefix'] ?? 'ibs_') ?></code>. Table readiness below checks <code>ibs_*</code> names only.</p>
        <?php if (!empty($activationStatus['prefix_mismatch_tables'])): ?>
        <table class="data-table" style="width: 100%; margin-top: 1rem;">
            <thead>
                <tr>
                    <th>Found (non-prefixed)</th>
                    <th>ERP expects</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($activationStatus['prefix_mismatch_tables'] as $row): ?>
                    <tr>
                        <td><code><?= e($row['found']) ?></code></td>
                        <td><code><?= e($row['expected']) ?></code></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
        <p class="page-description" style="margin-top: 1rem;">Migration draft files (v0.4.2.4) now create <code>ibs_*</code> tables. If older non-prefixed tables exist from a prior manual apply, rename or reset the dev database before continuing.</p>
    </div>
</div>
<?php endif; ?>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Current Activation Status</h2>
        </div>
        <div class="card-body">
            <dl class="info-list">
                <div class="info-row">
                    <dt>Database</dt>
                    <dd>
                        <span class="badge badge-<?= ($activationStatus['connected'] ?? false) ? 'ok' : 'warn' ?>">
                            <?= e($activationStatus['database_message'] ?? 'Unknown') ?>
                        </span>
                    </dd>
                </div>
                <div class="info-row">
                    <dt>Detail</dt>
                    <dd><?= e($activationStatus['database_detail'] ?? '') ?></dd>
                </div>
                <div class="info-row">
                    <dt>Overall status</dt>
                    <dd>
                        <?php
                        $overall = $activationStatus['overall_status'] ?? 'Unavailable';
                        $overallBadge = $overall === 'All groups ready' ? 'ok' : ($overall === 'Partial activation' ? 'warn' : 'warn');
                        ?>
                        <span class="badge badge-<?= $overallBadge ?>"><?= e($overall) ?></span>
                    </dd>
                </div>
                <div class="info-row">
                    <dt>Groups ready</dt>
                    <dd><?= e((string) ($activationStatus['ready_groups'] ?? 0)) ?> / <?= e((string) ($activationStatus['group_count'] ?? 0)) ?></dd>
                </div>
                <div class="info-row">
                    <dt>Tables ready</dt>
                    <dd><?= e((string) ($activationStatus['ready_tables'] ?? 0)) ?> / <?= e((string) ($activationStatus['total_tables'] ?? 0)) ?></dd>
                </div>
                <div class="info-row">
                    <dt>Table prefix</dt>
                    <dd><code><?= e($activationStatus['table_prefix'] ?? 'ibs_') ?></code></dd>
                </div>
            </dl>
            <?php if (!($activationStatus['connected'] ?? false)): ?>
                <p class="page-description" style="margin-top: 1rem;">Database not connected — table readiness checks are unavailable. Configure <code>config/database.php</code> and ensure MySQL is running, then refresh this page.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Safety Rules</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <li><strong>Manual-only:</strong> Apply migration SQL only through a trusted database client or controlled deployment — never from this application.</li>
                <li><strong>Backup required:</strong> Owner must back up the database before any manual SQL apply.</li>
                <li><strong>Dev/staging only:</strong> Test write forms on dev/staging first. No live production activation until full QA passes.</li>
                <li><strong>Read-only checks:</strong> This page uses INFORMATION_SCHEMA SELECT only. No CREATE, ALTER, DROP, or mutation SQL.</li>
                <li><strong>Write form gating (v0.4.2.3):</strong> Module write/action forms stay hidden until every required table in the matching group shows Ready. Read inventory cards remain visible regardless.</li>
                <li><strong>Table prefix (v0.4.2.4):</strong> Migration drafts create <code>ibs_*</code> tables matching <code>config/database.php</code>. Readiness checks use prefixed names only.</li>
            </ul>
            <p class="page-description"><a href="<?= e(url('/database-safety')) ?>">Database Safety sprint merge checklist</a> · <a href="<?= e(url('/migration-files')) ?>">Migration Files</a></p>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Migration Apply Order</h2>
    </div>
    <div class="card-body">
        <table class="data-table" style="width: 100%;">
            <thead>
                <tr>
                    <th>File</th>
                    <th>Tables</th>
                    <th>Group</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($applyOrder as $row): ?>
                    <tr>
                        <td><code><?= e($row['file']) ?></code></td>
                        <td><?= e((string) $row['tables']) ?></td>
                        <td><?= e($row['group']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <p class="page-description" style="margin-top: 1rem;">Group 0004 (status mapping/sync preview) is optional for core write-path testing. Groups A–F above map to migrations 0002, 0003, 0005, 0006, 0007, and 0008.</p>
    </div>
</div>

<?php foreach ($tableGroups as $group): ?>
<div class="card" style="margin-top: 1rem;">
    <div class="card-header">
        <h2 class="card-title"><?= e($group['label']) ?></h2>
    </div>
    <div class="card-body">
        <dl class="info-list">
            <div class="info-row">
                <dt>Migration file</dt>
                <dd><code><?= e(implode(', ', $group['migration_files'])) ?></code></dd>
            </div>
            <div class="info-row">
                <dt>Group status</dt>
                <dd>
                    <?php
                    $gs = $group['group_status'] ?? 'Unavailable';
                    $gsBadge = $gs === 'Ready' ? 'ok' : ($gs === 'Partial' ? 'warn' : 'warn');
                    ?>
                    <span class="badge badge-<?= $gsBadge ?>"><?= e($gs) ?></span>
                    (<?= e((string) ($group['ready_count'] ?? 0)) ?> / <?= e((string) ($group['table_count'] ?? 0)) ?> tables)
                </dd>
            </div>
        </dl>
        <table class="data-table" style="width: 100%; margin-top: 1rem;">
            <thead>
                <tr>
                    <th>Table</th>
                    <th>Readiness</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($group['tables'] as $tableRow): ?>
                    <tr>
                        <td><code><?= e($tableRow['table']) ?></code></td>
                        <td>
                            <?php
                            $ts = $tableRow['status'] ?? 'Unavailable';
                            $tsBadge = $ts === 'Ready' ? 'ok' : 'warn';
                            ?>
                            <span class="badge badge-<?= $tsBadge ?>"><?= e($ts) ?></span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <h3 style="margin-top: 1.25rem;">After apply — can test</h3>
        <p><?= e($group['testable_after']) ?></p>
        <h3>Must stay blocked</h3>
        <p><?= e($group['still_blocked']) ?></p>
    </div>
</div>
<?php endforeach; ?>

<div class="card-grid" style="margin-top: 1rem;">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">After Apply Test Flow</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($applyTestFlow as $step): ?>
                    <li><?= e($step) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Global — Must Stay Blocked</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($globalBlocked as $item): ?>
                    <li><?= e($item) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>
