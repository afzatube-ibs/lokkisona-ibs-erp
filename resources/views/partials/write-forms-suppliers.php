<?php if (!empty($writeServiceReady)): ?>
<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header"><h2 class="card-title">Supplier Create (v0.3.1)</h2></div>
    <div class="card-body">
        <form method="post" action="<?= e(url('/suppliers/create')) ?>">
            <?= $csrfField ?? '' ?>
            <div class="form-grid" style="display: grid; gap: 0.75rem; max-width: 600px;">
                <label>Supplier name *<input type="text" name="supplier_name" required class="form-input" style="width:100%"></label>
                <label>Contact person<input type="text" name="contact_person" class="form-input" style="width:100%"></label>
                <label>Phone<input type="text" name="phone" class="form-input" style="width:100%"></label>
                <label>Email<input type="email" name="email" class="form-input" style="width:100%"></label>
                <label>Payment terms<input type="text" name="payment_terms" class="form-input" style="width:100%"></label>
                <label>Status<select name="status" class="form-input"><option value="active">active</option><option value="inactive">inactive</option></select></label>
                <button type="submit" class="btn btn-primary">Create supplier</button>
            </div>
        </form>
        <hr style="margin: 1.5rem 0;">
        <form method="post" action="<?= e(url('/suppliers/edit')) ?>">
            <?= $csrfField ?? '' ?>
            <div class="form-grid" style="display: grid; gap: 0.75rem; max-width: 600px;">
                <label>Supplier ID to edit *<input type="number" name="supplier_id" required min="1" class="form-input" style="width:100%"></label>
                <label>Supplier name *<input type="text" name="supplier_name" required class="form-input" style="width:100%"></label>
                <label>Contact person<input type="text" name="contact_person" class="form-input" style="width:100%"></label>
                <label>Phone<input type="text" name="phone" class="form-input" style="width:100%"></label>
                <label>Status<select name="status" class="form-input"><option value="active">active</option><option value="inactive">inactive</option></select></label>
                <button type="submit" class="btn btn-primary">Save supplier changes (v0.3.2)</button>
            </div>
        </form>
    </div>
</div>
<?php elseif (isset($writeServiceReady)): ?>
<p class="page-description">Write forms disabled until migration 0003 is manually applied.</p>
<?php endif; ?>
