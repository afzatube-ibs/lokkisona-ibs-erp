<div class="page-header">
    <h1 class="page-title">Version</h1>
    <p class="page-description">Release information and platform details.</p>
</div>

<div class="version-hero">
    <div class="version-hero-mark">IBS</div>
    <div class="version-hero-info">
        <h2><?= e($info['product']) ?></h2>
        <p class="version-number">v<?= e($info['version']) ?> <?= e($info['codename']) ?></p>
        <p class="version-codename"><?= e($info['codename']) ?></p>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Release Details</h2>
        </div>
        <div class="card-body">
            <dl class="info-list">
                <div class="info-row">
                    <dt>Product</dt>
                    <dd><?= e($info['product']) ?></dd>
                </div>
                <div class="info-row">
                    <dt>Version</dt>
                    <dd><?= e($info['version']) ?></dd>
                </div>
                <div class="info-row">
                    <dt>Codename</dt>
                    <dd><?= e($info['codename']) ?></dd>
                </div>
                <div class="info-row">
                    <dt>Release Date</dt>
                    <dd><?= e($info['release_date']) ?></dd>
                </div>
                <div class="info-row">
                    <dt>PHP Version</dt>
                    <dd><?= e($info['php_version']) ?></dd>
                </div>
                <div class="info-row">
                    <dt>PHP Requirement</dt>
                    <dd><?= e($info['php_requirement']) ?></dd>
                </div>
                <div class="info-row">
                    <dt>Environment</dt>
                    <dd><?= e($info['environment']) ?></dd>
                </div>
            </dl>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Dependencies</h2>
        </div>
        <div class="card-body">
            <dl class="info-list">
                <?php foreach ($info['dependencies'] as $name => $value): ?>
                <div class="info-row">
                    <dt><?= e($name) ?></dt>
                    <dd><span class="badge badge-ok"><?= e($value) ?></span></dd>
                </div>
                <?php endforeach; ?>
            </dl>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">v0.1.2 Features</h2>
    </div>
    <div class="card-body">
        <ul class="feature-list feature-list-columns">
            <?php foreach ($info['features'] as $feature): ?>
            <li><?= e($feature) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
