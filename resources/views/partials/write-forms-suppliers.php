<?php if (!empty($writeGateReady)): ?>
<div class="card mb-15">
    <div class="card-header"><h2 class="card-title">Supplier Create (v0.3.1)</h2></div>
    <div class="card-body">
        <form method="post" action="<?= e(url('/suppliers/create')) ?>">
            <?= $csrfField ?? '' ?>
    <div class="form-grid">
        <label>Supplier name *<input type="text" name="supplier_name" required class="form-input"></label>
        <label>Contact person<input type="text" name="contact_person" class="form-input"></label>
        <label>Phone<input type="text" name="phone" class="form-input"></label>
        <label>Email<input type="email" name="email" class="form-input"></label>
        <label>Payment terms<input type="text" name="payment_terms" class="form-input"></label>
        <label>Status<select name="status" class="form-input"><option value="active">active</option><option value="inactive">inactive</option></select></label>
        <button type="submit" class="btn btn-primary">Create supplier</button>
    </div>
        </form>
        <hr>
        <form method="post" action="<?= e(url('/suppliers/edit')) ?>">
            <?= $csrfField ?? '' ?>
    <div class="form-grid">
        <label>Supplier ID to edit *<input type="number" name="supplier_id" required min="1" class="form-input"></label>
        <label>Supplier name *<input type="text" name="supplier_name" required class="form-input"></label>
        <label>Contact person<input type="text" name="contact_person" class="form-input"></label>
        <label>Phone<input type="text" name="phone" class="form-input"></label>
        <label>Status<select name="status" class="form-input"><option value="active">active</option><option value="inactive">inactive</option></select></label>
        <button type="submit" class="btn btn-primary">Save supplier changes (v0.3.2)</button>
    </div>
        </form>
    </div>
</div>
<?php else: ?>
<?php view('partials.write-gate-warning', ['writeGateReady' => $writeGateReady ?? false, 'writeGate' => $writeGate ?? []]); ?>
<?php endif; ?>
