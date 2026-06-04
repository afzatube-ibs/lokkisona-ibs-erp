<div class="page-header">
    <h1 class="page-title">Migration Execution Lock Planning</h1>
    <p class="page-description">Future final safety lock before any manual migration execution. Planning only; no SQL is executed and no lock records are written.</p>
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
                    <dt>Lock Mode</dt>
                    <dd><span class="badge badge-warn">Planning only</span></dd>
                </div>
                <div class="info-row">
                    <dt>Execution</dt>
                    <dd>No migration execution exists on this page.</dd>
                </div>
            </dl>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Execution Lock Purpose</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <li>Lock future migration execution by default.</li>
                <li>Require dry-run pass, approval gate, backup, clean Git, checksum confirmation, rollback confirmation, and zero Red Issues.</li>
                <li>Protect against wrong environment, missing approval, duplicate apply, and emergency stop conditions.</li>
                <li>Even ready state stays manual-only.</li>
            </ul>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Execution Lock Rules</h2>
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
            <h2 class="card-title">Planned Lock States</h2>
        </div>
        <div class="card-body">
            <div class="planned-table-grid">
                <?php foreach ($lockStates as $state): ?>
                    <code><?= e($state) ?></code>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Final Lock State Preview</h2>
        </div>
        <div class="card-body card-body-flush">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>State</th>
                        <th>Meaning</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($previewRows as $row): ?>
                    <tr>
                        <td class="cell-name"><?= e($row['state']) ?></td>
                        <td><?= e($row['meaning']) ?></td>
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
            <h2 class="card-title">Planned Execution Lock Fields</h2>
        </div>
        <div class="card-body">
            <div class="planned-table-grid">
                <?php foreach ($lockFields as $field): ?>
                    <code><?= e($field) ?></code>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Planned Lock Audit Fields</h2>
        </div>
        <div class="card-body">
            <div class="planned-table-grid">
                <?php foreach ($auditFields as $field): ?>
                    <code><?= e($field) ?></code>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Emergency Stop Planning</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <li>Emergency stop forces <code>emergency_locked</code> until owner/admin review.</li>
                <li>Emergency stop should record actor, role, previous state, new state, note, and timestamp later.</li>
                <li>Emergency stop must block build queue, sync/import, and migration apply paths.</li>
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Manual-Only Boundary</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <li>Migration Runner requires approval gate plus execution lock before future apply.</li>
                <li>Migration Approval does not mean automatic execution.</li>
                <li>Build Queue must never trigger migration execution.</li>
                <li>This foundation does not unlock, execute, apply, sync, import, commit, or push.</li>
            </ul>
        </div>
    </div>
</div>
