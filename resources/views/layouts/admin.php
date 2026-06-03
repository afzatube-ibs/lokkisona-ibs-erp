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
                    <span class="brand-name">Lokkisona</span>
                    <span class="brand-sub">IBS ERP</span>
                </div>
            </div>
            <nav class="sidebar-nav">
                <a href="<?= e(url('/dashboard')) ?>" class="nav-item <?= strpos($currentPath ?? '', '/dashboard') !== false ? 'active' : '' ?>">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                    Dashboard
                </a>
                <a href="<?= e(url('/health')) ?>" class="nav-item <?= strpos($currentPath ?? '', '/health') !== false ? 'active' : '' ?>">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                    Health Check
                </a>
                <a href="<?= e(url('/version')) ?>" class="nav-item <?= strpos($currentPath ?? '', '/version') !== false ? 'active' : '' ?>">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
                    Version
                </a>
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
                    <span class="user-badge"><?= e($currentUser ?? 'User') ?></span>
                    <a href="<?= e(url('/logout')) ?>" class="btn btn-ghost btn-sm">Sign out</a>
                </div>
            </header>

            <main class="main-content">
                <?= $content ?>
            </main>

            <footer class="app-footer">
                <span>&copy; <?= date('Y') ?> Lokkisona IBS ERP</span>
                <span>Standalone Foundation v<?= e($appVersion) ?></span>
            </footer>
        </div>
    </div>
    <script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
