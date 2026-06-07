<?php
$returns = $returns ?? [];
?>
<section class="card si-returns">
    <div class="card-header">
        <h2 class="card-title">Return Intelligence</h2>
    </div>
    <div class="card-body">
        <div class="si-returns-grid">
            <div class="si-returns-stat">
                <span class="si-returns-value"><?= e((string) ($returns['hub_return'] ?? 0)) ?></span>
                <span class="si-returns-label">Hub return</span>
            </div>
            <div class="si-returns-stat">
                <span class="si-returns-value"><?= e((string) ($returns['customer_return'] ?? 0)) ?></span>
                <span class="si-returns-label">Customer return</span>
            </div>
            <div class="si-returns-stat">
                <span class="si-returns-value"><?= e(number_format((float) ($returns['return_rate_pct'] ?? 0), 1)) ?>%</span>
                <span class="si-returns-label">Return rate</span>
            </div>
            <div class="si-returns-stat">
                <span class="si-returns-value"><?= e(number_format((float) ($returns['deduction_amount'] ?? 0), 0)) ?></span>
                <span class="si-returns-label">Deduction BDT</span>
            </div>
        </div>
        <div class="si-returns-breakdown">
            <span>Reusable: <?= e((string) ($returns['reusable'] ?? 0)) ?></span>
            <span>Damaged: <?= e((string) ($returns['damaged'] ?? 0)) ?></span>
            <span>Broken: <?= e((string) ($returns['broken'] ?? 0)) ?></span>
        </div>
        <?php $reasons = $returns['top_return_reasons'] ?? []; ?>
        <?php if ($reasons !== []): ?>
        <h3 class="si-section-label">Common reasons</h3>
        <ul class="si-reason-list">
            <?php foreach ($reasons as $reason): ?>
            <li><?= e((string) $reason) ?></li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
</section>
