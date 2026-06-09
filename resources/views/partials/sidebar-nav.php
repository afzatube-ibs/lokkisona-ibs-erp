<?php
$nav = $navNavigation ?? [];
$currentPath = $currentPath ?? '';
$currentQuery = $currentQuery ?? [];
$showDeveloperNav = ($appEnv ?? 'local') !== 'production';

$navItemActive = static function (array $item) use ($currentPath, $currentQuery): bool {
    $path = (string) ($item['path'] ?? '');
    $matchQuery = $item['match_query'] ?? null;

    if ($path === '') {
        return false;
    }

    if (strpos($currentPath, $path) === false) {
        return false;
    }

    if (!is_array($matchQuery) || $matchQuery === []) {
        return true;
    }

    foreach ($matchQuery as $key => $value) {
        if ((string) ($currentQuery[$key] ?? '') !== (string) $value) {
            return false;
        }
    }

    return true;
};

$buildNavHref = static function (array $item): string {
    $path = (string) ($item['path'] ?? '');
    $matchQuery = $item['match_query'] ?? null;

    if ($path === '') {
        return '#';
    }

    $href = url($path);
    if (is_array($matchQuery) && $matchQuery !== []) {
        $href .= '?' . http_build_query($matchQuery);
    }

    return $href;
};

$tierHasActive = static function (array $items) use ($navItemActive): bool {
    foreach ($items as $item) {
        if ($navItemActive($item)) {
            return true;
        }
    }

    return false;
};

$renderNavItem = static function (array $item) use ($navItemActive, $buildNavHref): void {
    $label = (string) ($item['short_label'] ?? $item['label'] ?? '');
    $isDisabled = !empty($item['nav_disabled']);
    $navAction = (string) ($item['nav_action'] ?? '');
    $isActive = $navItemActive($item);
    ?>
    <?php if ($isDisabled): ?>
    <span class="nav-item nav-item-compact nav-item-disabled" title="Coming soon">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><?= $item['icon'] ?? '' ?></svg>
        <?= e($label) ?>
        <span class="nav-soon-badge">Soon</span>
    </span>
    <?php elseif ($navAction !== ''): ?>
    <button type="button"
            class="nav-item nav-item-compact nav-item-button<?= $isActive ? ' active' : '' ?>"
            data-open-modal="<?= e($navAction) ?>">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><?= $item['icon'] ?? '' ?></svg>
        <?= e($label) ?>
    </button>
    <?php else: ?>
    <a href="<?= e($buildNavHref($item)) ?>"
       class="nav-item nav-item-compact <?= $isActive ? 'active' : '' ?>">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><?= $item['icon'] ?? '' ?></svg>
        <?= e($label) ?>
    </a>
    <?php endif; ?>
    <?php
};

$renderTier = static function (string $summary, array $items, array $options = []) use ($renderNavItem, $tierHasActive): void {
    if ($items === []) {
        return;
    }

    $tierClass = (string) ($options['class'] ?? '');
    $sectionIcon = (string) ($options['icon'] ?? '');
    $storageKey = (string) ($options['storage_key'] ?? '');
    $defaultOpen = !empty($options['default_open']);
    $isOpen = $defaultOpen || $tierHasActive($items);

    $class = 'sidebar-tier';
    if ($tierClass !== '') {
        $class .= ' ' . $tierClass;
    }
    ?>
    <details class="<?= e($class) ?>"<?= $isOpen ? ' open' : '' ?><?= $storageKey !== '' ? ' data-nav-tier="' . e($storageKey) . '"' : '' ?><?= $defaultOpen ? ' data-nav-default-open="1"' : '' ?>>
        <summary class="sidebar-tier-summary">
            <?php if ($sectionIcon !== ''): ?>
            <svg class="sidebar-tier-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><?= $sectionIcon ?></svg>
            <?php endif; ?>
            <span class="sidebar-tier-label"><?= e($summary) ?></span>
        </summary>
        <div class="sidebar-tier-body">
            <?php foreach ($items as $item): ?>
                <?php $renderNavItem($item); ?>
            <?php endforeach; ?>
        </div>
    </details>
    <?php
};

$filterFutureItems = static function (array $items) use ($showDeveloperNav): array {
    return array_values(array_filter($items, static function (array $item) use ($showDeveloperNav): bool {
        if (!empty($item['dev_only']) && !$showDeveloperNav) {
            return false;
        }

        return true;
    }));
};
?>

<?php foreach ($nav['dashboard'] ?? [] as $item): ?>
    <?php $renderNavItem($item); ?>
<?php endforeach; ?>

<?php $renderTier('Operations', $nav['operations'] ?? [], [
    'storage_key' => 'operations',
    'default_open' => true,
    'icon' => '<path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>',
]); ?>

<?php $renderTier('Supplier Management', $nav['supplier_management'] ?? [], [
    'storage_key' => 'supplier-management',
    'default_open' => true,
    'icon' => '<path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><path d="M20 8v6M23 11h-6"/>',
]); ?>

<?php $renderTier('Reports', $nav['reports'] ?? [], [
    'storage_key' => 'reports',
    'default_open' => true,
    'icon' => '<path d="M3 3v18h18"/><path d="M7 16l4-6 4 3 5-8"/>',
]); ?>

<?php $renderTier('System', $nav['system'] ?? [], [
    'storage_key' => 'system',
    'default_open' => true,
    'icon' => '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 9h6M9 13h6M9 17h4"/>',
]); ?>

<?php $renderTier('Admin Tools', $nav['admin_tools'] ?? [], [
    'storage_key' => 'admin-tools',
    'default_open' => true,
    'icon' => '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>',
]); ?>

<?php
$futureItems = $filterFutureItems($nav['future_plans'] ?? []);
if ($futureItems !== []):
?>
<?php $renderTier('Future Plans', $futureItems, [
    'class' => 'sidebar-tier-future',
    'storage_key' => 'future-plans',
    'default_open' => false,
    'icon' => '<path d="M3 7h18v13H3z"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M10 11h4"/>',
]); ?>
<?php endif; ?>
