<div class="page-header">
    <h1 class="page-title">Dashboard</h1>
    <p class="page-description">Welcome to IBS-LK Business Manager.</p>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon stat-icon-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        </div>
        <div class="stat-content">
            <span class="stat-label">System Status</span>
            <span class="stat-value">Operational</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon stat-icon-success">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        </div>
        <div class="stat-content">
            <span class="stat-label">Version</span>
            <span class="stat-value">v<?= e($appVersion) ?></span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon stat-icon-info">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
        </div>
        <div class="stat-content">
            <span class="stat-label">Signed in as</span>
            <span class="stat-value"><?= e($currentUser) ?></span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon stat-icon-warn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>
        </div>
        <div class="stat-content">
            <span class="stat-label">Database</span>
            <span class="stat-value">Configure</span>
        </div>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Quick Actions</h2>
        </div>
        <div class="card-body">
            <div class="action-links">
                <a href="<?= e(url('/health')) ?>" class="action-link">
                    <span class="action-link-title">Health Check</span>
                    <span class="action-link-desc">Verify system requirements and connectivity</span>
                </a>
                <a href="<?= e(url('/version')) ?>" class="action-link">
                    <span class="action-link-title">Version Info</span>
                    <span class="action-link-desc">View release details and dependencies</span>
                </a>
                <a href="<?= e(url('/activity-log')) ?>" class="action-link">
                    <span class="action-link-title">Activity Log</span>
                    <span class="action-link-desc">Review recent authentication and system activity</span>
                </a>
                <a href="<?= e(url('/roles-permissions')) ?>" class="action-link">
                    <span class="action-link-title">Role & Permissions</span>
                    <span class="action-link-desc">Review role and permission foundation planning</span>
                </a>
                <a href="<?= e(url('/database-safety')) ?>" class="action-link">
                    <span class="action-link-title">Database Safety</span>
                    <span class="action-link-desc">Review manual migration rules and planned tables</span>
                </a>
                <a href="<?= e(url('/users')) ?>" class="action-link">
                    <span class="action-link-title">Users</span>
                    <span class="action-link-desc">Review user management foundation planning</span>
                </a>
                <a href="<?= e(url('/suppliers')) ?>" class="action-link">
                    <span class="action-link-title">Suppliers</span>
                    <span class="action-link-desc">Review supplier foundation — primary supplier Iqbal &amp; Brothers</span>
                </a>
                <a href="<?= e(url('/business-sources')) ?>" class="action-link">
                    <span class="action-link-title">Business Sources</span>
                    <span class="action-link-desc">Review business source and sales channel foundation</span>
                </a>
                <a href="<?= e(url('/product-control')) ?>" class="action-link">
                    <span class="action-link-title">Product Control</span>
                    <span class="action-link-desc">Review product, cost, and stock control foundation</span>
                </a>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Foundation Overview</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <li>Standalone PHP application — no OpenCart extension</li>
                <li>Session-based authentication ready for future owner, admin, and staff roles</li>
                <li>Config-backed role and permission foundation</li>
                <li>Manual migration and database safety foundation</li>
                <li>User management foundation without database writes</li>
                <li>Supplier foundation without database writes — channel-neutral and multi-supplier ready</li>
                <li>Business source and sales channel foundation without database writes</li>
                <li>Product control foundation without database writes — supplier cost/stock planning ready</li>
                <li>Simple router with MVC-style controllers</li>
                <li>Responsive admin layout with IBS-LK branding</li>
                <li>File-backed activity log foundation</li>
                <li>Git-ready repository structure</li>
            </ul>
        </div>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Product Control Foundation</h2>
        </div>
        <div class="card-body">
            <p>Supplier product, cost, and stock planning for Iqbal &amp; Brothers — read-only platform fields, editable supplier model/cost/stock, cost/stock history, and low-stock warnings without OpenCart sync.</p>
            <p class="page-description"><a href="<?= e(url('/product-control')) ?>">Open Product Control foundation</a></p>
        </div>
    </div>
</div>
