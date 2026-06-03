<div class="page-header">
    <h1 class="page-title">Access Denied</h1>
    <p class="page-description">Your current role does not have permission to view this page.</p>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Permission Required</h2>
    </div>
    <div class="card-body">
        <p>This action requires <code><?= e($permission ?? 'unknown') ?></code>.</p>
        <p class="page-description">The denied access event has been recorded in the activity log.</p>
    </div>
</div>
