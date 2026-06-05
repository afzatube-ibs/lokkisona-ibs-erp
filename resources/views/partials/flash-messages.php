<?php if (!empty($flashSuccess)): ?>
    <div class="alert alert-success mb-1">
        <?= e($flashSuccess) ?>
    </div>
<?php endif; ?>
<?php if (!empty($flashError)): ?>
    <div class="alert alert-error mb-1">
        <?= e($flashError) ?>
    </div>
<?php endif; ?>
