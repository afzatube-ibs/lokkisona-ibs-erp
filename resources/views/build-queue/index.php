<div class="page-header">
    <h1 class="page-title">Build Queue &amp; Semi-Automation Planning</h1>
    <p class="page-description">Safe planning foundation for one-build-at-a-time development. Planning only; no auto-runner, commit, push, or task chaining is available in this release.</p>
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
                    <dt>Automation</dt>
                    <dd><span class="badge badge-warn">Planning only</span></dd>
                </div>
                <div class="info-row">
                    <dt>Commit / Push</dt>
                    <dd>Manual owner approval required later.</dd>
                </div>
            </dl>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Build Queue Purpose</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <li>Help the agent choose the next safe build task without running a long blind task chain.</li>
                <li>Keep each build bounded to one task or one owner-approved small batch.</li>
                <li>Require checkpoint output before owner review, commit, or push.</li>
                <li>Document the future process without creating build queue tables or records.</li>
            </ul>
        </div>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Semi-Automation Workflow</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($workflow as $step): ?>
                <li><?= e($step) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Safety Gates</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($safetyGates as $gate): ?>
                <li><?= e($gate) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Allowed Automation Levels</h2>
        </div>
        <div class="card-body">
            <div class="permission-grid">
                <?php foreach ($automationLevels as $level): ?>
                <div class="permission-group">
                    <h3><?= e($level['title']) ?></h3>
                    <p><?= e($level['summary']) ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Blocked Automation Actions</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($blockedActions as $action): ?>
                <li><?= e($action) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Future Planning Sections</h2>
    </div>
    <div class="card-body">
        <div class="permission-grid">
            <?php foreach ($planningSections as $section): ?>
            <div class="permission-group">
                <h3><?= e($section['title']) ?></h3>
                <ul>
                    <?php foreach ($section['points'] as $point): ?>
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
            <h2 class="card-title">Planned Build Queue Fields</h2>
        </div>
        <div class="card-body">
            <div class="planned-table-grid">
                <?php foreach ($buildQueueFields as $field): ?>
                    <code><?= e($field) ?></code>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Planned Build Run Fields</h2>
        </div>
        <div class="card-body">
            <div class="planned-table-grid">
                <?php foreach ($buildRunFields as $field): ?>
                    <code><?= e($field) ?></code>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Planned Red Issue Fields</h2>
        </div>
        <div class="card-body">
            <div class="planned-table-grid">
                <?php foreach ($redIssueFields as $field): ?>
                    <code><?= e($field) ?></code>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Checkpoint-First Stop Rule</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <li>Passing builds show version, changed files, browser/route count, Red Issues: none, and recommended next build.</li>
                <li>Failed builds stop immediately at [FAIL] RED ISSUES SUMMARY.</li>
                <li>No commit, push, next task, migration apply, sync, import, stock, payable, or invoice action happens automatically.</li>
                <li>Next build starts only after Git is synced with origin/main.</li>
                <li>Migration tasks require owner approval, backup confirmation, and manual apply only.</li>
                <li>Migration dry-run must pass before any migration-related build can move forward.</li>
                <li>Build Queue must never trigger migration apply automatically; the approval gate is manual only.</li>
                <li>Build Queue must never trigger migration execution or bypass the execution lock.</li>
                <li>After the opening balance and launch cutover foundation, the next major phase should move toward v0.2 real database/model foundation planning.</li>
            </ul>
        </div>
    </div>
</div>
