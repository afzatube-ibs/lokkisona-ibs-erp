<?php
$filters = $fulfillmentFilters ?? [];
$statusFilter = $statusFilter ?? null;
$supplierOptions = $supplierOptions ?? [];
?>
<form method="get" action="<?= e(url('/order-workflow')) ?>" class="vf-filter-form card mb-15">
    <div class="card-body vf-filter-body">
        <?php if ($statusFilter !== null): ?>
        <input type="hidden" name="status" value="<?= e($statusFilter) ?>">
        <?php endif; ?>
        <label class="vf-filter-field">
            <span>Search</span>
            <input type="search" name="q" class="form-input" value="<?= e((string) ($filters['q'] ?? '')) ?>" placeholder="Order, customer, model">
        </label>
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
        <label class="vf-filter-field">
            <span>Rows</span>
            <select name="per_page" class="form-input">
                <?php foreach ([20, 30, 50] as $size): ?>
                <option value="<?= e((string) $size) ?>" <?= (int) ($filters['per_page'] ?? 20) === $size ? 'selected' : '' ?>><?= e((string) $size) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <div class="vf-filter-actions">
            <button type="submit" class="btn btn-primary btn-sm">Apply</button>
            <a href="<?= e(url('/order-workflow' . ($statusFilter !== null ? '?status=' . rawurlencode($statusFilter) : ''))) ?>" class="btn btn-secondary btn-sm">Reset</a>
        </div>
    </div>
</form>
