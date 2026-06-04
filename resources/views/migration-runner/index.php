<div class="page-header">
    <h1 class="page-title">Migration Runner Planning</h1>
    <p class="page-description">Owner/admin foundation for future controlled database migrations. Future real apply must require successful dry-run and Migration Approval Gate first; no SQL execution is available in this release.</p>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Current Safety Status</h2>
        </div>
        <div class="card-body">
            <dl class="info-list">
                <div class="info-row">
                    <dt>Access Mode</dt>
                    <dd><?= e($accessMode['mode']) ?></dd>
                </div>
                <div class="info-row">
                    <dt>Current Role</dt>
                    <dd><span class="badge badge-ok"><?= e($accessMode['role']) ?></span></dd>
                </div>
                <div class="info-row">
                    <dt>Execution</dt>
                    <dd><span class="badge badge-warn">Planning only</span></dd>
                </div>
                <div class="info-row">
                    <dt>Database Change</dt>
                    <dd>No CREATE TABLE / ALTER TABLE / DROP TABLE from this page.</dd>
                </div>
            </dl>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Hard Safety Rules</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <li>No page-load migration, installer, or self-healing database behavior.</li>
                <li>No automatic SQL execution in v<?= e($appVersion) ?>.</li>
                <li>Dry-run/check-first and backup-before-apply are mandatory for the future runner.</li>
                <li>Future real apply must require successful Migration Dry Run validation first.</li>
                <li>Future real apply must require Migration Approval Gate before any manual execution.</li>
                <li>Staff and supplier pages must never manage migrations.</li>
                <li>Draft files under <code>database/migrations/</code> are manual review files only.</li>
            </ul>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Foundation Rules</h2>
    </div>
    <div class="card-body">
        <div class="permission-grid">
            <?php foreach ($rules as $rule): ?>
            <div class="permission-group">
                <h3><?= e($rule['title']) ?></h3>
                <ul>
                    <?php foreach ($rule['points'] as $point): ?>
                    <li><?= e($point) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Planned Migration Groups</h2>
    </div>
    <div class="card-body">
        <p class="page-description">Documented only. These groups describe future controlled migration ordering and do not create database objects.</p>
        <div class="planned-table-grid">
            <?php foreach ($plannedGroups as $group): ?>
                <code><?= e($group) ?></code>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Planned Migration Fields</h2>
        </div>
        <div class="card-body">
            <div class="planned-table-grid">
                <?php foreach ($migrationFields as $field): ?>
                    <code><?= e($field) ?></code>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Planned Run Log Fields</h2>
        </div>
        <div class="card-body">
            <div class="planned-table-grid">
                <?php foreach ($runLogFields as $field): ?>
                    <code><?= e($field) ?></code>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Planned Rollback Fields</h2>
        </div>
        <div class="card-body">
            <div class="planned-table-grid">
                <?php foreach ($rollbackFields as $field): ?>
                    <code><?= e($field) ?></code>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Future Runner Boundary</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <li>Future CLI/web runner must show what will change before apply.</li>
                <li>Future migration logs must include actor, environment, timing, result, and Red Issues Summary.</li>
                <li>Future rollback plans must be approved before execution.</li>
                <li>Build Queue or semi-automation must never trigger migration apply automatically.</li>
                <li>This foundation does not write migration records or run migration files.</li>
            </ul>
            <p class="page-description"><a href="<?= e(url('/database-safety')) ?>">Review Database Safety rules</a></p>
            <p class="page-description"><a href="<?= e(url('/migration-files')) ?>">Review Migration Files planning</a></p>
            <p class="page-description"><a href="<?= e(url('/migration-dry-run')) ?>">Review Migration Dry Run planning</a></p>
            <p class="page-description"><a href="<?= e(url('/migration-approval')) ?>">Review Migration Approval planning</a></p>
        </div>
    </div>
</div>
