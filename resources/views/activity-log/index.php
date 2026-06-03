<div class="page-header">
    <h1 class="page-title">Activity Log</h1>
    <p class="page-description">Recent authentication and system access events.</p>
</div>

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
