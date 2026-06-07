<?php if (empty($columnGateReady)): ?>
<div class="card card-warn-border mb-15">
    <div class="card-header">
        <h2 class="card-title"><?= e($columnGateTitle ?? 'Optional Column Gate') ?></h2>
    </div>
    <div class="card-body">
        <p class="page-description"><?= e($columnGateMessage ?? 'An optional migration column is not applied yet.') ?></p>
        <?php if (!empty($columnGateDetails)): ?>
        <ul class="feature-list">
            <?php foreach ($columnGateDetails as $detail): ?>
            <li><?= e($detail) ?></li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
        <p class="page-description mt-05">Product list and edit continue to work. Apply <code>database/migrations/<?= e($columnGateMigrationFile ?? '0012_supplier_product_note.sql') ?></code> manually when owner-approved — not from this page.</p>
        <p class="page-description mt-05"><a href="<?= e(url('/migration-files')) ?>">Open Migration Files</a> for apply order and safety rules.</p>
    </div>
</div>
<?php endif; ?>
