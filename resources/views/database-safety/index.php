<div class="page-header">
    <h1 class="page-title">Database Safety</h1>
    <p class="page-description">Manual migration rules, draft migration files, dry-run validation planning, approval gate planning, execution lock planning, migration runner planning, build automation boundaries, and future database planning.</p>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Current Database Connection</h2>
        </div>
        <div class="card-body">
            <dl class="info-list">
                <div class="info-row">
                    <dt>Status</dt>
                    <dd>
                        <span class="badge badge-<?= $databaseStatus['connected'] ? 'ok' : 'warn' ?>">
                            <?= e($databaseStatus['message']) ?>
                        </span>
                    </dd>
                </div>
                <div class="info-row">
                    <dt>Detail</dt>
                    <dd><?= e($databaseStatus['detail']) ?></dd>
                </div>
            </dl>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Manual Migration Rule</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <li>Migrations are owner/admin-reviewed files under <code>database/migrations/</code>.</li>
                <li>Back up the database before applying any schema changes.</li>
                <li>Apply SQL manually through a trusted database client or controlled deployment process.</li>
                <li>Real migration drafts exist under <code>database/migrations/</code> but remain manual-only.</li>
                <li>Dry-run validation is required before future migration apply.</li>
                <li>Migration Approval Gate protects against unsafe database changes.</li>
                <li>Migration Execution Lock protects against accidental database changes and duplicate apply attempts.</li>
                <li>Future runner actions must be explicit owner/admin actions with dry-run/check-first review.</li>
                <li>Build automation must never run migrations automatically.</li>
            </ul>
            <p class="page-description"><a href="<?= e(url('/migration-runner')) ?>">Open Migration Runner planning</a></p>
            <p class="page-description"><a href="<?= e(url('/migration-files')) ?>">Open Migration Files planning</a></p>
            <p class="page-description"><a href="<?= e(url('/migration-dry-run')) ?>">Open Migration Dry Run planning</a></p>
            <p class="page-description"><a href="<?= e(url('/migration-approval')) ?>">Open Migration Approval planning</a></p>
            <p class="page-description"><a href="<?= e(url('/migration-execution-lock')) ?>">Open Migration Execution Lock planning</a></p>
        </div>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Read-Only Repository Layer</h2>
        </div>
        <div class="card-body">
            <dl class="info-list">
                <div class="info-row">
                    <dt>Query Guard</dt>
                    <dd>
                        <span class="badge badge-<?= $queryGuardActive ? 'ok' : 'warn' ?>">
                            <?= $queryGuardActive ? 'Active' : 'Inactive' ?>
                        </span>
                    </dd>
                </div>
                <div class="info-row">
                    <dt>Scope</dt>
                    <dd>SELECT-only repository and read service foundation for all <?= e((string) ($readFoundationQa['read_repositories'] ?? 17)) ?> wired read repositories across core module pages (v0.2.9 QA gate).</dd>
                </div>
                <div class="info-row">
                    <dt>Writes</dt>
                    <dd>No mutation SQL or schema changes from read repository code. Write services use whitelisted paths only.</dd>
                </div>
            </dl>
            <?php if (!empty($readOnlyRepositorySummary)): ?>
                <table class="data-table" style="margin-top: 1rem; width: 100%;">
                    <thead>
                        <tr>
                            <th>Logical Table</th>
                            <th>Prefixed Table</th>
                            <th>Table Exists</th>
                            <th>Row Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($readOnlyRepositorySummary as $entry): ?>
                            <tr>
                                <td><code><?= e($entry['logical_table']) ?></code></td>
                                <td><code><?= e($entry['prefixed_table']) ?></code></td>
                                <td>
                                    <span class="badge badge-<?= $entry['table_exists'] ? 'ok' : 'warn' ?>">
                                        <?= $entry['table_exists'] ? 'Yes' : 'Not applied' ?>
                                    </span>
                                </td>
                                <td><?= e((string) $entry['row_count']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">No Page-Load Schema Changes</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <li>No <code>CREATE TABLE</code> during page load.</li>
                <li>No <code>ALTER TABLE</code> during page load.</li>
                <li>No <code>DROP TABLE</code> during page load.</li>
                <li>No automatic schema repair or installer execution.</li>
                <li>No OpenCart database dependency in this foundation.</li>
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Future Install Process</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <li>Design migrations for multi-business, multi-channel operations.</li>
                <li>Review SQL with owner/admin before production use.</li>
                <li>Run future migrations only through a controlled CLI/web runner after successful dry-run output, approval gate completion, execution lock readiness, and backup confirmation.</li>
                <li>Do not trigger migration apply from the Build Queue or semi-automation workflow.</li>
                <li>Record the release, actor, timing, result, and Red Issues Summary for every future run.</li>
                <li>Only then connect future ERP modules to the new tables.</li>
            </ul>
        </div>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Read Foundation QA Gate (v0.2.9)</h2>
        </div>
        <div class="card-body">
            <dl class="info-list">
                <div class="info-row">
                    <dt>Model contracts</dt>
                    <dd><?= e((string) ($readFoundationQa['model_contracts'] ?? 0)) ?></dd>
                </div>
                <div class="info-row">
                    <dt>Read repositories</dt>
                    <dd><?= e((string) ($readFoundationQa['read_repositories'] ?? 0)) ?></dd>
                </div>
                <div class="info-row">
                    <dt>Module pages wired</dt>
                    <dd><?= e((string) ($readFoundationQa['module_pages_wired'] ?? 0)) ?></dd>
                </div>
                <div class="info-row">
                    <dt>Model pending</dt>
                    <dd><?= e((string) ($readFoundationQa['model_pending_count'] ?? 0)) ?> tables awaiting future model contracts</dd>
                </div>
            </dl>
            <?php if (!empty($readFoundationModulePages)): ?>
                <table class="data-table" style="margin-top: 1rem; width: 100%;">
                    <thead>
                        <tr>
                            <th>Route</th>
                            <th>Tables</th>
                            <th>Migration</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($readFoundationModulePages as $page): ?>
                            <tr>
                                <td><code><?= e($page['route']) ?></code></td>
                                <td><?= e(implode(', ', $page['tables'])) ?></td>
                                <td><code><?= e($page['migration']) ?></code></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Real Data Readiness Checklist</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($readinessChecklist as $item): ?>
                    <li><?= e($item) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Write Path Whitelist (planned v0.3.1+)</h2>
        </div>
        <div class="card-body">
            <p class="page-description">Checkpoint allows mutation SQL only in these directories from v0.3.1 onward. No write services exist in v0.2.9.</p>
            <ul class="feature-list">
                <?php foreach ($writePathWhitelistDirs as $dir): ?>
                    <li><code><?= e($dir) ?></code></li>
                <?php endforeach; ?>
            </ul>
            <ul class="feature-list" style="margin-top: 1rem;">
                <?php foreach ($writePathWhitelistRules as $rule): ?>
                    <li><?= e($rule) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Model Pending Tables</h2>
        </div>
        <div class="card-body">
            <p class="page-description">Migration draft tables without a model contract yet. Recorded as model pending — not mass-added in v0.2.9.</p>
            <?php if (!empty($modelPendingTables)): ?>
                <table class="data-table" style="width: 100%;">
                    <thead>
                        <tr>
                            <th>Table</th>
                            <th>Model</th>
                            <th>Read Repo</th>
                            <th>Planned Version</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($modelPendingTables as $row): ?>
                            <tr>
                                <td><code><?= e($row['table']) ?></code></td>
                                <td><span class="badge badge-<?= $row['has_model'] ? 'ok' : 'warn' ?>"><?= $row['has_model'] ? 'Yes' : 'Pending' ?></span></td>
                                <td><span class="badge badge-<?= $row['has_read_repo'] ? 'ok' : 'warn' ?>"><?= $row['has_read_repo'] ? 'Yes' : 'Pending' ?></span></td>
                                <td><?= e($row['planned_version']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Pending / Planned Tables</h2>
    </div>
    <div class="card-body">
        <p class="page-description">Documented only. These tables are not created automatically.</p>
        <div class="planned-table-grid">
            <?php foreach ($plannedTables as $table): ?>
                <code><?= e($table) ?></code>
            <?php endforeach; ?>
        </div>
    </div>
</div>
