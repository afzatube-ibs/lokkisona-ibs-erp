<?php
$insights = $insights ?? [];
$items = $insights['items'] ?? [];
?>
<section class="si-insight-strip card">
    <div class="si-insight-items">
        <?php foreach ($items as $item): ?>
        <span class="si-insight-chip si-insight-<?= e((string) ($item['tone'] ?? 'info')) ?>">
            <?= e((string) ($item['label'] ?? '')) ?>
        </span>
        <?php endforeach; ?>
    </div>
    <a href="<?= e(url('/reports')) ?>" class="btn btn-sm btn-ghost si-insight-link">View All Insights</a>
</section>
