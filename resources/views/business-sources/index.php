<div class="page-header">
    <h1 class="page-title">Business Sources</h1>
    <p class="page-description">Business source read inventory plus controlled create/edit (v0.3.1–v0.3.2) when migration 0003 is applied.</p>
</div>

<?php view('partials.flash-messages', ['flashSuccess' => $flashSuccess ?? null, 'flashError' => $flashError ?? null]); ?>
<?php if (!empty($writeGateReady)): ?>
<div class="card" style="margin-bottom:1.5rem;"><div class="card-header"><h2 class="card-title">Business Source Create / Edit</h2></div><div class="card-body">
<form method="post" action="<?= e(url('/business-sources/create')) ?>"><?= $csrfField ?? '' ?>
<label>Source name *<input name="source_name" required style="width:100%"></label>
<label>Source type *<input name="source_type" required placeholder="Ecommerce Website" style="width:100%"></label>
<label>Website domain<input name="website_domain" style="width:100%"></label>
<button type="submit">Create source</button></form>
<hr><form method="post" action="<?= e(url('/business-sources/edit')) ?>"><?= $csrfField ?? '' ?>
<label>Source ID *<input type="number" name="business_source_id" required min="1"></label>
<label>Source name *<input name="source_name" required style="width:100%"></label>
<label>Source type *<input name="source_type" required style="width:100%"></label>
<button type="submit">Save source changes</button></form>
</div></div>
<?php else: ?>
<?php view('partials.write-gate-warning', ['writeGateReady' => $writeGateReady ?? false, 'writeGate' => $writeGate ?? []]); ?>
<?php endif; ?>

<?php view('partials.read-inventory-card', ['readInventory' => $readInventory, 'recordLabel' => 'business source', 'cardTitle' => 'Read-Only Inventory']); ?>

<h2 class="section-heading" style="margin: 1.5rem 0 1rem;">Planning Foundation</h2>

<div class="card-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Current Primary Source</h2>
        </div>
        <div class="card-body">
            <dl class="info-list">
                <div class="info-row">
                    <dt>Source</dt>
                    <dd><?= e($currentSource['name']) ?></dd>
                </div>
                <div class="info-row">
                    <dt>Type</dt>
                    <dd><?= e($currentSource['type']) ?></dd>
                </div>
                <div class="info-row">
                    <dt>Label</dt>
                    <dd><?= e($currentSource['label']) ?></dd>
                </div>
            </dl>
            <p class="page-description"><?= e($currentSource['summary']) ?></p>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Current Primary Supplier Relationship</h2>
        </div>
        <div class="card-body">
            <dl class="info-list">
                <div class="info-row">
                    <dt>Supplier</dt>
                    <dd><?= e($primarySupplierRelationship['supplier']) ?></dd>
                </div>
                <div class="info-row">
                    <dt>Relationship</dt>
                    <dd><?= e($primarySupplierRelationship['relationship']) ?></dd>
                </div>
            </dl>
            <p class="page-description"><?= e($primarySupplierRelationship['summary']) ?></p>
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
            <h2 class="card-title">Planned Business / Source Fields</h2>
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
            <h2 class="card-title">Source Type Examples</h2>
        </div>
        <div class="card-body">
            <ul class="feature-list">
                <?php foreach ($sourceTypes as $type): ?>
                    <li><?= e($type) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Planned Business Sources</h2>
    </div>
    <div class="card-body card-body-flush">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Source</th>
                    <th>Platform</th>
                    <th>Planning Note</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($plannedBusinessSources as $source): ?>
                <tr>
                    <td class="cell-name"><?= e($source['name']) ?></td>
                    <td><?= e($source['platform']) ?></td>
                    <td class="cell-detail"><?= e($source['note']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <p class="page-description" style="padding: 1rem;">Lokkisona = OpenCart, Sonamoni = WooCommerce, Manual/Offline = external reference entry. Manual / Offline and Sonamoni external reference orders can enter the same supplier workflow. Each Business Source can later have its own ERP invoice template/style. See <a href="<?= e(url('/manual-orders')) ?>">Manual Orders planning foundation</a>, <a href="<?= e(url('/sync-preview')) ?>">Sync Preview planning foundation</a>, and <a href="<?= e(url('/invoice-printing')) ?>">Invoice Printing planning foundation</a>.</p>
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
            <p class="page-description">Owner and admin can view the Business Sources foundation now. Staff and supplier roles should not manage business sources yet.</p>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Manual Migration Required</h2>
        </div>
        <div class="card-body">
            <p>No business, source, or sales channel tables are created automatically and no database records are written in this release.</p>
            <p class="page-description">Real business source and sales channel data requires an owner/admin-reviewed manual migration before activation. No table creation, alteration, or schema repair runs on page load.</p>
        </div>
    </div>
</div>
