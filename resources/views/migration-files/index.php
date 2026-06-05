<div class="page-header">
    <h1 class="page-title">Migration Files Planning</h1>
    <p class="page-description">Manual SQL draft inventory for future real database setup. Draft files are review material only and are not executed by the application.</p>
</div>

<div class="card" style="margin-bottom: 1.5rem; border-left: 4px solid var(--color-warn, #d97706);">
    <div class="card-header">
        <h2 class="card-title">v0.4.2 Write Foundation — Manual Activation Required</h2>
    </div>
    <div class="card-body">
        <p class="page-description"><?= e($v042ActivationNote ?? 'v0.4.2 write foundations are code-ready but database activation remains manual.') ?></p>
        <ul class="feature-list">
            <li>Supplier, business source, product, variant, cost/stock, opening balance, launch lock, manual order, workflow, and dispatch write services are implemented in code.</li>
            <li>No application page applies migration SQL automatically.</li>
            <li>Apply migrations manually on dev/staging, verify tables, re-run checkpoint, then test write forms.</li>
            <li>No live production database activation until owner completes dev/staging QA on <a href="<?= e(url('/database-safety')) ?>">Database Safety sprint merge checklist</a> and <a href="<?= e(url('/dev-db-activation')) ?>">Dev DB Activation table verification</a>.</li>
        </ul>
    </div>
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
                    <dt>File Status</dt>
                    <dd><span class="badge badge-warn">Draft only</span></dd>
                </div>
                <div class="info-row">
                    <dt>Apply Mode</dt>
                    <dd>Manual owner-approved database client or deployment process only.</dd>
                </div>
            </dl>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Draft-Only Warning</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <li>Draft SQL files live under <code>database/migrations/</code> for review.</li>
                <li>No application page loads, parses, or applies the draft files.</li>
                <li>Draft SQL files should be validated by Migration Dry Run before any owner/admin manual apply.</li>
                <li>Back up the database before any future manual apply.</li>
                <li>Use dry-run/check-first review before any future production apply.</li>
            </ul>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Migration Safety Rules</h2>
    </div>
    <div class="card-body">
        <div class="permission-grid">
            <?php foreach ($safetyRules as $rule): ?>
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

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Draft Files</h2>
        </div>
        <div class="card-body">
            <p class="page-description">Manual draft files only. Review in order before any future owner-approved apply.</p>
            <div class="planned-table-grid">
                <?php foreach ($draftFiles as $file): ?>
                    <code><?= e($file) ?></code>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Apply Order</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($applyOrder as $step): ?>
                <li><?= e($step) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Migration Groups</h2>
    </div>
    <div class="card-body">
        <div class="planned-table-grid">
            <?php foreach ($migrationGroups as $group): ?>
                <code><?= e($group) ?></code>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Index Planning</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <li>Source order references, business source, supplier, product, and variant lookups are indexed in draft files where useful.</li>
                <li>Status fields, created timestamps, reference numbers, and batch numbers are indexed for future lists and audits.</li>
                <li>Advanced database features and foreign key constraints are avoided for basic MySQL/MariaDB compatibility.</li>
                <li>Logical relationships are planned for ERP service-layer enforcement first.</li>
                <li>Migration files must be reviewed and checksum-confirmed before Migration Approval can continue.</li>
                <li>Draft files remain manual-only until approval gate and execution lock are complete later.</li>
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Rollback &amp; Red Issues</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <li>Rollback planning is required before future production apply.</li>
                <li>Any failed future check or apply stops immediately.</li>
                <li>Red Issues Summary should show severity, area, file path, route, issue detail, suggested fix, and status.</li>
                <li>No build queue task, commit, push, sync, import, or migration action continues after a red issue.</li>
            </ul>
            <p class="page-description"><a href="<?= e(url('/migration-dry-run')) ?>">Review Migration Dry Run planning</a></p>
            <p class="page-description"><a href="<?= e(url('/migration-execution-lock')) ?>">Review Migration Execution Lock planning</a></p>
        </div>
    </div>
</div>

<div class="card" style="margin-top: 1.5rem;">
    <div class="card-header"><h2 class="card-title">Manual Migration Apply Guide (v0.3.0)</h2></div>
    <div class="card-body">
        <h3>Minimum activation set (before v0.3.1)</h3>
        <ul class="feature-list"><?php foreach ($minimumActivationSet ?? [] as $file): ?><li><code><?= e($file) ?></code></li><?php endforeach; ?></ul>
        <h3>Activation steps</h3>
        <ul class="feature-list"><?php foreach ($activationGuide ?? [] as $step): ?><li><?= e($step) ?></li><?php endforeach; ?></ul>
        <h3>Full apply order</h3>
        <?php if (!empty($fullApplyOrder)): ?>
        <table class="data-table" style="width:100%;"><thead><tr><th>File</th><th>Tables</th><th>Group</th></tr></thead><tbody>
        <?php foreach ($fullApplyOrder as $row): ?><tr><td><code><?= e($row['file']) ?></code></td><td><?= e((string) $row['tables']) ?></td><td><?= e($row['group']) ?></td></tr><?php endforeach; ?>
        </tbody></table>
        <?php endif; ?>
        <h3>Write phase gates</h3>
        <ul class="feature-list"><?php foreach ($writePhaseGates ?? [] as $gate): ?><li><?= e($gate) ?></li><?php endforeach; ?></ul>
    </div>
</div>
