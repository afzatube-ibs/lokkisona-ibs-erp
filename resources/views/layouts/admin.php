<?php
/* Nav section labels — maps first path of each group to a label.
 * Rendered inline before the first item in each group. */
$navSectionMap = [
    '/dashboard'             => 'Core',
    '/roles-permissions'     => 'Admin',
    '/database-safety'       => 'Dev / Database',
    '/suppliers'             => 'Operations',
    '/supplier-payables'     => 'Finance',
    '/status-mapping'        => 'Tools & Sync',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? $appName) ?> — <?= e($appName) ?></title>
    <!-- Prevent dark-mode flash: apply saved theme before CSS renders -->
    <script>(function(){try{var t=localStorage.getItem('ibs-theme');if(t==='dark')document.documentElement.setAttribute('data-theme','dark');}catch(e){}}());</script>
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
                <?php if (isset($navSectionMap[$item['path']])): ?>
                    <span class="nav-section-label"><?= e($navSectionMap[$item['path']]) ?></span>
                <?php endif; ?>
                <a href="<?= e(url($item['path'])) ?>"
                   class="nav-item <?= strpos($currentPath ?? '', $item['path']) !== false ? 'active' : '' ?>">
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
                <?php if (!empty($canUseCalculator)): ?>
                <button type="button" class="topbar-tool-btn" data-open-modal="supplierCalculatorModal" title="Calculator" aria-label="Open calculator">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="2" width="16" height="20" rx="2"/><line x1="8" y1="6" x2="16" y2="6"/><line x1="8" y1="10" x2="8" y2="10.01"/><line x1="12" y1="10" x2="12" y2="10.01"/><line x1="16" y1="10" x2="16" y2="10.01"/><line x1="8" y1="14" x2="8" y2="14.01"/><line x1="12" y1="14" x2="12" y2="14.01"/><line x1="16" y1="14" x2="16" y2="14.01"/><line x1="8" y1="18" x2="8" y2="18.01"/><line x1="12" y1="18" x2="12" y2="18.01"/><line x1="16" y1="18" x2="16" y2="18.01"/></svg>
                </button>
                <?php endif; ?>
                <?php if (!empty($canUseQuickInvoice)): ?>
                <button type="button" class="topbar-tool-btn" data-open-modal="supplierQuickInvoiceModal" title="Quick Invoice" aria-label="Open quick invoice generator">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 13h8M8 17h6"/></svg>
                </button>
                <?php endif; ?>
                <span class="user-badge"><?= e($currentUser ?? 'User') ?> · <?= e($currentRole ?? 'owner') ?></span>
                <button type="button" class="theme-toggle" id="themeToggle" aria-label="Toggle dark mode" title="Toggle dark mode">
                    <!-- Moon icon (shown in light mode) -->
                    <svg class="icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                    </svg>
                    <!-- Sun icon (shown in dark mode) -->
                    <svg class="icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="5"/>
                        <line x1="12" y1="1" x2="12" y2="3"/>
                        <line x1="12" y1="21" x2="12" y2="23"/>
                        <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/>
                        <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
                        <line x1="1" y1="12" x2="3" y2="12"/>
                        <line x1="21" y1="12" x2="23" y2="12"/>
                        <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/>
                        <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
                    </svg>
                </button>
                <a href="<?= e(url('/logout')) ?>" class="btn btn-ghost btn-sm">Sign out</a>
            </div>
        </header>

        <div class="dev-mode-banner">
            <strong>DEV / TEST MODE</strong>
            <span class="dev-sep">·</span>No live sync
            <span class="dev-sep">·</span>No payable finalization
            <span class="dev-sep">·</span>No stock deduction
            <span class="dev-sep">·</span>Opening balance draft only
        </div>

        <main class="main-content">
            <?= $content ?>
        </main>

        <footer class="app-footer">
            <span>&copy; <?= date('Y') ?> IBS-LK Business Manager</span>
            <span>PHP <?= e(PHP_VERSION) ?> · v<?= e($appVersion) ?> <?= e($appReleaseLabel) ?></span>
        </footer>

    </div>
</div>

<?php if (!empty($canUseCalculator)): ?>
    <?php view('partials.supplier-calculator-modal'); ?>
<?php endif; ?>
<?php if (!empty($canUseQuickInvoice)): ?>
    <?php view('partials.supplier-quick-invoice-modal', [
        'csrfField' => $csrfField ?? '',
        'quickInvoiceGateReady' => $quickInvoiceGateReady ?? false,
        'writeGateMessage' => $writeGateMessage ?? '',
    ]); ?>
<?php endif; ?>

<script src="<?= e(asset('js/app.js')) ?>"></script>
<script src="<?= e(asset('js/supplier-tools.js')) ?>"></script>
</body>
</html>
