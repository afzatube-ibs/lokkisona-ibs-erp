<?php
use App\Domain\SupplierTerminology;

$reportDetail = $reportDetail ?? null;
$statementTitle = SupplierTerminology::dailyDispatchStatement();
$rateLabel = SupplierTerminology::dispatchRateLabel();
$lineLabel = SupplierTerminology::dispatchLineTotalLabel();
$totalLabel = SupplierTerminology::totalDispatchedAmount();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($statementTitle) ?> — <?= e((string) ($reportDetail['report']['dispatch_reference'] ?? '')) ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="quick-invoice-print-body dds-print-body">
<?php if ($reportDetail === null): ?>
<div class="quick-invoice-print">
    <p>Statement not found.</p>
</div>
<?php else: ?>
<?php
$report = $reportDetail['report'] ?? [];
$productRows = $reportDetail['product_rows'] ?? [];
$dispatchRef = (string) ($report['dispatch_reference'] ?? '');
$dispatchDate = (string) ($report['dispatch_date'] ?? $report['created_at'] ?? '');
$dateLabel = $dispatchDate !== '' ? date('d M Y', strtotime($dispatchDate)) : '—';
$displayQty = (int) ($reportDetail['total_quantity'] ?? 0);
$displayAmount = (float) ($reportDetail['total_amount'] ?? 0);
if ($displayAmount <= 0) {
    $displayAmount = (float) ($report['total_product_cost'] ?? 0);
}
$totalOrders = (int) ($report['total_orders'] ?? count($reportDetail['items'] ?? []));
?>
<div class="quick-invoice-print dds-print">
    <div class="qip-actions no-print">
        <button type="button" class="btn btn-primary" onclick="window.print()">Print</button>
        <a href="<?= e(url('/dispatch-report/' . rawurlencode($dispatchRef))) ?>" class="btn btn-secondary">Back to Statement</a>
    </div>

    <header class="qip-header">
        <div>
            <h1 class="qip-supplier"><?= e($statementTitle) ?></h1>
            <p class="qip-sub">Supplier payable snapshot · <?= e((string) ($reportDetail['business_source_name'] ?? '')) ?></p>
        </div>
        <div class="qip-meta">
            <div><strong>Statement Ref</strong> <?= e($dispatchRef) ?></div>
            <div><strong>Dispatch Date</strong> <?= e($dateLabel) ?></div>
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
    <table class="qip-table dds-lines-table dds-lines-table--flat dispatch-report-orders-table--payable">
        <thead>
            <tr>
                <th>SL</th>
                <th>Source</th>
                <th>Order No</th>
                <th>Customer</th>
                <th>Product</th>
                <th>Model / Option</th>
                <th class="num">Qty</th>
                <th class="num"><?= e($rateLabel) ?></th>
                <th class="num"><?= e($lineLabel) ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($productRows as $row): ?>
            <tr>
                <td><?= e((string) ($row['sl'] ?? '')) ?></td>
                <td><?= e((string) ($row['source_name'] ?? '—')) ?></td>
                <td><?= e((string) ($row['order_no'] ?? '')) ?></td>
                <td><?= e((string) ($row['customer_name'] ?? '')) ?></td>
                <td>
                    <?php if (!empty($row['image_url'])): ?>
                    <img src="<?= e((string) $row['image_url']) ?>" alt="" class="dispatch-report-product-thumb dds-print-thumb" loading="lazy">
                    <?php endif; ?>
                    <?= e((string) ($row['product_name'] ?? '')) ?>
                </td>
                <td class="dispatch-report-model-option">
                    <strong><?= e((string) ($row['model'] ?? '')) ?></strong>
                    <?php foreach (($row['option_chips'] ?? []) as $chip): ?>
                    <span class="dispatch-report-option-chip"><?= e((string) ($chip['label'] ?? '')) ?></span>
                    <?php endforeach; ?>
                </td>
                <td class="num"><?= e((string) ($row['quantity'] ?? '0')) ?></td>
                <td class="num"><?= e(number_format((float) ($row['rate'] ?? 0), 2)) ?></td>
                <td class="num"><?= e(number_format((float) ($row['line_total'] ?? 0), 2)) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <section class="qip-notes">
        No product lines found for this dispatch statement.
    </section>
    <?php endif; ?>

    <div class="qip-totals">
        <div class="qip-total-row"><span>Total Orders</span><span><?= e((string) $totalOrders) ?></span></div>
        <div class="qip-total-row"><span>Total Qty</span><span><?= e((string) $displayQty) ?></span></div>
        <div class="qip-total-row qip-balance"><span><?= e($totalLabel) ?></span><span><?= e(number_format($displayAmount, 2)) ?> BDT</span></div>
    </div>

    <?php if (!empty($reportDetail['payable_draft_ref'])): ?>
    <section class="qip-notes">
        <strong>Payable checkpoint:</strong>
        <?= e((string) $reportDetail['payable_draft_ref']) ?> — draft only, awaiting owner review on Supplier Payables.
    </section>
    <?php endif; ?>

    <footer class="qip-footer">
        <p>Supplier payable snapshot — not a tax invoice or courier document. <?= e($totalLabel) ?> is the amount payable to the supplier for this dispatch batch.</p>
    </footer>
</div>
<?php endif; ?>
</body>
</html>
