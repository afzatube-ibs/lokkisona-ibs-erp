<div class="page-header">
    <h1 class="page-title">Health Check</h1>
    <p class="page-description">System diagnostics and environment verification.</p>
</div>

<div class="status-banner status-banner-<?= e($overall) ?>">
    <?php if ($overall === 'ok'): ?>
        <strong>All systems operational</strong> — Core checks passed.
    <?php elseif ($overall === 'warn'): ?>
        <strong>Attention required</strong> — Some checks need configuration.
    <?php else: ?>
        <strong>Critical issues detected</strong> — Review failed checks below.
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Diagnostic Results</h2>
    </div>
    <div class="card-body card-body-flush">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Check</th>
                    <th>Status</th>
                    <th>Message</th>
                    <th>Detail</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($checks as $check): ?>
                <tr>
                    <td class="cell-name"><?= e($check['name']) ?></td>
                    <td>
                        <span class="badge badge-<?= e($check['status']) ?>">
                            <?= e(strtoupper($check['status'])) ?>
                        </span>
                    </td>
                    <td><?= e($check['message']) ?></td>
                    <td class="cell-detail"><?= e($check['detail']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
