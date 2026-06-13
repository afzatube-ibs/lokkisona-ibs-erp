<?php
use App\Domain\OrderWorkflowStatus;

$workflowStageNav = $workflowStageNav ?? [];
$statusFilter = $statusFilter ?? null;
$stageHints = [
    'sfm_new' => 'Awaiting accept',
    'sfm_accepted' => 'Ready to pack',
    'sfm_packed' => 'Packed / ready for dispatch',
    'sfm_dispatched' => 'Dispatch statement',
    'sfm_delivered' => 'Completed',
    'sfm_returned' => 'Return in progress',
    'sfm_cancelled' => 'Cancelled / hold',
];
$mainByCode = [];
foreach ($workflowStageNav['main'] ?? [] as $stage) {
    $mainByCode[(string) ($stage['code'] ?? '')] = $stage;
}
$exceptionIcons = [
    'hold' => '<path d="M10 7.5v9M14 7.5v9"/>',
    'cancelled' => '<path d="M7.5 7.5l9 9M16.5 7.5l-9 9"/>',
    'delivery_stop' => '<path d="M8.5 11V8a3.5 3.5 0 0 1 7 0v3"/><path d="M6.5 11h11v6.5a2 2 0 0 1-2 2h-7a2 2 0 0 1-2-2V11z"/>',
    'hub_return' => '<path d="M9 8.5H5.5V5"/><path d="M5.5 8.5A6.5 6.5 0 1 1 6 17"/>',
    'order_returning' => '<path d="M16 8.5h3.5V5"/><path d="M19.5 8.5A6.5 6.5 0 1 0 19 17"/><path d="M8 15.5H4.5V19"/><path d="M4.5 15.5A6.5 6.5 0 1 1 5 7"/>',
];
?>
<?php if (!empty($workflowStageNav)): ?>
<div class="vf-stages-section">
<div class="workflow-stages-panel vf-status-cards-panel">
    <section class="vf-stage-group vf-stage-group--all">
        <span class="vf-stage-group-legend">Overview</span>
        <div class="workflow-stage-grid vf-status-cards vf-status-cards--group vf-status-cards--all">
            <a href="<?= e(url($workflowStageNav['all_url'] ?? '/order-workflow?from_card=1')) ?>" class="workflow-stage-card workflow-stage-link vf-stage-kpi-card workflow-accent-muted<?= !empty($workflowStageNav['all_active']) ? ' is-active' : '' ?>">
                <span class="workflow-stage-label kpi-label">All</span>
                <span class="workflow-stage-value kpi-value"><?= e((string) ((int) ($workflowStageNav['all_count'] ?? 0))) ?></span>
                <span class="workflow-stage-hint kpi-hint">Every workflow status</span>
            </a>
        </div>
    </section>
    <?php foreach (OrderWorkflowStatus::releaseStatusCardGroups() as $group): ?>
    <?php
    $groupKey = (string) ($group['key'] ?? '');
    $groupLabel = (string) ($group['label'] ?? '');
    $groupCodes = $group['codes'] ?? [];
    ?>
    <section class="vf-stage-group vf-stage-group--<?= e($groupKey) ?>">
        <span class="vf-stage-group-legend"><?= e($groupLabel) ?></span>
        <div class="workflow-stage-grid vf-status-cards vf-status-cards--group vf-status-cards--<?= e($groupKey) ?>">
            <?php foreach ($groupCodes as $code): ?>
            <?php
            $stage = $mainByCode[$code] ?? null;
            if ($stage === null) {
                continue;
            }
            $count = (int) ($stage['count'] ?? 0);
            $hint = $stageHints[$code] ?? 'SFM stage';
            ?>
            <a href="<?= e(url($stage['url'] ?? '/order-workflow')) ?>" class="workflow-stage-card workflow-stage-link vf-stage-kpi-card <?= e(OrderWorkflowStatus::stageAccentClass($code)) ?><?= !empty($stage['active']) ? ' is-active' : '' ?><?= $code === 'sfm_dispatched' ? ' vf-stage-kpi-card--created-report' : '' ?>">
                <span class="workflow-stage-label kpi-label"><?= e($stage['label']) ?></span>
                <?php if ($code === 'sfm_dispatched'): ?>
                <span class="workflow-stage-value kpi-value<?= $count === 0 ? ' workflow-stage-value-zero' : '' ?>"><?= e((string) $count) ?> Orders</span>
                <span class="workflow-stage-hint kpi-hint vf-stage-batch-hint">Latest Batch:<br><strong><?= e($stage['latest_batch'] ?? '--') ?></strong></span>
                <?php else: ?>
                <span class="workflow-stage-value kpi-value<?= $count === 0 ? ' workflow-stage-value-zero' : '' ?>"><?= e((string) $count) ?></span>
                <span class="workflow-stage-hint kpi-hint"><?= e($hint) ?></span>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endforeach; ?>
</div>
<?php if (!empty($workflowStageNav['exceptions'])): ?>
<div class="vf-followup-block">
    <div class="workflow-chip-row vf-exception-chips">
        <span class="vf-followup-label">Follow Up:</span>
        <?php foreach ($workflowStageNav['exceptions'] as $stage): ?>
        <?php
        $code = (string) ($stage['code'] ?? '');
        $chipCount = (int) ($stage['count'] ?? 0);
        ?>
        <a href="<?= e(url($stage['url'] ?? '/order-workflow')) ?>" class="workflow-chip workflow-chip-link vf-followup-chip<?= !empty($stage['active']) ? ' is-active' : '' ?>">
            <?php if (!empty($exceptionIcons[$code])): ?>
            <span class="vf-followup-chip-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><?= $exceptionIcons[$code] ?></svg>
            </span>
            <?php endif; ?>
            <span class="vf-followup-chip-label"><?= e($stage['label']) ?></span>
            <span class="vf-followup-chip-count"><?= e((string) $chipCount) ?></span>
        </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>
</div>
<?php endif; ?>
