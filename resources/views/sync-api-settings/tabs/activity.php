<?php
$syncHistory = $syncHistory ?? [];
$connectionSummary = $connectionSummary ?? [];
?>
<div class="card sync-hub-card-wide">
    <div class="card-header">
        <h2 class="card-title">Recent Activity</h2>
        <a href="<?= e(url('/activity-log')) ?>" class="btn btn-secondary btn-sm">View full activity log</a>
    </div>
    <div class="card-body">
        <?php if ($syncHistory !== []): ?>
        <div class="table-scroll">
            <table class="data-table">
                <thead><tr><th>Time</th><th>Action</th><th>Details</th></tr></thead>
                <tbody>
                    <?php foreach ($syncHistory as $entry): ?>
                    <tr>
                        <td><?= e((string) ($entry['time'] ?? '')) ?></td>
                        <td><code><?= e((string) ($entry['action'] ?? '')) ?></code></td>
                        <td><?= e((string) ($entry['message'] ?? '')) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="sync-hub-empty">No sync activity logged yet.</div>
        <?php endif; ?>
        <p class="form-help mt-15">Last product sync: <?= e((string) ($connectionSummary['last_product_sync_at'] ?? '—')) ?> · Last order sync: <?= e((string) ($connectionSummary['last_order_sync_at'] ?? '—')) ?></p>
    </div>
</div>
