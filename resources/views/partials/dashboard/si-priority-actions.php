<?php
$priority_actions = $priority_actions ?? [];
?>
<section class="si-priority-actions">
    <div class="si-priority-head">
        <h2 class="si-priority-title">Priority Actions</h2>
    </div>
    <div class="si-priority-grid">
        <?php foreach ($priority_actions as $action): ?>
        <article class="card si-priority-card si-priority-<?= e((string) ($action['tone'] ?? 'primary')) ?>">
            <h3 class="si-priority-card-title"><?= e((string) ($action['title'] ?? '')) ?></h3>
            <a href="<?= e(url((string) ($action['url'] ?? '#'))) ?>" class="btn btn-sm btn-secondary"><?= e((string) ($action['cta'] ?? 'View')) ?></a>
        </article>
        <?php endforeach; ?>
    </div>
</section>
