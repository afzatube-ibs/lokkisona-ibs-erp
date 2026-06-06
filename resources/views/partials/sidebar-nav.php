<?php
$nav = $navNavigation ?? [];
$currentPath = $currentPath ?? '';
$showDeveloperNav = ($appEnv ?? 'local') !== 'production';

$renderNavItem = static function (array $item) use ($currentPath): void {
    $path = (string) ($item['path'] ?? '');
    $label = (string) ($item['short_label'] ?? $item['label'] ?? '');
    $isActive = $path !== '' && strpos($currentPath, $path) !== false;
    ?>
    <a href="<?= e(url($path)) ?>"
       class="nav-item nav-item-compact <?= $isActive ? 'active' : '' ?>">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><?= $item['icon'] ?? '' ?></svg>
        <?= e($label) ?>
    </a>
    <?php
};

$renderTier = static function (string $summary, array $items) use ($renderNavItem, $currentPath): void {
    if ($items === []) {
        return;
    }

    $isOpen = false;
    foreach ($items as $item) {
        $path = (string) ($item['path'] ?? '');
        if ($path !== '' && strpos($currentPath, $path) !== false) {
            $isOpen = true;
            break;
        }
    }
    ?>
    <details class="sidebar-tier"<?= $isOpen ? ' open' : '' ?>>
        <summary class="sidebar-tier-summary"><?= e($summary) ?></summary>
        <div class="sidebar-tier-body">
            <?php foreach ($items as $item): ?>
                <?php $renderNavItem($item); ?>
            <?php endforeach; ?>
        </div>
    </details>
    <?php
};
?>

<?php foreach ($nav['primary'] ?? [] as $groupName => $groupItems): ?>
    <?php if (count($nav['primary'] ?? []) > 1): ?>
        <span class="nav-section-label"><?= e($groupName) ?></span>
    <?php endif; ?>
    <?php foreach ($groupItems as $item): ?>
        <?php $renderNavItem($item); ?>
    <?php endforeach; ?>
<?php endforeach; ?>

<?php $renderTier('Setup & Admin', $nav['setup'] ?? []); ?>
<?php $renderTier('System', $nav['system'] ?? []); ?>
<?php if ($showDeveloperNav): ?>
    <?php $renderTier('Developer', $nav['developer'] ?? []); ?>
<?php endif; ?>
