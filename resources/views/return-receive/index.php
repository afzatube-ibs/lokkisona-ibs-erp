<div class="page-header">
    <h1 class="page-title">Return Receive</h1>
    <p class="page-description">Return Receive &amp; Review Batch Planning Foundation — owner/admin controlled. No return tables, no payable adjustment records, no order/dispatch/payable links, no OpenCart connection, and no return records are written in this release.</p>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Current Return Context</h2>
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
            <h2 class="card-title">Return Receive Purpose</h2>
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

<div class="card-grid">
    <?php foreach ([$supplierReturnFlow, $lokkisonaReturnFlow, $syncMappingRule, $manualEntryRule] as $section): ?>
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><?= e($section['title']) ?></h2>
        </div>
        <div class="card-body">
            <p><?= e($section['summary']) ?></p>
            <ul class="feature-list">
                <?php foreach ($section['points'] as $point): ?>
                    <li><?= e($point) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><?= e($confirmationRule['title']) ?></h2>
        </div>
        <div class="card-body">
            <p><?= e($confirmationRule['summary']) ?></p>
            <ul class="feature-list">
                <?php foreach ($confirmationRule['points'] as $point): ?>
                    <li><?= e($point) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Pre-Submit Total Warning</h2>
        </div>
        <div class="card-body">
            <p class="page-description">The confirmation warning must show these totals before submit:</p>
            <ul class="feature-list">
                <?php foreach ($totalsWarning as $item): ?>
                    <li><?= e($item) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Confirmation Examples</h2>
    </div>
    <div class="card-body card-body-flush">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Return Type</th>
                    <th>Payable Impact</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($confirmationExamples as $example): ?>
                <tr>
                    <td class="cell-name"><?= e($example['type']) ?></td>
                    <td class="cell-detail"><?= e($example['impact']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card-grid">
    <?php foreach ([$returnBatchPlan, $supplierReviewRule, $lokkisonaNoDeductionRule, $payableAdjustmentRule] as $section): ?>
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><?= e($section['title']) ?></h2>
        </div>
        <div class="card-body">
            <p><?= e($section['summary']) ?></p>
            <ul class="feature-list">
                <?php foreach ($section['points'] as $point): ?>
                    <li><?= e($point) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Remark / Note Rule</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($remarkRule as $rule): ?>
                    <li><?= e($rule) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Optional Image / Proof Upload Plan</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($imageProofPlan as $item): ?>
                    <li><?= e($item) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Approval &amp; Audit Rule</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($approvalRule as $rule): ?>
                    <li><?= e($rule) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Future Report / Export Plan</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($reportPlan as $item): ?>
                    <li><?= e($item) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<div class="card-grid">
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
            <p class="page-description">Owner and admin can view the Return Receive planning foundation now. Staff may view/manage later based on permission. Supplier role should not see all return receive / batch / payable-impact controls.</p>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Planned Return Receive Fields</h2>
        </div>
        <div class="card-body">
            <p class="page-description">Documented only. No return tables are created automatically and no records are written.</p>
            <ul class="feature-list">
                <?php foreach ($plannedReceiveFields as $field): ?>
                    <li><code><?= e($field) ?></code></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Planned Return Batch Fields</h2>
        </div>
        <div class="card-body">
            <p class="page-description">Documented only. No return tables are created automatically and no records are written.</p>
            <ul class="feature-list">
                <?php foreach ($plannedBatchFields as $field): ?>
                    <li><code><?= e($field) ?></code></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Planned Return Item Fields</h2>
        </div>
        <div class="card-body">
            <p class="page-description">Documented only. No return tables are created automatically and no records are written.</p>
            <ul class="feature-list">
                <?php foreach ($plannedItemFields as $field): ?>
                    <li><code><?= e($field) ?></code></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Planned Payable Adjustment Fields</h2>
        </div>
        <div class="card-body">
            <p class="page-description">Documented only. No payable adjustment tables are created automatically and no records are written.</p>
            <ul class="feature-list">
                <?php foreach ($plannedAdjustmentFields as $field): ?>
                    <li><code><?= e($field) ?></code></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>
