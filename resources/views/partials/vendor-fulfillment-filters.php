<?php
$filters = $fulfillmentFilters ?? [];
$statusFilter = $statusFilter ?? null;
$supplierOptions = $supplierOptions ?? [];
?>
<details class="vf-advanced-filters card mb-15">
    <summary class="card-header vf-advanced-summary"><h2 class="card-title">Advanced filters</h2></summary>
    <form method="get" action="<?= e(url('/order-workflow')) ?>" class="vf-filter-form">
        <div class="card-body vf-filter-body">
            <?php if ($statusFilter !== null): ?>
            <input type="hidden" name="status" value="<?= e($statusFilter) ?>">
            <?php endif; ?>
            <?php if (($filters['q'] ?? '') !== ''): ?>
            <input type="hidden" name="q" value="<?= e((string) $filters['q']) ?>">
            <?php endif; ?>
            <?php if ((int) ($filters['per_page'] ?? 20) !== 20): ?>
            <input type="hidden" name="per_page" value="<?= e((string) ($filters['per_page'] ?? 20)) ?>">
            <?php endif; ?>
            <?php if (empty($isSupplierView) && $supplierOptions !== []): ?>
            <label class="vf-filter-field">
                <span>Supplier</span>
                <select name="supplier_id" class="form-input">
                    <option value="">All suppliers</option>
                    <?php foreach ($supplierOptions as $option): ?>
                    <option value="<?= e((string) ($option['id'] ?? '')) ?>" <?= (int) ($filters['supplier_id'] ?? 0) === (int) ($option['id'] ?? 0) ? 'selected' : '' ?>><?= e((string) ($option['label'] ?? '')) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <?php endif; ?>
            <label class="vf-filter-field">
                <span>Courier status</span>
                <input type="text" name="courier_status" class="form-input" value="<?= e((string) ($filters['courier_status'] ?? '')) ?>" placeholder="Any">
            </label>
            <label class="vf-filter-field">
                <span>Date from</span>
                <input type="date" name="date_from" class="form-input" value="<?= e((string) ($filters['date_from'] ?? '')) ?>">
            </label>
            <label class="vf-filter-field">
                <span>Date to</span>
                <input type="date" name="date_to" class="form-input" value="<?= e((string) ($filters['date_to'] ?? '')) ?>">
            </label>
            <div class="vf-filter-actions">
                <button type="submit" class="btn btn-primary btn-sm">Apply filters</button>
            </div>
        </div>
    </form>
</details>
