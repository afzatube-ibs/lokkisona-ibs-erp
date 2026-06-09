<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — IBS-LK Business Manager</title>
    <script>(function(){try{var t=localStorage.getItem('ibs-theme');if(t==='dark'||(!t&&window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches))document.documentElement.setAttribute('data-theme','dark');}catch(e){}}());</script>
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="login-body">
    <div class="login-bg-glow login-bg-glow-left" aria-hidden="true"></div>
    <div class="login-bg-glow login-bg-glow-right" aria-hidden="true"></div>
    <div class="login-bg-mesh login-bg-mesh-left" aria-hidden="true"></div>
    <div class="login-bg-mesh login-bg-mesh-right" aria-hidden="true"></div>

    <div class="login-shell">
        <div class="login-card">
            <div class="login-brand-block">
                <div class="login-brand-logo">
                    <span class="brand-mark brand-mark-lg">IBS</span>
                </div>
                <h1 class="login-brand-title">
                    <span class="login-brand-title-light">IBS-LK</span>
                    <span class="login-brand-title-accent">Business Manager</span>
                </h1>
                <p class="login-brand-subtitle">Supplier Operations Portal</p>
                <div class="login-brand-divider" aria-hidden="true"></div>
                <p class="login-brand-lead">Manage orders, dispatch, products, returns and supplier accounts from one place.</p>
            </div>

            <?php if (!empty($error)): ?>
            <div class="login-alert login-alert-error" role="alert">
                <svg class="login-alert-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <span><?= e($error) ?></span>
            </div>
            <?php endif; ?>

            <form method="post" action="<?= e(url('/login')) ?>" class="login-form" autocomplete="on">
                <div class="form-group login-field">
                    <label for="username">Username</label>
                    <div class="login-input-group">
                        <span class="login-input-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        </span>
                        <input type="text" id="username" name="username" required autofocus
                               class="login-input"
                               placeholder="Enter your username" value="<?= e($_POST['username'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-group login-field">
                    <label for="password">Password</label>
                    <div class="login-input-group">
                        <span class="login-input-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        </span>
                        <input type="password" id="password" name="password" required
                               class="login-input"
                               placeholder="Enter your password">
                    </div>
                </div>
                <div class="login-options">
                    <label class="login-remember">
                        <input type="checkbox" tabindex="-1" aria-hidden="true">
                        <span>Remember me</span>
                    </label>
                </div>
                <button type="submit" class="btn btn-primary btn-block login-submit">Sign In</button>
            </form>

            <p class="login-trust">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                Secure &bull; Private &bull; Reliable
            </p>
        </div>

        <footer class="login-page-footer">
            <p class="login-page-footer-line">&copy; 2026 IBS-LK Business Manager</p>
            <p class="login-page-footer-line">Lokkisona Supplier Operations Platform</p>
            <p class="login-page-footer-line login-page-footer-version">Version <?= e(config('app.version')) ?></p>
        </footer>
    </div>
</body>
</html>
