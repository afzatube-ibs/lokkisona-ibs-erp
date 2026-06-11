<?php

use App\Domain\SupplierTerminology;



$reportDetail = $reportDetail ?? null;

$batchReference = $batchReference ?? '';

$productRows = $reportDetail['product_rows'] ?? [];

$statementTitle = SupplierTerminology::supplierReturnStatement();

$rateLabel = SupplierTerminology::returnRateLabel();

$lineLabel = SupplierTerminology::returnLineTotalLabel();

$totalLabel = SupplierTerminology::returnAmount();

?>

<div class="return-report-view-page">

<div class="page-header page-header-compact no-print">

    <h1 class="page-title"><?= e($statementTitle) ?></h1>

    <?php if ($reportDetail !== null): ?>

    <div class="page-header-actions">

        <a href="<?= e(url('/return-report/' . rawurlencode((string) ($reportDetail['report']['return_report_reference'] ?? $batchReference)) . '/print')) ?>" class="btn btn-sm btn-secondary" target="_blank" rel="noopener">Print Statement</a>

        <a href="<?= e(url('/return-reports')) ?>" class="btn btn-sm btn-ghost">Back to List</a>

        <a href="<?= e(url('/return-receive')) ?>" class="btn btn-sm btn-ghost">Return Receive</a>

    </div>

    <?php endif; ?>

</div>



<?php if ($reportDetail === null): ?>

<div class="empty-state">

    <p>Supplier Return Statement<?= $batchReference !== '' ? ' <strong>' . e($batchReference) . '</strong>' : '' ?> was not found.</p>

    <a href="<?= e(url('/return-reports')) ?>" class="btn btn-sm btn-secondary">Back to Return Reports</a>

</div>

<?php else: ?>

<?php

$report = $reportDetail['report'] ?? [];

$returnRef = (string) ($report['return_report_reference'] ?? '');

$displayQty = (int) ($reportDetail['total_quantity'] ?? 0);

$displayAmount = (float) ($reportDetail['total_amount'] ?? 0);

if ($displayAmount <= 0) {

    $displayAmount = (float) ($report['total_adjustment_amount'] ?? 0);

}

$totalReturns = (int) ($report['total_returns'] ?? count($reportDetail['items'] ?? []));

?>

<?php if (!empty($reportDetail['ledger_notice'])): ?>

<div class="workflow-info-banner no-print" style="margin-bottom: 1rem;">

    <?= e((string) $reportDetail['ledger_notice']) ?>

</div>

<?php endif; ?>



<?php if (!empty($reportDetail['legacy_warning'])): ?>

<div class="workflow-info-banner no-print" style="margin-bottom: 1rem; color: var(--color-warning, #b45309);">

    <?= e((string) $reportDetail['legacy_warning']) ?>

</div>

<?php endif; ?>



<div class="card dispatch-report-summary-card return-report-summary-card">

    <div class="card-body">

        <dl class="info-list dispatch-report-meta return-report-meta">

            <div class="info-row"><dt>Statement Ref</dt><dd><?= e($returnRef) ?></dd></div>

            <div class="info-row"><dt>Supplier</dt><dd><?= e((string) ($reportDetail['supplier_name'] ?? '—')) ?></dd></div>

            <div class="info-row"><dt>Business Source</dt><dd><?= e((string) ($reportDetail['business_source_name'] ?? '—')) ?></dd></div>

            <div class="info-row"><dt>Return Date</dt><dd><?= e((string) ($report['return_date'] ?? $report['created_at'] ?? '—')) ?></dd></div>

            <div class="info-row"><dt>Prepared By</dt><dd><?= e((string) ($reportDetail['prepared_by'] ?? '—')) ?></dd></div>

            <div class="info-row"><dt>Total Returns</dt><dd><?= e((string) $totalReturns) ?></dd></div>

            <div class="info-row"><dt>Total Qty</dt><dd><?= e((string) $displayQty) ?></dd></div>

            <div class="info-row"><dt><?= e($totalLabel) ?></dt><dd><?= e(number_format($displayAmount, 2)) ?> BDT</dd></div>

            <div class="info-row"><dt>Status</dt><dd><span class="badge badge-ok"><?= e((string) ($report['status_label'] ?? 'Created / Locked')) ?></span></dd></div>

        </dl>

        <p class="page-description dispatch-payable-note">Locked return cost snapshot — amounts locked at report creation. Independent from Daily Dispatch Statement.</p>

    </div>

</div>



<div class="card no-print">

    <div class="card-header"><h2 class="card-title">Return Lines</h2></div>

    <div class="card-body">

        <?php if (!empty($productRows)): ?>

        <div class="table-scroll dispatch-table-wrap">

            <table class="data-table dispatch-report-orders-table return-report-orders-table srs-lines-table srs-screen-table">

                <thead>

                    <tr>

                        <th>SL</th>

                        <th>Source</th>

                        <th>Order No</th>

                        <th>Product</th>

                        <th>Model / Option</th>

                        <th class="num">Qty</th>

                        <th class="num"><?= e($rateLabel) ?></th>

                        <th class="num"><?= e($lineLabel) ?></th>

                        <th>Return Reason</th>

                        <th>Return Type</th>

                    </tr>

                </thead>

                <tbody>

                    <?php foreach ($productRows as $row): ?>

                    <tr<?= !empty($row['snapshot_only']) ? ' class="dispatch-report-row--snapshot"' : '' ?>>

                        <td><?= e((string) ($row['sl'] ?? '')) ?></td>

                        <td><?= e((string) ($row['source_name'] ?? '—')) ?></td>

                        <td><?= e((string) ($row['order_no'] ?? '')) ?></td>

                        <td class="dispatch-report-product-cell">

                            <?php if (!empty($row['image_url'])): ?>

                            <img src="<?= e((string) $row['image_url']) ?>" alt="" class="dispatch-report-product-thumb" loading="lazy">

                            <?php endif; ?>

                            <span class="dispatch-report-product-name"><?= e((string) ($row['product_name'] ?? '')) ?></span>

                        </td>

                        <td class="dispatch-report-model-option">

                            <span class="dispatch-report-model"><?= e((string) ($row['model'] ?? '')) ?></span>

                            <?php foreach (($row['option_chips'] ?? []) as $chip): ?>

                            <span class="dispatch-report-option-chip"><?= e((string) ($chip['label'] ?? '')) ?></span>

                            <?php endforeach; ?>

                        </td>

                        <td class="num"><?= e((string) ($row['quantity'] ?? '0')) ?></td>

                        <td class="num"><?= e(number_format((float) ($row['rate'] ?? 0), 2)) ?></td>

                        <td class="num"><?= e(number_format((float) ($row['line_total'] ?? 0), 2)) ?></td>

                        <td><?= e((string) ($row['return_reason'] ?? '—')) ?></td>

                        <td><?= e((string) ($row['return_type'] ?? '—')) ?></td>

                    </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

        <?php else: ?>

        <div class="empty-state"><p>No return lines found for this statement.</p></div>

        <?php endif; ?>

    </div>

</div>

<?php endif; ?>

</div>


