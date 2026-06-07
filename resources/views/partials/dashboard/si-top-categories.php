<?php
$top_categories = $top_categories ?? [];
?>
<section class="card si-top-categories">
    <div class="card-header">
        <h2 class="card-title">Top Categories</h2>
    </div>
    <div class="card-body">
        <?php if ($top_categories !== []): ?>
        <ul class="si-bar-chart">
            <?php foreach ($top_categories as $bar): ?>
            <li class="si-bar-row">
                <span class="si-bar-label"><?= e((string) ($bar['category'] ?? '')) ?></span>
                <div class="si-bar-track">
                    <div class="si-bar-fill" style="width: <?= e((string) min(100, max(0, (float) ($bar['pct'] ?? 0)))) ?>%;"></div>
                </div>
                <span class="si-bar-value"><?= (float) ($bar['value'] ?? 0) > 0 ? e(number_format((float) $bar['value'], 0)) . ' BDT' : '—' ?></span>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php else: ?>
        <p class="si-empty-note">No category breakdown available.</p>
        <?php endif; ?>
    </div>
</section>
