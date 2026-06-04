<div class="page-header">
    <h1 class="page-title">Suppliers</h1>
    <p class="page-description">Supplier Foundation for future database-backed supplier operations. No supplier records are written in this release.</p>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Current Primary Supplier</h2>
        </div>
        <div class="card-body">
            <dl class="info-list">
                <div class="info-row">
                    <dt>Supplier</dt>
                    <dd><?= e($primarySupplier['name']) ?></dd>
                </div>
                <div class="info-row">
                    <dt>Role</dt>
                    <dd><?= e($primarySupplier['role']) ?></dd>
                </div>
                <div class="info-row">
                    <dt>Channel</dt>
                    <dd><?= e($primarySupplier['channel']) ?></dd>
                </div>
            </dl>
            <p class="page-description"><?= e($primarySupplier['summary']) ?></p>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Supplier Operation Purpose</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($operationPurpose as $purpose): ?>
                    <li><?= e($purpose) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<div class="card-grid">
    <?php foreach ($foundationSections as $section): ?>
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><?= e($section['title']) ?></h2>
        </div>
        <div class="card-body">
            <p><?= e($section['description']) ?></p>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Planned Supplier Fields</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($plannedFields as $field): ?>
                    <li><?= e($field) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Supplier Accounting Terms</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($accountingTerms as $term): ?>
                    <li><?= e($term) ?></li>
                <?php endforeach; ?>
            </ul>
            <p class="page-description">These terms keep supplier accounting wording clean and consistent for the future payable and settlement workflow.</p>
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
            <p class="page-description">Owner and admin can view the supplier foundation now. Staff may view later depending on permission, and a supplier role should only see its own supplier area in future, not all suppliers.</p>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Manual Migration Required</h2>
        </div>
        <div class="card-body">
            <p>No suppliers table is created automatically and no supplier records are written in this release.</p>
            <p class="page-description">Real supplier data requires an owner/admin-reviewed manual migration before activation. No table creation, alteration, or schema repair runs on page load.</p>
        </div>
    </div>
</div>
