<div class="page-header">
    <h1 class="page-title">Activity Log</h1>
    <p class="page-description">File-based runtime activity log with live read-only database inventory in v0.2.8. No activity log DB write from this page.</p>
</div>

<h2 class="section-heading" style="margin: 0 0 0.75rem;">Read-Only Activity Log Inventory (v0.2.8)</h2>
<p class="page-description" style="margin-bottom: 1rem;">SELECT only. No database writes. No activity log DB write. No migration apply from this page. File-based runtime logging below remains unchanged.</p>

<?php view('partials.read-inventory-card', ['readInventory' => $readInventory, 'cardTitle' => 'Database Activity Logs']); ?>

<h2 class="section-heading" style="margin: 1.5rem 0 1rem;">File-Based Runtime Activity</h2>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Recent Activity</h2>
    </div>
    <div class="card-body card-body-flush">
        <?php if (empty($entries)): ?>
            <div class="empty-state">
                No activity has been recorded yet.
            </div>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Action</th>
                        <th>User</th>
                        <th>Role</th>
                        <th>Message</th>
                        <th>Path</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($entries as $entry): ?>
                    <tr>
                        <td class="cell-detail"><?= e($entry['time'] ?? '') ?></td>
                        <td class="cell-name"><?= e($entry['action'] ?? '') ?></td>
                        <td><?= e($entry['user'] ?? 'guest') ?></td>
                        <td><?= e($entry['role'] ?? 'admin') ?></td>
                        <td><?= e($entry['message'] ?? '') ?></td>
                        <td class="cell-detail"><?= e($entry['path'] ?? '') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
