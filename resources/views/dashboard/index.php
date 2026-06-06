<div class="page-header">
    <h1 class="page-title">Dashboard</h1>
    <p class="page-description">
        <?php if (!empty($isSupplierView)): ?>
            Supplier fulfillment tasks — Iqbal &amp; Brothers. Action-focused view only.
        <?php else: ?>
            Owner / Admin control center — v0.4.9.0. IBS-LK Business Manager for Lokkisona / Sonamoni supplier operations.
        <?php endif; ?>
    </p>
</div>

<?php if (!empty($isSupplierView)): ?>

<div class="task-grid">
    <?php foreach ($supplierTasks as $task): ?>
    <a href="<?= e(url($task['path'])) ?>" class="task-card <?= (int) ($task['count'] ?? 0) > 0 ? 'task-card-active' : '' ?>">
        <span class="task-card-count"><?= e((string) ($task['count'] ?? 0)) ?></span>
        <span class="task-card-label"><?= e($task['label']) ?></span>
    </a>
    <?php endforeach; ?>
</div>

<?php if (!empty($canUseCalculator) || !empty($canUseQuickInvoice)): ?>
<div class="engage-tool-grid">
    <?php if (!empty($canUseCalculator)): ?>
    <button type="button" class="engage-tool-card" data-open-modal="supplierCalculatorModal">
        <svg class="engage-tool-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="2" width="16" height="20" rx="2"/><line x1="8" y1="6" x2="16" y2="6"/><line x1="8" y1="10" x2="8" y2="10.01"/><line x1="12" y1="10" x2="12" y2="10.01"/><line x1="16" y1="10" x2="16" y2="10.01"/></svg>
        <span class="engage-tool-label">Calculator</span>
    </button>
    <?php endif; ?>
    <?php if (!empty($canUseQuickInvoice)): ?>
    <button type="button" class="engage-tool-card" data-open-modal="supplierQuickInvoiceModal">
        <svg class="engage-tool-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 13h8M8 17h6"/></svg>
        <span class="engage-tool-label">Quick Invoice</span>
    </button>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if (!empty($recentNotes)): ?>
<div class="card mt-15">
    <div class="card-header"><h2 class="card-title">Recent Notes</h2></div>
    <div class="card-body">
        <ul class="feature-list">
            <?php foreach ($recentNotes as $note): ?>
                <li><strong><?= e($note['time']) ?></strong> — <?= e($note['text']) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
<?php endif; ?>

<?php else: ?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon stat-icon-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        </div>
        <div class="stat-content">
            <span class="stat-label">Net Supplier Payable</span>
            <span class="stat-value"><?= e(number_format((float) ($ownerMetrics['net_payable'] ?? 0), 2)) ?> BDT</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon stat-icon-warn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13" rx="1"/><path d="M16 8h4l3 3v5a1 1 0 0 1-1 1h-6z"/></svg>
        </div>
        <div class="stat-content">
            <span class="stat-label">Dispatch Cost Snapshots</span>
            <span class="stat-value"><?= e(number_format((float) ($ownerMetrics['dispatch_snapshot_total'] ?? 0), 2)) ?> BDT</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon stat-icon-info">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7v6h6"/><path d="M3 13a9 9 0 1 0 3-7.7L3 8"/></svg>
        </div>
        <div class="stat-content">
            <span class="stat-label">Pending Returns</span>
            <span class="stat-value"><?= e((string) ($ownerMetrics['pending_returns'] ?? 0)) ?></span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon stat-icon-success">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
        </div>
        <div class="stat-content">
            <span class="stat-label">Payable Drafts Pending</span>
            <span class="stat-value"><?= e((string) ($ownerMetrics['pending_draft_entries'] ?? 0)) ?></span>
        </div>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header"><h2 class="card-title">Quick Control</h2></div>
        <div class="card-body">
            <div class="action-links">
                <a href="<?= e(url('/order-workflow')) ?>" class="action-link">
                    <span class="action-link-title">Order Workflow</span>
                    <span class="action-link-desc"><?= e((string) ($ownerMetrics['active_fulfillment_orders'] ?? 0)) ?> active fulfillment orders</span>
                </a>
                <a href="<?= e(url('/dispatch-reports')) ?>" class="action-link">
                    <span class="action-link-title">Dispatch Reports</span>
                    <span class="action-link-desc">Daily dispatch batch and locked cost snapshots</span>
                </a>
                <a href="<?= e(url('/supplier-payables')) ?>" class="action-link">
                    <span class="action-link-title">Supplier Payables</span>
                    <span class="action-link-desc">Approve payable drafts and manage ledger</span>
                </a>
                <a href="<?= e(url('/return-receive')) ?>" class="action-link">
                    <span class="action-link-title">Return Receive</span>
                    <span class="action-link-desc">Confirm supplier and warehouse returns</span>
                </a>
                <a href="<?= e(url('/reports')) ?>" class="action-link">
                    <span class="action-link-title">Reports</span>
                    <span class="action-link-desc">Ledger, dispatch, return, and statement reports</span>
                </a>
                <a href="<?= e(url('/activity-log')) ?>" class="action-link">
                    <span class="action-link-title">Activity Log</span>
                    <span class="action-link-desc">Audit trail for important actions</span>
                </a>
                <a href="<?= e(url('/dev-db-activation')) ?>" class="action-link">
                    <span class="action-link-title">Dev DB Activation</span>
                    <span class="action-link-desc">Table readiness before write testing</span>
                </a>
            </div>
            <p class="page-description mt-1">Sync status: <?= e($ownerMetrics['sync_status'] ?? 'Unknown') ?></p>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h2 class="card-title">System</h2></div>
        <div class="card-body">
            <dl class="info-list">
                <div class="info-row"><dt>Version</dt><dd>v<?= e($appVersion) ?></dd></div>
                <div class="info-row"><dt>Role</dt><dd><?= e($currentRole ?? 'owner') ?></dd></div>
                <div class="info-row"><dt>Signed in</dt><dd><?= e($currentUser) ?></dd></div>
            </dl>
        </div>
    </div>
</div>

<?php if (!empty($recentNotes)): ?>
<div class="card mt-15">
    <div class="card-header"><h2 class="card-title">Recent Activity</h2></div>
    <div class="card-body">
        <ul class="feature-list">
            <?php foreach ($recentNotes as $note): ?>
                <li><strong><?= e($note['time']) ?></strong> — <?= e($note['text']) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
<?php endif; ?>

<details class="dev-collapse mt-15">
    <summary>Planning Foundation (collapsed)</summary>
    <div class="dev-collapse-body">
        <p class="page-description">Legacy planning cards and migration documentation remain available from the sidebar. Core operational modules are live when dev DB tables are applied.</p>
        <div class="action-links">
            <a href="<?= e(url('/database-safety')) ?>" class="action-link"><span class="action-link-title">Database Safety</span></a>
            <a href="<?= e(url('/build-queue')) ?>" class="action-link"><span class="action-link-title">Build Queue</span></a>
            <a href="<?= e(url('/version')) ?>" class="action-link"><span class="action-link-title">Version Info</span></a>
        </div>
    </div>
</details>

<?php endif; ?>
