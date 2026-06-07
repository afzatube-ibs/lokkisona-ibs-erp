<?php
$performance = $performance ?? [];
$score = (int) ($performance['score'] ?? 0);
$grade = (string) ($performance['grade'] ?? 'B');
$breakdown = $performance['breakdown'] ?? [];
?>
<section class="card si-performance">
    <div class="card-header card-header-flex">
        <h2 class="card-title">IBS Performance Score</h2>
        <span class="si-grade-badge"><?= e($grade) ?></span>
    </div>
    <div class="card-body">
        <div class="si-performance-score-row">
            <span class="si-performance-big"><?= e((string) $score) ?></span>
            <span class="si-performance-of">/100</span>
        </div>
        <div class="si-performance-bars">
            <?php foreach ($breakdown as $row): ?>
            <div class="si-performance-bar-row">
                <div class="si-performance-bar-head">
                    <span><?= e((string) ($row['label'] ?? '')) ?></span>
                    <span><?= e((string) ($row['score'] ?? 0)) ?>% · weight <?= e((string) ($row['weight_pct'] ?? 0)) ?>%</span>
                </div>
                <div class="si-progress-track">
                    <div class="si-progress-fill" style="width: <?= e((string) min(100, max(0, (int) ($row['score'] ?? 0)))) ?>%;"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
