<div class="page-header">
    <h1 class="page-title">Role & Permission Foundation</h1>
    <p class="page-description">Config-backed access planning for future database users.</p>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Current Access Mode</h2>
        </div>
        <div class="card-body">
            <dl class="info-list">
                <div class="info-row">
                    <dt>Mode</dt>
                    <dd><?= e($accessMode['mode']) ?></dd>
                </div>
                <div class="info-row">
                    <dt>Current Role</dt>
                    <dd><?= e($accessMode['role']) ?></dd>
                </div>
            </dl>
            <p class="page-description"><?= e($accessMode['summary']) ?></p>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Future User Management Plan</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <li>Add users, roles, and permissions with explicit manual migrations.</li>
                <li>Keep owner, admin, staff, and supplier as the first role set.</li>
                <li>Map business modules to permission groups before enabling write actions.</li>
                <li>No automatic table creation, schema repair, or OpenCart dependency.</li>
            </ul>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Roles</h2>
    </div>
    <div class="card-body card-body-flush">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Role</th>
                    <th>Label</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($roles as $key => $role): ?>
                <tr>
                    <td class="cell-name"><?= e($key) ?></td>
                    <td><?= e($role['label'] ?? '') ?></td>
                    <td class="cell-detail"><?= e($role['description'] ?? '') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Permission Groups</h2>
    </div>
    <div class="card-body">
        <div class="permission-grid">
            <?php foreach ($groups as $group => $permissions): ?>
            <div class="permission-group">
                <h3><?= e($group) ?></h3>
                <ul>
                    <?php foreach ($permissions as $permission): ?>
                    <li><code><?= e($permission) ?></code></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
