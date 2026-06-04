<div class="page-header">
    <h1 class="page-title">Migration Dry Run Validator Planning</h1>
    <p class="page-description">Future check layer for migration drafts. Planning only; no SQL is executed and no database changes are made.</p>
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
                    <dt>Database Write</dt>
                    <dd>No SQL execution, no migration apply, and no schema changes.</dd>
                </div>
            </dl>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Dry-Run Purpose</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <li>Scan migration draft files before any future real apply.</li>
                <li>Validate safety, order, warnings, duplicate keys, and planned rollback references.</li>
                <li>Show warnings and Red Issues Summary without touching the database.</li>
                <li>Require owner/admin approval before any future real apply.</li>
            </ul>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Dry-Run Validator Rules</h2>
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

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Planned Dry-Run Checks</h2>
        </div>
        <div class="card-body">
            <div class="planned-table-grid">
                <?php foreach ($plannedChecks as $check): ?>
                    <code><?= e($check) ?></code>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Future Result Preview</h2>
        </div>
        <div class="card-body card-body-flush">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Migration File</th>
                        <th>Group</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($previewRows as $row): ?>
                    <tr>
                        <td class="cell-name"><?= e($row['file']) ?></td>
                        <td><?= e($row['group']) ?></td>
                        <td><span class="badge badge-warn"><?= e($row['status']) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Planned Dry-Run Result Fields</h2>
        </div>
        <div class="card-body">
            <div class="planned-table-grid">
                <?php foreach ($resultFields as $field): ?>
                    <code><?= e($field) ?></code>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Planned Dry-Run Issue Fields</h2>
        </div>
        <div class="card-body">
            <div class="planned-table-grid">
                <?php foreach ($issueFields as $field): ?>
                    <code><?= e($field) ?></code>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Backup &amp; Owner Approval</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <li>Dry-run pass is required before future migration apply planning can continue.</li>
                <li>Backup reminder must still be shown before any future real apply.</li>
                <li>Owner/admin approval remains required even after dry-run passes.</li>
                <li>A passed dry-run never auto-applies a migration.</li>
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Red Issues Stop Rule</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <li>Any red issue blocks future migration apply.</li>
                <li>Issue summaries should include severity, file path, line number, issue detail, and suggested fix.</li>
                <li>No next migration, build task, commit, push, sync, or import continues after a red issue.</li>
                <li>This page documents the behavior only; no dry-run records are written.</li>
            </ul>
        </div>
    </div>
</div>
