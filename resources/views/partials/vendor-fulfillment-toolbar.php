<?php
use App\Domain\OrderWorkflowStatus;

$filters = $fulfillmentFilters ?? [];
$statusFilter = $statusFilter ?? null;
$canManage = !empty($canManageWorkflow);
$bulkActionForFilter = $bulkActionForFilter ?? null;
$bulkActionLabelForFilter = $bulkActionLabelForFilter ?? null;
$statusFilterOptions = $statusFilterOptions ?? OrderWorkflowStatus::filterStatusOptions();
$perPage = (int) ($filters['per_page'] ?? 20);
$clearUrl = url('/order-workflow');
$toolbarAction = url('/order-workflow');
?>
<div class="vf-ops-toolbar" id="vfToolbar"
    data-bulk-filter="<?= e((string) ($bulkActionForFilter ?? '')) ?>"
    data-bulk-label="<?= e((string) ($bulkActionLabelForFilter ?? '')) ?>"
    data-status-filter="<?= e((string) ($statusFilter ?? '')) ?>">
    <form method="get" action="<?= e($toolbarAction) ?>" class="vf-toolbar-form" id="vfToolbarForm">
        <div class="vf-toolbar-row vf-toolbar-row-ops">
            <?php if ($canManage): ?>
            <div class="vf-bulk-bar" id="vfBulkBar">
                <span class="vf-selected-count"><strong id="vfSelectedCount">0</strong> selected</span>
                <button type="button" class="btn btn-primary btn-sm js-vf-bulk-forward" id="vfBulkForwardBtn" hidden disabled data-bulk-action=""><?= e((string) ($bulkActionLabelForFilter ?? 'Bulk action')) ?></button>
                <button type="button" class="btn btn-secondary btn-sm js-vf-bulk-hold" id="vfBulkHoldBtn" hidden disabled>Hold</button>
                <button type="button" class="btn btn-secondary btn-sm js-vf-bulk-cancel" id="vfBulkCancelBtn" hidden disabled>Cancel</button>
                <span class="vf-bulk-hint page-description" id="vfBulkHint" hidden>Mixed statuses — select rows in one stage only.</span>
            </div>
            <?php endif; ?>

            <div class="vf-toolbar-filters">
                <input type="search" name="q" class="form-input form-input-sm vf-toolbar-search-input" value="<?= e((string) ($filters['q'] ?? '')) ?>" placeholder="Search order, customer, mobile, product, model" aria-label="Search orders">
                <select name="status" class="form-input form-input-sm vf-toolbar-status-select" aria-label="Status filter">
                    <?php foreach ($statusFilterOptions as $option): ?>
                    <?php $code = (string) ($option['code'] ?? ''); ?>
                    <?php $selected = ($code === '' && $statusFilter === null) || ($code !== '' && $statusFilter === $code); ?>
                    <option value="<?= e($code) ?>" <?= $selected ? 'selected' : '' ?>><?= e((string) ($option['label'] ?? '')) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="courier_status" class="form-input form-input-sm vf-toolbar-courier-input" value="<?= e((string) ($filters['courier_status'] ?? '')) ?>" placeholder="Courier" aria-label="Courier status">
                <select name="per_page" class="form-input form-input-sm vf-toolbar-per-page-select" aria-label="Per page">
                    <option value="20" <?= $perPage === 20 ? 'selected' : '' ?>>20</option>
                    <option value="50" <?= $perPage === 50 ? 'selected' : '' ?>>50</option>
                </select>
                <?php if (!empty($filters['supplier_id'])): ?>
                <input type="hidden" name="supplier_id" value="<?= e((string) $filters['supplier_id']) ?>">
                <?php endif; ?>
                <a href="<?= e($clearUrl) ?>" class="btn btn-ghost btn-sm">Clear</a>
            </div>
        </div>
    </form>
</div>
