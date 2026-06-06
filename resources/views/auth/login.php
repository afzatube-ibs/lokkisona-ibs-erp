<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — IBS-LK Business Manager</title>
    <script>(function(){try{var t=localStorage.getItem('ibs-theme');if(t==='dark'||(!t&&window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches))document.documentElement.setAttribute('data-theme','dark');}catch(e){}}());</script>
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700&display=swap" rel="stylesheet">
</head>
<body class="login-body">
    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-brand">
                <div class="brand-mark brand-mark-lg">IBS</div>
                <h1 class="login-title">IBS-LK Business Manager</h1>
                <p class="login-subtitle">Supplier fulfillment ERP — Lokkisona / Sonamoni</p>
                <span class="version-pill">v<?= e(config('app.version')) ?> <?= e(config('app.release_label')) ?></span>
            </div>

            <?php if (!empty($error)): ?>
            <div class="alert alert-error" role="alert">
                <?= e($error) ?>
            </div>
            <?php endif; ?>

            <form method="post" action="<?= e(url('/login')) ?>" class="login-form" autocomplete="on">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required autofocus
                           placeholder="Enter username" value="<?= e($_POST['username'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required
                           placeholder="Enter password">
                </div>
                <button type="submit" class="btn btn-primary btn-block">Sign In</button>
            </form>

            <?php if (!empty($showDevCredentials)): ?>
            <p class="login-hint">Local dev only: <code>admin</code> / <code>admin</code> · <code>supplier</code> / <code>supplier</code></p>
            <?php endif; ?>
        </div>

        <footer class="login-footer">
            <span>Git-based deployment · No OpenCart · Runtime PHP <?= e(PHP_VERSION) ?></span>
        </footer>
    </div>
</body>
</html>
