<?php
$dispatch_sla = $dispatch_sla ?? [];
$dispatch_pipeline = $dispatch_pipeline ?? [];
$workflow = $dispatch_pipeline['workflow'] ?? [];
$exceptions = $dispatch_pipeline['exceptions'] ?? [];
$sampleSize = (int) ($dispatch_sla['sample_size'] ?? 0);
?>
<section class="card si-dispatch">
    <div class="card-header">
        <h2 class="card-title">Dispatch Intelligence</h2>
        <p class="card-subtitle"><?= e((string) ($dispatch_sla['method_label'] ?? 'Order Received → Created Report')) ?></p>
    </div>
    <div class="card-body">
        <?php if ($sampleSize > 0): ?>
        <div class="si-sla-stats">
            <div class="si-sla-stat">
                <span class="si-sla-stat-value"><?= e(number_format((float) ($dispatch_sla['on_time_rate'] ?? 0), 1)) ?>%</span>
                <span class="si-sla-stat-label">On-time rate</span>
            </div>
            <div class="si-sla-stat">
                <span class="si-sla-stat-value"><?= e(number_format((float) ($dispatch_sla['avg_hours'] ?? 0), 1)) ?>h</span>
                <span class="si-sla-stat-label">Average</span>
            </div>
            <div class="si-sla-stat">
                <span class="si-sla-stat-value"><?= e(number_format((float) ($dispatch_sla['fastest_hours'] ?? 0), 1)) ?>h</span>
                <span class="si-sla-stat-label">Fastest</span>
            </div>
            <div class="si-sla-stat">
                <span class="si-sla-stat-value"><?= e(number_format((float) ($dispatch_sla['slowest_hours'] ?? 0), 1)) ?>h</span>
                <span class="si-sla-stat-label">Slowest</span>
            </div>
            <div class="si-sla-stat si-sla-stat-warn">
                <span class="si-sla-stat-value"><?= e((string) ($dispatch_sla['late_count'] ?? 0)) ?></span>
                <span class="si-sla-stat-label">Late (&gt;12h)</span>
            </div>
        </div>
        <?php else: ?>
        <p class="si-empty-note">No dispatch SLA samples yet — metrics appear after orders move from Order Received to Created Report.</p>
        <?php endif; ?>
        <p class="si-footnote"><?= e((string) ($dispatch_sla['footnote'] ?? '12h SLA target — not courier delivery')) ?></p>

        <h3 class="si-section-label">Pipeline</h3>
        <div class="si-pipeline-cards">
            <?php foreach ($workflow as $stage): ?>
            <a href="<?= e(url((string) ($stage['url'] ?? '/order-workflow'))) ?>" class="si-pipeline-card">
                <span class="si-pipeline-count"><?= e((string) ($stage['count'] ?? 0)) ?></span>
                <span class="si-pipeline-label"><?= e((string) ($stage['label'] ?? '')) ?></span>
            </a>
            <?php endforeach; ?>
        </div>

        <?php if ($exceptions !== []): ?>
        <h3 class="si-section-label">Exceptions</h3>
        <div class="si-pipeline-cards si-pipeline-exceptions">
            <?php foreach ($exceptions as $stage): ?>
            <a href="<?= e(url((string) ($stage['url'] ?? '/order-workflow'))) ?>" class="si-pipeline-card si-pipeline-card-warn">
                <span class="si-pipeline-count"><?= e((string) ($stage['count'] ?? 0)) ?></span>
                <span class="si-pipeline-label"><?= e((string) ($stage['label'] ?? '')) ?></span>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>
