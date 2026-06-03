<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? $appName) ?> — <?= e($appName) ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700&display=swap" rel="stylesheet">
</head>
<body class="admin-body">
    <div class="admin-shell">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                <div class="brand-mark">IBS</div>
                <div class="brand-text">
                    <span class="brand-name">IBS-LK</span>
                    <span class="brand-sub">Business Manager</span>
                </div>
            </div>
            <nav class="sidebar-nav">
                <?php foreach ($navItems ?? [] as $item): ?>
                <a href="<?= e(url($item['path'])) ?>" class="nav-item <?= strpos($currentPath ?? '', $item['path']) !== false ? 'active' : '' ?>">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><?= $item['icon'] ?></svg>
                    <?= e($item['label']) ?>
                </a>
                <?php endforeach; ?>
            </nav>
            <div class="sidebar-footer">
                <span class="version-badge">v<?= e($appVersion) ?></span>
            </div>
        </aside>

        <div class="main-wrapper">
            <header class="topbar">
                <button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle menu">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12h18M3 6h18M3 18h18"/></svg>
                </button>
                <?php if (!empty($breadcrumbs)): ?>
                <nav class="breadcrumbs" aria-label="Breadcrumb">
                    <?php foreach ($breadcrumbs as $crumb): ?>
                        <?php if (!empty($crumb['active'])): ?>
                            <span class="breadcrumb-current"><?= e($crumb['label']) ?></span>
                        <?php else: ?>
                            <span class="breadcrumb-muted"><?= e($crumb['label']) ?></span>
                            <span class="breadcrumb-sep">/</span>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </nav>
                <?php endif; ?>
                <div class="topbar-actions">
                    <span class="user-badge"><?= e($currentUser ?? 'User') ?> · <?= e($currentRole ?? 'owner') ?></span>
                    <a href="<?= e(url('/logout')) ?>" class="btn btn-ghost btn-sm">Sign out</a>
                </div>
            </header>

            <main class="main-content">
                <?= $content ?>
            </main>

            <footer class="app-footer">
                <span>&copy; <?= date('Y') ?> IBS-LK Business Manager</span>
                <span>Runtime PHP <?= e(PHP_VERSION) ?> · v<?= e($appVersion) ?> <?= e($appReleaseLabel) ?></span>
            </footer>
        </div>
    </div>
    <script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
