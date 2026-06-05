<?php if (!empty($flashSuccess)): ?>
    <div class="alert alert-success" style="margin-bottom: 1rem; padding: 0.75rem 1rem; background: #e8f5e9; border: 1px solid #a5d6a7; border-radius: 4px;">
        <?= e($flashSuccess) ?>
    </div>
<?php endif; ?>
<?php if (!empty($flashError)): ?>
    <div class="alert alert-error" style="margin-bottom: 1rem; padding: 0.75rem 1rem; background: #ffebee; border: 1px solid #ef9a9a; border-radius: 4px;">
        <?= e($flashError) ?>
    </div>
<?php endif; ?>
