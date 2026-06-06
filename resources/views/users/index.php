<div class="page-header page-header-compact">
    <h1 class="page-title">Users</h1>
    <p class="ops-page-subtitle">User management — config-based login active; no database user writes in this release.</p>
</div>

<details class="planning-collapsible">
    <summary class="planning-collapsible-summary">Read-Only User Inventory (developer reference)</summary>
    <div class="planning-collapsible-body">
        <p class="page-description" style="margin-bottom: 1rem;">SELECT only. No database writes. No user creation. No password change. No migration apply from this page. Sensitive fields are redacted in row display.</p>
        <?php view('partials.read-inventory-card', ['readInventory' => $readInventory, 'cardTitle' => 'Users']); ?>
    </div>
</details>

<details class="planning-collapsible">
    <summary class="planning-collapsible-summary">Planning Foundation (reference)</summary>
    <div class="planning-collapsible-body">

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Current Login Mode</h2>
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
            <h2 class="card-title">Future Database Users Plan</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <li>Keep config-based admin login active until explicit user migrations are reviewed.</li>
                <li>Add database users only after a manual migration creates the required tables.</li>
                <li>Support owner, admin, staff, and supplier access without hard-coding one sales channel.</li>
                <li>Prepare for multi-business, supplier, payable, return, and offline order workflows.</li>
            </ul>
        </div>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Planned Roles</h2>
        </div>
        <div class="card-body card-body-flush">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Role</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($roles as $key => $role): ?>
                    <tr>
                        <td class="cell-name"><?= e($role['label'] ?? $key) ?></td>
                        <td class="cell-detail"><?= e($role['description'] ?? '') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Planned User Fields</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($plannedFields as $field): ?>
                    <li><?= e($field) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Security Rules</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($securityRules as $rule): ?>
                    <li><?= e($rule) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Manual Migration Required</h2>
        </div>
        <div class="card-body">
            <p>No users table is created automatically and no database user records are written in this release.</p>
            <p class="page-description">Real database users require an owner/admin-reviewed manual migration before activation.</p>
        </div>
    </div>
</div>

    </div>
</details>
