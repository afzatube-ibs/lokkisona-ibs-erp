<?php
use App\Domain\OrderWorkflowStatus;

$filters = $fulfillmentFilters ?? [];
$statusFilter = $statusFilter ?? null;
$canManage = !empty($canManageWorkflow);
$bulkActionForFilter = $bulkActionForFilter ?? null;
$perPage = (int) ($filters['per_page'] ?? 20);
$clearUrl = url('/order-workflow');
$searchAction = url('/order-workflow');
if ($statusFilter !== null) {
    $searchAction .= '?status=' . rawurlencode($statusFilter);
}
?>
<div class="vf-toolbar card mb-15" id="vfToolbar"
    data-bulk-filter="<?= e((string) ($bulkActionForFilter ?? '')) ?>"
    data-status-filter="<?= e((string) ($statusFilter ?? '')) ?>">
    <div class="card-body vf-toolbar-body">
        <form method="get" action="<?= e($searchAction) ?>" class="vf-toolbar-search">
            <?php if ($statusFilter !== null): ?>
            <input type="hidden" name="status" value="<?= e($statusFilter) ?>">
            <?php endif; ?>
            <?php if (!empty($filters['supplier_id'])): ?>
            <input type="hidden" name="supplier_id" value="<?= e((string) $filters['supplier_id']) ?>">
            <?php endif; ?>
            <input type="search" name="q" class="form-input vf-toolbar-search-input" value="<?= e((string) ($filters['q'] ?? '')) ?>" placeholder="Search order, customer, mobile, product, model" aria-label="Search orders">
            <button type="submit" class="btn btn-secondary btn-sm">Search</button>
        </form>

        <div class="vf-toolbar-actions">
            <?php if ($canManage): ?>
            <span class="vf-selected-count"><strong id="vfSelectedCount">0</strong> selected</span>
            <button type="button" class="btn btn-primary btn-sm js-vf-bulk-forward" id="vfBulkForwardBtn" hidden data-bulk-action="">Bulk action</button>
            <button type="button" class="btn btn-secondary btn-sm js-vf-bulk-hold" id="vfBulkHoldBtn" hidden>Hold</button>
            <button type="button" class="btn btn-secondary btn-sm js-vf-bulk-cancel" id="vfBulkCancelBtn" hidden>Cancel</button>
            <span class="vf-bulk-hint page-description" id="vfBulkHint" hidden>Mixed statuses — select rows in one stage only.</span>
            <?php endif; ?>

            <form method="get" action="<?= e(url('/order-workflow')) ?>" class="vf-per-page-form">
                <?php if ($statusFilter !== null): ?>
                <input type="hidden" name="status" value="<?= e($statusFilter) ?>">
                <?php endif; ?>
                <?php if (($filters['q'] ?? '') !== ''): ?>
                <input type="hidden" name="q" value="<?= e((string) $filters['q']) ?>">
                <?php endif; ?>
                <label class="vf-per-page-label">
                    <span>Per page</span>
                    <select name="per_page" class="form-input" onchange="this.form.submit()">
                        <option value="20" <?= $perPage === 20 ? 'selected' : '' ?>>20</option>
                        <option value="50" <?= $perPage === 50 ? 'selected' : '' ?>>50</option>
                    </select>
                </label>
            </form>

            <a href="<?= e($clearUrl) ?>" class="btn btn-ghost btn-sm">Clear</a>

            <?php if (!empty($canShowTestSync)): ?>
            <a href="<?= e(url('/sync-preview')) ?>" class="btn btn-ghost btn-sm">Test Sync</a>
            <?php endif; ?>
        </div>
    </div>
</div>
