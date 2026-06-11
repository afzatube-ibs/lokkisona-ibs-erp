<?php

use App\Domain\SupplierTerminology;

$reportDetail = $reportDetail ?? null;
$statementTitle = SupplierTerminology::supplierReturnStatement();
$rateLabel = SupplierTerminology::returnRateLabel();
$lineLabel = SupplierTerminology::returnLineTotalLabel();
$totalLabel = SupplierTerminology::returnAmount();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($statementTitle) ?> — <?= e((string) ($reportDetail['report']['return_report_reference'] ?? '')) ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="quick-invoice-print-body srs-print-body">
<?php if ($reportDetail === null): ?>
<div class="quick-invoice-print">
    <p>Statement not found.</p>
</div>
<?php else: ?>
<?php
$report = $reportDetail['report'] ?? [];
$productRows = $reportDetail['product_rows'] ?? [];
$returnRef = (string) ($report['return_report_reference'] ?? '');
$returnDate = (string) ($report['return_date'] ?? $report['created_at'] ?? '');
$dateLabel = $returnDate !== '' ? date('d M Y', strtotime($returnDate)) : '—';
$displayQty = (int) ($reportDetail['total_quantity'] ?? 0);
$displayAmount = (float) ($reportDetail['total_amount'] ?? 0);
if ($displayAmount <= 0) {
    $displayAmount = (float) ($report['total_adjustment_amount'] ?? 0);
}
$totalReturns = (int) ($report['total_returns'] ?? count($reportDetail['items'] ?? []));
?>
<div class="quick-invoice-print srs-print dds-print">
    <div class="qip-actions no-print">
        <button type="button" class="btn btn-primary" onclick="window.print()">Print</button>
        <a href="<?= e(url('/return-report/' . rawurlencode($returnRef))) ?>" class="btn btn-secondary">Back to Statement</a>
    </div>

    <header class="qip-header srs-print-header">
        <div class="srs-print-title-block">
            <h1 class="qip-supplier"><?= e($statementTitle) ?></h1>
            <p class="qip-sub">Locked return cost snapshot · <?= e((string) ($reportDetail['business_source_name'] ?? '')) ?></p>
        </div>
        <div class="qip-meta srs-print-meta">
            <div><strong>Statement Ref</strong> <?= e($returnRef) ?></div>
            <div><strong>Return Date</strong> <?= e($dateLabel) ?></div>
            <div><strong>Supplier</strong> <?= e((string) ($reportDetail['supplier_name'] ?? '—')) ?></div>
            <div><strong>Business Source</strong> <?= e((string) ($reportDetail['business_source_name'] ?? '—')) ?></div>
            <div><strong>Prepared By</strong> <?= e((string) ($reportDetail['prepared_by'] ?? '—')) ?></div>
        </div>
    </header>

    <?php if (!empty($reportDetail['legacy_warning'])): ?>
    <section class="qip-notes dds-legacy-note">
        <?= e((string) $reportDetail['legacy_warning']) ?>
    </section>
    <?php endif; ?>

    <?php if (!empty($productRows)): ?>
    <table class="qip-table dds-lines-table dds-lines-table--flat dispatch-report-orders-table--payable srs-lines-table return-lines-table">
        <colgroup>
            <col class="col-sl">
            <col class="col-source">
            <col class="col-order">
            <col class="col-product">
            <col class="col-model">
            <col class="col-qty">
            <col class="col-num">
            <col class="col-num">
            <col class="col-reason">
            <col class="col-type">
        </colgroup>
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
            <tr>
                <td class="cell-sl"><?= e((string) ($row['sl'] ?? '')) ?></td>
                <td class="cell-source"><?= e((string) ($row['source_name'] ?? '—')) ?></td>
                <td class="cell-order"><?= e((string) ($row['order_no'] ?? '')) ?></td>
                <td class="cell-product srs-cell-wrap">
                    <?php if (!empty($row['image_url'])): ?>
                    <img src="<?= e((string) $row['image_url']) ?>" alt="" class="dispatch-report-product-thumb dds-print-thumb srs-print-thumb" loading="lazy">
                    <?php endif; ?>
                    <span class="srs-product-text"><?= e((string) ($row['product_name'] ?? '')) ?></span>
                </td>
                <td class="cell-model dispatch-report-model-option srs-cell-wrap">
                    <strong class="srs-model-text"><?= e((string) ($row['model'] ?? '')) ?></strong>
                    <?php foreach (($row['option_chips'] ?? []) as $chip): ?>
                    <span class="dispatch-report-option-chip srs-option-chip"><?= e((string) ($chip['label'] ?? '')) ?></span>
                    <?php endforeach; ?>
                </td>
                <td class="num cell-qty"><?= e((string) ($row['quantity'] ?? '0')) ?></td>
                <td class="num cell-rate"><?= e(number_format((float) ($row['rate'] ?? 0), 2)) ?></td>
                <td class="num cell-total"><?= e(number_format((float) ($row['line_total'] ?? 0), 2)) ?></td>
                <td class="cell-reason srs-cell-wrap"><?= e((string) ($row['return_reason'] ?? '—')) ?></td>
                <td class="cell-type srs-cell-wrap"><?= e((string) ($row['return_type'] ?? '—')) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <section class="qip-notes">No return lines found for this statement.</section>
    <?php endif; ?>

    <div class="qip-totals srs-print-totals">
        <div class="qip-total-row"><span>Total Returns</span><span><?= e((string) $totalReturns) ?></span></div>
        <div class="qip-total-row"><span>Total Qty</span><span><?= e((string) $displayQty) ?></span></div>
        <div class="qip-total-row qip-balance"><span><?= e($totalLabel) ?></span><span><?= e(number_format($displayAmount, 2)) ?> BDT</span></div>
    </div>

    <footer class="qip-footer srs-print-footer">
        <p>Locked dispatch-era cost snapshot — independent from Daily Dispatch. <?= e($totalLabel) ?> will reduce supplier payable when v2.5 Supplier Ledger posting is enabled (uses locked report snapshot, not current product cost).</p>
    </footer>
</div>
<?php endif; ?>
</body>
</html>
