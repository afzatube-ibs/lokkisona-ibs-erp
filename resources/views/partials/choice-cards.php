<?php
/**
 * @var string $name Field name for radio group
 * @var array<int, array{code: string, label: string, description?: string}> $options
 * @var bool $required
 * @var string|null $legend
 */
$required = !empty($required);
?>
<?php if (!empty($legend)): ?>
<p class="choice-card-legend"><?= e($legend) ?><?= $required ? ' *' : '' ?></p>
<?php endif; ?>
<div class="choice-card-grid" role="radiogroup" aria-label="<?= e($legend ?? $name) ?>">
    <?php foreach ($options as $option): ?>
    <label class="choice-card">
        <input type="radio" name="<?= e($name) ?>" value="<?= e($option['code']) ?>" <?= $required ? 'required' : '' ?>>
        <span class="choice-card-body">
            <strong class="choice-card-label"><?= e($option['label']) ?></strong>
            <?php if (!empty($option['description'])): ?>
            <span class="choice-card-desc"><?= e($option['description']) ?></span>
            <?php endif; ?>
        </span>
    </label>
    <?php endforeach; ?>
</div>
