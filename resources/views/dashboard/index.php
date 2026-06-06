<?php if (!empty($isSupplierView)): ?>

<div class="page-header">
    <h1 class="page-title">Dashboard</h1>
    <p class="dash-welcome">Supplier fulfillment — Iqbal &amp; Brothers</p>
</div>

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
    <div class="card-header card-header-flex">
        <h2 class="card-title">Recent Activity</h2>
    </div>
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

<div class="page-header page-header-compact">
    <h1 class="page-title">Dashboard</h1>
    <p class="dash-welcome">Welcome back, <?= e($currentUser ?? 'Owner') ?> — <?= e($welcomeDate ?? date('l, d F Y')) ?></p>
</div>

<div class="kpi-grid">
    <a href="<?= e(url('/order-workflow')) ?>" class="kpi-card kpi-accent-primary">
        <span class="kpi-label">Active Orders</span>
        <span class="kpi-value"><?= e((string) ($ownerMetrics['active_fulfillment_orders'] ?? 0)) ?></span>
        <span class="kpi-hint">In fulfillment workflow</span>
    </a>
    <a href="<?= e(url('/dispatch-reports')) ?>" class="kpi-card kpi-accent-info">
        <span class="kpi-label">Shipped Awaiting Dispatch</span>
        <span class="kpi-value"><?= e((string) ($ownerMetrics['shipped_awaiting_dispatch'] ?? 0)) ?></span>
        <span class="kpi-hint">Ready for daily batch</span>
    </a>
    <a href="<?= e(url('/supplier-payables')) ?>" class="kpi-card kpi-accent-warn">
        <span class="kpi-label">Net Payable</span>
        <span class="kpi-value"><?= e(number_format((float) ($ownerMetrics['net_payable'] ?? 0), 2)) ?></span>
        <span class="kpi-hint">BDT · ledger balance</span>
    </a>
    <a href="<?= e(url('/return-receive')) ?>" class="kpi-card kpi-accent-success">
        <span class="kpi-label">Pending Returns</span>
        <span class="kpi-value"><?= e((string) ($ownerMetrics['pending_returns'] ?? 0)) ?></span>
        <span class="kpi-hint">Awaiting receive / batch</span>
    </a>
    <a href="<?= e(url('/supplier-payables')) ?>" class="kpi-card kpi-accent-error">
        <span class="kpi-label">Payable Drafts</span>
        <span class="kpi-value"><?= e((string) ($ownerMetrics['pending_draft_entries'] ?? 0)) ?></span>
        <span class="kpi-hint">Pending owner post</span>
    </a>
    <div class="kpi-card kpi-accent-muted">
        <span class="kpi-label">Dispatch Snapshots</span>
        <span class="kpi-value"><?= e(number_format((float) ($ownerMetrics['dispatch_snapshot_total'] ?? 0), 2)) ?></span>
        <span class="kpi-hint">BDT locked total</span>
    </div>
</div>

<div class="dash-widget-grid">
    <div class="card">
        <div class="card-header card-header-flex">
            <h2 class="card-title">Order Status Breakdown</h2>
            <a href="<?= e(url('/order-workflow')) ?>" class="btn btn-sm btn-secondary">View All</a>
        </div>
        <div class="card-body card-body-flush">
            <?php if (!empty($workflowStageCounts)): ?>
            <div class="table-scroll">
                <table class="data-table data-table-compact">
                    <thead>
                        <tr>
                            <th>Stage</th>
                            <th>Orders</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($workflowStageCounts as $stage): ?>
                        <tr>
                            <td><?= e((string) ($stage['label'] ?? '')) ?></td>
                            <td><strong><?= e((string) ($stage['count'] ?? 0)) ?></strong></td>
                            <td><a href="<?= e(url((string) ($stage['url'] ?? '/order-workflow'))) ?>" class="btn btn-sm btn-ghost">Open</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state"><p>No active orders in workflow yet.</p></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header card-header-flex">
            <h2 class="card-title">Needs Attention</h2>
        </div>
        <div class="card-body">
            <?php if (!empty($needsAttention)): ?>
            <ul class="attention-list">
                <?php foreach ($needsAttention as $item): ?>
                <li class="attention-item attention-<?= e((string) ($item['tone'] ?? 'primary')) ?>">
                    <a href="<?= e(url((string) ($item['url'] ?? '#'))) ?>" class="attention-link">
                        <span class="attention-count"><?= e((string) ($item['count'] ?? 0)) ?></span>
                        <span class="attention-label"><?= e((string) ($item['label'] ?? '')) ?></span>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php else: ?>
            <div class="empty-state"><p>Nothing urgent right now.</p></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card mt-15">
    <div class="card-header card-header-flex">
        <h2 class="card-title">Recent Activity</h2>
        <a href="<?= e(url('/activity-log')) ?>" class="btn btn-sm btn-secondary">Activity Log</a>
    </div>
    <div class="card-body">
        <?php if (!empty($recentNotes)): ?>
        <ul class="feature-list">
            <?php foreach ($recentNotes as $note): ?>
                <li><strong><?= e($note['time']) ?></strong> — <?= e($note['text']) ?></li>
            <?php endforeach; ?>
        </ul>
        <?php else: ?>
        <div class="empty-state"><p>No recent workflow or payable activity yet.</p></div>
        <?php endif; ?>
    </div>
</div>

<?php endif; ?>
