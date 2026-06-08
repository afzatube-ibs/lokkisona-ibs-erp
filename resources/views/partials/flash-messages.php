<?php if (!empty($flashSuccess)): ?>
    <div class="alert alert-success mb-1">
        <?= e($flashSuccess) ?>
        <?php if (!empty($flashSuccessLink['url']) && !empty($flashSuccessLink['label'])): ?>
        <a href="<?= e((string) $flashSuccessLink['url']) ?>" class="alert-link"><?= e((string) $flashSuccessLink['label']) ?></a>
        <?php endif; ?>
    </div>
<?php endif; ?>
<?php if (!empty($flashError)): ?>
    <div class="alert alert-error mb-1">
        <?= e($flashError) ?>
    </div>
<?php endif; ?>
