<div class="page-header page-header-flex">
    <div>
        <h1 class="page-title">Reports</h1>
        <p class="page-description">Supplier Statement &amp; Reports — v<?= e($appVersion) ?> — <?= e($appReleaseLabel ?? '') ?>. Read-only reports with print and CSV export. Uses locked dispatch snapshots and posted ledger entries only.</p>
    </div>
    <?php if (!empty($reportData) && !empty($reportData['rows'])): ?>
    <div class="report-actions no-print">
        <button type="button" class="btn btn-secondary" onclick="window.print()">Print</button>
        <button type="button" class="btn btn-secondary" id="exportCsvBtn">Export CSV</button>
    </div>
    <?php endif; ?>
</div>

<div class="card mb-15 no-print">
    <div class="card-header">
        <h2 class="card-title">Select Report</h2>
    </div>
    <div class="card-body">
        <form method="get" action="<?= e(url('/reports')) ?>" class="form-grid form-grid-wide">
            <div class="form-group">
                <label for="report">Report</label>
                <select name="report" id="report" class="form-input" required>
                    <option value="">Choose report</option>
                    <?php foreach ($definitions as $key => $def): ?>
                        <option value="<?= e($key) ?>" <?= ($reportKey ?? '') === $key ? 'selected' : '' ?>>
                            <?= e($def['title']) ?> (<?= e($def['group']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="supplier_id">Supplier (optional)</label>
                <select name="supplier_id" id="supplier_id" class="form-input">
                    <option value="">All suppliers</option>
                    <?php foreach ($suppliers as $supplier): ?>
                        <option value="<?= e((string) ($supplier['supplier_id'] ?? '')) ?>" <?= (int) ($supplier['supplier_id'] ?? 0) === (int) ($selectedSupplierId ?? 0) ? 'selected' : '' ?>>
                            <?= e((string) ($supplier['supplier_name'] ?? '')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="month">Month (monthly report)</label>
                <input type="month" name="month" id="month" class="form-input" value="<?= e($selectedMonth ?? date('Y-m')) ?>">
            </div>
            <div class="form-group form-actions">
                <button type="submit" class="btn btn-primary">Run Report</button>
            </div>
        </form>
    </div>
</div>

<?php if (!empty($reportData)): ?>
<div class="card report-output" id="reportOutput">
    <div class="card-header">
        <h2 class="card-title"><?= e($reportData['title']) ?></h2>
    </div>
    <div class="card-body card-body-flush">
        <p class="report-summary"><?= e($reportData['summary']) ?></p>
        <?php if (empty($reportData['rows'])): ?>
            <div class="empty-state">
                <p><?= e($reportData['empty_message'] ?? 'No data.') ?></p>
            </div>
        <?php else: ?>
        <div class="table-scroll">
            <table class="data-table" id="reportTable">
                <thead>
                    <tr>
                        <?php foreach ($reportData['columns'] as $col): ?>
                            <th><?= e($col) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reportData['rows'] as $row): ?>
                    <tr>
                        <?php foreach ($row as $cell): ?>
                            <td><?= e((string) $cell) ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($reportData) && !empty($reportData['rows'])): ?>
<script>
(function () {
    var btn = document.getElementById('exportCsvBtn');
    var table = document.getElementById('reportTable');
    if (!btn || !table) return;
    btn.addEventListener('click', function () {
        var rows = [];
        table.querySelectorAll('tr').forEach(function (tr) {
            var cells = [];
            tr.querySelectorAll('th, td').forEach(function (cell) {
                var text = (cell.textContent || '').split('"').join('""');
                cells.push('"' + text + '"');
            });
            rows.push(cells.join(','));
        });
        var blob = new Blob([rows.join('\n')], { type: 'text/csv;charset=utf-8;' });
        var link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'ibs-report-<?= e($reportKey ?? 'export') ?>.csv';
        link.click();
    });
})();
</script>
<?php endif; ?>
