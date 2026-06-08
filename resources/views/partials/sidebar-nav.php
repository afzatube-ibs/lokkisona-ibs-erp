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

$renderTier = static function (string $summary, array $items, string $tierClass = '') use ($renderNavItem, $navItemActive): void {
    if ($items === []) {
        return;
    }

    $isOpen = false;
    foreach ($items as $item) {
        if ($navItemActive($item)) {
            $isOpen = true;
            break;
        }
    }

    $class = 'sidebar-tier' . ($tierClass !== '' ? ' ' . $tierClass : '');
    ?>
    <details class="<?= e($class) ?>"<?= $isOpen ? ' open' : '' ?>>
        <summary class="sidebar-tier-summary"><?= e($summary) ?></summary>
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

<?php foreach ($nav['main'] ?? [] as $groupName => $groupItems): ?>
    <span class="nav-section-label"><?= e($groupName) ?></span>
    <?php foreach ($groupItems as $item): ?>
        <?php $renderNavItem($item); ?>
    <?php endforeach; ?>
<?php endforeach; ?>

<?php $renderTier('Reports', $nav['reports'] ?? []); ?>

<span class="nav-section-label nav-section-label-settings">Settings</span>
<?php $renderTier('Settings', $nav['settings'] ?? []); ?>

<?php
$futureItems = $filterFutureItems($nav['future'] ?? []);
if ($futureItems !== []):
?>
<span class="nav-section-label nav-section-label-future">Future Modules</span>
<?php $renderTier('Future Modules', $futureItems, 'sidebar-tier-future'); ?>
<?php endif; ?>
