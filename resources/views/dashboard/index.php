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
                <?php if (\App\Permission::can('migration_runner.view')): ?>
                <a href="<?= e(url('/migration-runner')) ?>" class="action-link">
                    <span class="action-link-title">Migration Runner</span>
                    <span class="action-link-desc">Review real database migration runner planning and safety rules</span>
                </a>
                <?php endif; ?>
                <?php if (\App\Permission::can('migration_files.view')): ?>
                <a href="<?= e(url('/migration-files')) ?>" class="action-link">
                    <span class="action-link-title">Migration Files</span>
                    <span class="action-link-desc">Review draft SQL migration files and manual apply rules</span>
                </a>
                <?php endif; ?>
                <?php if (\App\Permission::can('migration_dry_run.view')): ?>
                <a href="<?= e(url('/migration-dry-run')) ?>" class="action-link">
                    <span class="action-link-title">Migration Dry Run</span>
                    <span class="action-link-desc">Review migration dry-run validator planning and Red Issues behavior</span>
                </a>
                <?php endif; ?>
                <?php if (\App\Permission::can('migration_approval.view')): ?>
                <a href="<?= e(url('/migration-approval')) ?>" class="action-link">
                    <span class="action-link-title">Migration Approval</span>
                    <span class="action-link-desc">Review apply approval gate planning and future checklist requirements</span>
                </a>
                <?php endif; ?>
                <?php if (\App\Permission::can('migration_execution_lock.view')): ?>
                <a href="<?= e(url('/migration-execution-lock')) ?>" class="action-link">
                    <span class="action-link-title">Migration Execution Lock</span>
                    <span class="action-link-desc">Review execution lock planning, emergency stop, and manual-only readiness</span>
                </a>
                <?php endif; ?>
                <?php if (\App\Permission::can('build_queue.view')): ?>
                <a href="<?= e(url('/build-queue')) ?>" class="action-link">
                    <span class="action-link-title">Build Queue</span>
                    <span class="action-link-desc">Review semi-automation planning, checkpoint gates, and owner approval rules</span>
                </a>
                <?php endif; ?>
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
                <a href="<?= e(url('/order-workflow')) ?>" class="action-link">
                    <span class="action-link-title">Order Workflow</span>
                    <span class="action-link-desc">Review independent order workflow and dispatch gate planning</span>
                </a>
                <a href="<?= e(url('/dispatch-reports')) ?>" class="action-link">
                    <span class="action-link-title">Dispatch Reports</span>
                    <span class="action-link-desc">Review dispatch batch, lock, and cost snapshot planning</span>
                </a>
                <a href="<?= e(url('/supplier-payables')) ?>" class="action-link">
                    <span class="action-link-title">Supplier Payables</span>
                    <span class="action-link-desc">Review supplier payable and settlement planning</span>
                </a>
                <a href="<?= e(url('/return-receive')) ?>" class="action-link">
                    <span class="action-link-title">Return Receive</span>
                    <span class="action-link-desc">Review return receive and review batch planning</span>
                </a>
                <a href="<?= e(url('/status-mapping')) ?>" class="action-link">
                    <span class="action-link-title">Status Mapping</span>
                    <span class="action-link-desc">Review status mapping and sync planning foundation</span>
                </a>
                <a href="<?= e(url('/sync-preview')) ?>" class="action-link">
                    <span class="action-link-title">Sync Preview</span>
                    <span class="action-link-desc">Review sync preview and import safety planning</span>
                </a>
                <a href="<?= e(url('/invoice-printing')) ?>" class="action-link">
                    <span class="action-link-title">Invoice Printing</span>
                    <span class="action-link-desc">Review ERP invoice and packing print planning</span>
                </a>
                <a href="<?= e(url('/supplier-tools')) ?>" class="action-link">
                    <span class="action-link-title">Supplier Tools</span>
                    <span class="action-link-desc">Review independent supplier engagement tools planning</span>
                </a>
                <a href="<?= e(url('/manual-orders')) ?>" class="action-link">
                    <span class="action-link-title">Manual Orders</span>
                    <span class="action-link-desc">Review manual and external order planning</span>
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
                <li>Migration runner planning foundation without SQL execution</li>
                <li>Draft migration files planning without automatic apply</li>
                <li>Migration dry-run validator planning without database writes</li>
                <li>Migration approval gate planning without apply execution</li>
                <li>Migration execution lock planning without migration execution</li>
                <li>Build queue and semi-automation planning without auto commit or push</li>
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
    <?php if (\App\Permission::can('migration_runner.view')): ?>
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Migration Runner Planning</h2>
        </div>
        <div class="card-body">
            <p>Safe database migration runner planning — manual-only, dry-run/check-first, backup-before-apply, owner/admin confirmation, audit logging, rollback planning, production safety, and Red Issues Summary behavior without executing migration SQL.</p>
            <p class="page-description"><a href="<?= e(url('/migration-runner')) ?>">Open Migration Runner planning foundation</a></p>
        </div>
    </div>
    <?php endif; ?>

    <?php if (\App\Permission::can('migration_files.view')): ?>
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Migration Files Planning</h2>
        </div>
        <div class="card-body">
            <p>Manual SQL draft planning — real migration files under database/migrations for owner/admin review, backup-first manual apply, dry-run/check-first review, rollback planning, and Red Issues behavior.</p>
            <p class="page-description"><a href="<?= e(url('/migration-files')) ?>">Open Migration Files planning foundation</a></p>
        </div>
    </div>
    <?php endif; ?>

    <?php if (\App\Permission::can('migration_dry_run.view')): ?>
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Migration Dry Run Planning</h2>
        </div>
        <div class="card-body">
            <p>Future dry-run validator planning — scan migration files, validate safety and order, show warnings/red issues, and require owner approval before any future real apply.</p>
            <p class="page-description"><a href="<?= e(url('/migration-dry-run')) ?>">Open Migration Dry Run planning foundation</a></p>
        </div>
    </div>
    <?php endif; ?>

    <?php if (\App\Permission::can('migration_approval.view')): ?>
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Migration Approval Gate Planning</h2>
        </div>
        <div class="card-body">
            <p>Future apply approval gate planning — dry-run pass, backup confirmation, environment confirmation, checksum confirmation, rollback review, owner/admin approval, and Red Issues clear state before manual execution.</p>
            <p class="page-description"><a href="<?= e(url('/migration-approval')) ?>">Open Migration Approval planning foundation</a></p>
        </div>
    </div>
    <?php endif; ?>

    <?php if (\App\Permission::can('migration_execution_lock.view')): ?>
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Migration Execution Lock Planning</h2>
        </div>
        <div class="card-body">
            <p>Future execution lock planning — locked by default, waiting states for dry-run, backup, owner approval, clean Git, checksum, rollback review, Red Issues blocks, wrong environment blocks, emergency lock, and ready-but-manual-only status.</p>
            <p class="page-description"><a href="<?= e(url('/migration-execution-lock')) ?>">Open Migration Execution Lock planning foundation</a></p>
        </div>
    </div>
    <?php endif; ?>

    <?php if (\App\Permission::can('build_queue.view')): ?>
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Build Queue / Semi-Automation Planning</h2>
        </div>
        <div class="card-body">
            <p>Safe build queue planning — one task or one small approved batch, checkpoint-first, Red Issues stop rule, Git sync before next build, and manual owner approval before commit or push.</p>
            <p class="page-description"><a href="<?= e(url('/build-queue')) ?>">Open Build Queue planning foundation</a></p>
        </div>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Product Control Foundation</h2>
        </div>
        <div class="card-body">
            <p>Supplier product, cost, and stock planning for Iqbal &amp; Brothers — read-only platform fields, editable supplier model/cost/stock, cost/stock history, and low-stock warnings without OpenCart sync.</p>
            <p class="page-description"><a href="<?= e(url('/product-control')) ?>">Open Product Control foundation</a></p>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Order Workflow Planning</h2>
        </div>
        <div class="card-body">
            <p>Independent IBS order workflow planning — main and exception stages, allowed transition matrix, dispatch report gate, cost snapshot, and source mapping rules without order sync or database writes.</p>
            <p class="page-description"><a href="<?= e(url('/order-workflow')) ?>">Open Order Workflow planning foundation</a></p>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Dispatch Report Planning</h2>
        </div>
        <div class="card-body">
            <p>Dispatch batch / dispatch report planning — the locked gate after Shipped, single-supplier batches, batch reference format, batch locking, and cost snapshots for payable without dispatch tables or database writes.</p>
            <p class="page-description"><a href="<?= e(url('/dispatch-reports')) ?>">Open Dispatch Report planning foundation</a></p>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Supplier Payable Planning</h2>
        </div>
        <div class="card-body">
            <p>Supplier payable &amp; settlement planning for Iqbal &amp; Brothers — Product Cost Payable from dispatch snapshots, supplier invoices, additional payable, return/damage deductions, payments, advances, and net payable without payable tables or database writes.</p>
            <p class="page-description"><a href="<?= e(url('/supplier-payables')) ?>">Open Supplier Payable planning foundation</a></p>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Return Receive / Review Batch Planning</h2>
        </div>
        <div class="card-body">
            <p>Owner/admin Return Receive &amp; Review Batch planning — separates Supplier Return from Lokkisona Return, pre-submit total confirmation, return batches, and payable adjustment review without return tables or database writes.</p>
            <p class="page-description"><a href="<?= e(url('/return-receive')) ?>">Open Return Receive planning foundation</a></p>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Status Mapping / Sync Planning</h2>
        </div>
        <div class="card-body">
            <p>Status mapping and sync planning — source status to IBS workflow/return/courier rules, Test Sync preview counts, unmapped status safety, and independent IBS workflow after import without OpenCart connection or mapping writes.</p>
            <p class="page-description"><a href="<?= e(url('/status-mapping')) ?>">Open Status Mapping planning foundation</a></p>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Sync Preview / Import Safety</h2>
        </div>
        <div class="card-body">
            <p>Sync Preview and Import Safety planning — multi-source Lokkisona/Sonamoni/manual preview, shared vendor stock, ERP invoice planning, preview-before-import, duplicate blocking, and return candidate separation without live sync or database writes.</p>
            <p class="page-description"><a href="<?= e(url('/sync-preview')) ?>">Open Sync Preview planning foundation</a></p>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Invoice &amp; Packing Print Planning</h2>
        </div>
        <div class="card-body">
            <p>ERP invoice and packing print planning — source-aware customer invoice templates, internal packing/dispatch documents, PIT courier reference fields, print logs, and reprint rules without invoice generation or database writes.</p>
            <p class="page-description"><a href="<?= e(url('/invoice-printing')) ?>">Open Invoice Printing planning foundation</a></p>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Supplier Tools Planning</h2>
        </div>
        <div class="card-body">
            <p>Independent supplier engagement tools planning — quick invoice generator and simple calculator, separated from official ERP invoices, payables, stock, orders, courier, returns, sync/import, and accounting.</p>
            <p class="page-description"><a href="<?= e(url('/supplier-tools')) ?>">Open Supplier Tools planning foundation</a></p>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Manual / External Order Planning</h2>
        </div>
        <div class="card-body">
            <p>Manual and external order planning — Sonamoni WooCommerce reference entry, offline/manual orders, product mapping, shared vendor stock, cost snapshots, confirmation, and workflow entry without creating orders or syncing channels.</p>
            <p class="page-description"><a href="<?= e(url('/manual-orders')) ?>">Open Manual Orders planning foundation</a></p>
        </div>
    </div>
</div>
