<?php if (empty($writeGateReady)): ?>
<div class="card" style="margin-bottom: 1.5rem; border-left: 4px solid var(--color-warn, #d97706);">
    <div class="card-header">
        <h2 class="card-title">Write Form Blocked</h2>
    </div>
    <div class="card-body">
        <p class="page-description"><?= e($writeGateMessage ?? \App\ReadFoundation\WriteGate::WARNING_MESSAGE) ?></p>
        <p class="page-description"><a href="<?= e(url('/dev-db-activation')) ?>">Open Dev DB Activation</a> to review migration groups and table readiness.</p>
        <?php if (!empty($writeGate['missing_tables'])): ?>
            <p class="page-description" style="margin-top: 0.75rem;">Missing or unavailable tables:</p>
            <div class="planned-table-grid">
                <?php foreach ($writeGate['missing_tables'] as $table): ?>
                    <code><?= e($table) ?></code>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
