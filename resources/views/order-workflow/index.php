<div class="page-header">
    <h1 class="page-title">Order Workflow</h1>
    <p class="page-description">Order Workflow Planning Foundation for the independent IBS fulfillment workflow. No order sync, no OpenCart connection, no order tables, and no database records are written in this release.</p>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Current Workflow Context</h2>
        </div>
        <div class="card-body">
            <dl class="info-list">
                <div class="info-row">
                    <dt>Primary Supplier</dt>
                    <dd><?= e($currentContext['supplier']) ?></dd>
                </div>
                <div class="info-row">
                    <dt>Primary Source</dt>
                    <dd><?= e($currentContext['source']) ?></dd>
                </div>
            </dl>
            <p class="page-description"><?= e($currentContext['summary']) ?></p>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Workflow Purpose</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($purpose as $item): ?>
                    <li><?= e($item) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Main Workflow Path</h2>
    </div>
    <div class="card-body">
        <p class="page-description">
            <?php foreach ($mainFlowPath as $i => $stage): ?>
                <strong><?= e($stage) ?></strong><?= $i < count($mainFlowPath) - 1 ? ' &rarr; ' : '' ?>
            <?php endforeach; ?>
        </p>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Main Workflow Stages</h2>
    </div>
    <div class="card-body card-body-flush">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Label</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($mainStages as $stage): ?>
                <tr>
                    <td class="cell-name"><code><?= e($stage['code']) ?></code></td>
                    <td><?= e($stage['label']) ?></td>
                    <td class="cell-detail"><?= e($stage['description']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Exception Stages</h2>
    </div>
    <div class="card-body card-body-flush">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Label</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($exceptionStages as $stage): ?>
                <tr>
                    <td class="cell-name"><code><?= e($stage['code']) ?></code></td>
                    <td><?= e($stage['label']) ?></td>
                    <td class="cell-detail"><?= e($stage['description']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Allowed Transition Matrix</h2>
    </div>
    <div class="card-body card-body-flush">
        <table class="data-table">
            <thead>
                <tr>
                    <th>From</th>
                    <th>Allowed To</th>
                    <th>Note</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transitionMatrix as $row): ?>
                <tr>
                    <td class="cell-name"><?= e($row['from']) ?></td>
                    <td><?= e($row['to']) ?></td>
                    <td class="cell-detail"><?= e($row['note']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><?= e($dispatchGate['title']) ?></h2>
        </div>
        <div class="card-body">
            <p><?= e($dispatchGate['summary']) ?></p>
            <ul class="feature-list">
                <?php foreach ($dispatchGate['points'] as $point): ?>
                    <li><?= e($point) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><?= e($costSnapshotRule['title']) ?></h2>
        </div>
        <div class="card-body">
            <p><?= e($costSnapshotRule['summary']) ?></p>
            <ul class="feature-list">
                <?php foreach ($costSnapshotRule['points'] as $point): ?>
                    <li><?= e($point) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><?= e($mappingRule['title']) ?></h2>
        </div>
        <div class="card-body">
            <p><?= e($mappingRule['summary']) ?></p>
            <ul class="feature-list">
                <?php foreach ($mappingRule['points'] as $point): ?>
                    <li><?= e($point) ?></li>
                <?php endforeach; ?>
            </ul>
            <p class="page-description">Source status mapping is used only at import/sync time. IBS workflow remains independent after sync. See <a href="<?= e(url('/status-mapping')) ?>">Status Mapping planning foundation</a>.</p>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><?= e($independentRule['title']) ?></h2>
        </div>
        <div class="card-body">
            <p><?= e($independentRule['summary']) ?></p>
            <ul class="feature-list">
                <?php foreach ($independentRule['points'] as $point): ?>
                    <li><?= e($point) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Action Confirmation &amp; Activity Log Rules</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($actionLogRule as $rule): ?>
                    <li><?= e($rule) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Performance Rules</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($performanceRules as $rule): ?>
                    <li><?= e($rule) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Future Sync Safety Rules</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($futureSyncSafety as $rule): ?>
                    <li><?= e($rule) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Current Access Mode</h2>
        </div>
        <div class="card-body">
            <dl class="info-list">
                <div class="info-row">
                    <dt>Mode</dt>
                    <dd><?= e($accessMode['mode']) ?></dd>
                </div>
                <div class="info-row">
                    <dt>Current Role</dt>
                    <dd><?= e($accessMode['role']) ?></dd>
                </div>
            </dl>
            <p class="page-description">Owner, admin, and staff can view the Order Workflow planning foundation now. No order, dispatch, or workflow tables are created automatically and no database records are written in this release.</p>
        </div>
    </div>
</div>
