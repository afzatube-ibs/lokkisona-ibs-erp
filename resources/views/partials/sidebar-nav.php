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

    $class = 'sidebar-tier sidebar-tier-workflow';
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

<?php $renderTier('Orders', $nav['orders'] ?? [], [
    'storage_key' => 'orders',
    'default_open' => true,
    'icon' => '<path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>',
]); ?>

<?php $renderTier('Fulfillment', $nav['fulfillment'] ?? [], [
    'storage_key' => 'fulfillment',
    'default_open' => true,
    'icon' => '<rect x="1" y="3" width="15" height="13" rx="1"/><path d="M16 8h4l3 3v5a1 1 0 0 1-1 1h-6z"/>',
]); ?>

<?php $renderTier('Accounts', $nav['accounts'] ?? [], [
    'storage_key' => 'accounts',
    'default_open' => true,
    'icon' => '<path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>',
]); ?>

<?php $renderTier('Catalog', $nav['catalog'] ?? [], [
    'storage_key' => 'catalog',
    'default_open' => true,
    'icon' => '<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>',
]); ?>

<?php $renderTier('Settings', $nav['settings'] ?? [], [
    'storage_key' => 'settings',
    'default_open' => false,
    'icon' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>',
]); ?>

<?php
$futureItems = $filterFutureItems($nav['future_plans'] ?? []);
if ($futureItems !== []):
?>
<?php $renderTier('Future Plan', $futureItems, [
    'class' => 'sidebar-tier-future',
    'storage_key' => 'future-plans',
    'default_open' => false,
    'icon' => '<path d="M3 7h18v13H3z"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M10 11h4"/>',
]); ?>
<?php endif; ?>
