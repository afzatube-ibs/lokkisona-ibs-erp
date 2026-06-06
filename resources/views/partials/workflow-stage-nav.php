<?php
use App\Domain\OrderWorkflowStatus;

$workflowStageNav = $workflowStageNav ?? [];
$statusFilter = $statusFilter ?? null;
?>
<?php if (!empty($workflowStageNav)): ?>
<div class="card workflow-stages-panel" style="margin-bottom: 1.5rem;">
    <div class="card-header"><h2 class="card-title">Workflow Stages</h2></div>
    <div class="card-body">
        <div class="workflow-filter-row">
            <a href="<?= e(url($workflowStageNav['all_url'] ?? '/order-workflow')) ?>" class="workflow-filter-pill<?= !empty($workflowStageNav['all_active']) ? ' is-active' : '' ?>">All stages</a>
        </div>
        <div class="workflow-stage-grid">
            <?php foreach ($workflowStageNav['main'] ?? [] as $stage): ?>
            <?php
            $count = (int) ($stage['count'] ?? 0);
            $code = (string) ($stage['code'] ?? '');
            ?>
            <a href="<?= e(url($stage['url'] ?? '/order-workflow')) ?>" class="workflow-stage-card workflow-stage-link <?= e(OrderWorkflowStatus::stageAccentClass($code)) ?><?= !empty($stage['active']) ? ' is-active' : '' ?>">
                <span class="workflow-stage-label"><?= e($stage['label']) ?></span>
                <span class="workflow-stage-value<?= $count === 0 ? ' workflow-stage-value-zero' : '' ?>"><?= e((string) $count) ?></span>
            </a>
            <?php endforeach; ?>
        </div>
        <?php if (!empty($workflowStageNav['exceptions'])): ?>
        <div class="workflow-chip-row">
            <?php foreach ($workflowStageNav['exceptions'] as $stage): ?>
            <?php $code = (string) ($stage['code'] ?? ''); ?>
            <a href="<?= e(url($stage['url'] ?? '/order-workflow')) ?>" class="workflow-chip workflow-chip-link <?= e(OrderWorkflowStatus::stageAccentClass($code)) ?><?= !empty($stage['active']) ? ' is-active' : '' ?>"><?= e($stage['label']) ?> <strong><?= e((string) ((int) ($stage['count'] ?? 0))) ?></strong></a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php if ($statusFilter !== null): ?>
        <p class="page-description workflow-filter-note">Showing <strong><?= e(OrderWorkflowStatus::groupDisplayLabel($statusFilter)) ?></strong> only. <a href="<?= e(url('/order-workflow')) ?>">Clear filter</a></p>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
