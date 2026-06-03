<div class="page-header">
    <h1 class="page-title">Database Safety</h1>
    <p class="page-description">Manual migration rules and future database planning.</p>
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
            </ul>
        </div>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">No Page-Load Schema Changes</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <li>No <code>CREATE TABLE</code> during page load.</li>
                <li>No <code>ALTER TABLE</code> during page load.</li>
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
                <li>Apply migrations manually and record the release that introduced them.</li>
                <li>Only then connect future ERP modules to the new tables.</li>
            </ul>
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
