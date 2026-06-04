<div class="page-header">
    <h1 class="page-title">Migration Apply Approval Gate Planning</h1>
    <p class="page-description">Future owner/admin approval gate before real migration apply. Planning only; approval does not mean automatic execution, no SQL is executed, and no approval records are written.</p>
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
                    <dt>Approval Mode</dt>
                    <dd><span class="badge badge-warn">Planning only</span></dd>
                </div>
                <div class="info-row">
                    <dt>Apply Mode</dt>
                    <dd>Future manual execution only after approval; no apply action exists here.</dd>
                </div>
            </dl>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Approval Gate Purpose</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <li>Require successful dry-run before future real apply.</li>
                <li>Require backup confirmation, correct environment, checksum confirmation, apply order review, and rollback plan review.</li>
                <li>Require owner approval plus admin/operator confirmation.</li>
                <li>Block apply when Red Issues count is not zero.</li>
            </ul>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Approval Gate Rules</h2>
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
            <h2 class="card-title">Future Approval Checklist Preview</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($checklistItems as $item): ?>
                <li><?= e($item) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Production Safety Warning</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <li>Production apply must require extra confirmation later.</li>
                <li>Dry-run pass, backup reference, checksum match, and Red Issues clear state are mandatory.</li>
                <li>The approved file set should be locked before future apply.</li>
                <li>This page does not execute, approve, lock, or write anything.</li>
            </ul>
        </div>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Planned Migration Approval Fields</h2>
        </div>
        <div class="card-body">
            <div class="planned-table-grid">
                <?php foreach ($approvalFields as $field): ?>
                    <code><?= e($field) ?></code>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Planned Checklist Fields</h2>
        </div>
        <div class="card-body">
            <div class="planned-table-grid">
                <?php foreach ($checkFields as $field): ?>
                    <code><?= e($field) ?></code>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Planned Approval Audit Fields</h2>
        </div>
        <div class="card-body">
            <div class="planned-table-grid">
                <?php foreach ($auditFields as $field): ?>
                    <code><?= e($field) ?></code>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Manual-Only Boundary</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <li>Build Queue must never trigger migration apply automatically.</li>
                <li>Migration Runner must require this approval gate before future apply.</li>
                <li>Approval does not mean automatic execution; Migration Execution Lock must still be ready later.</li>
                <li>Migration Files must be reviewed and checksum-confirmed before approval.</li>
                <li>Migration Dry Run must pass before approval can continue.</li>
            </ul>
            <p class="page-description"><a href="<?= e(url('/migration-execution-lock')) ?>">Review Migration Execution Lock planning</a></p>
        </div>
    </div>
</div>
