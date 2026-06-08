<?php
$reportDetail = $reportDetail ?? null;
$printMode = !empty($printMode);
$batchReference = $batchReference ?? '';
?>
<div class="dispatch-report-view-page<?= $printMode ? ' dispatch-report-view-page--print' : '' ?>">
<div class="page-header page-header-compact">
    <h1 class="page-title">Dispatch Report</h1>
    <?php if ($reportDetail !== null && !$printMode): ?>
    <div class="page-header-actions">
        <a href="<?= e(url('/dispatch-report/' . rawurlencode((string) ($reportDetail['report']['dispatch_reference'] ?? $batchReference)) . '?print=1')) ?>" class="btn btn-sm btn-secondary" target="_blank" rel="noopener">Print</a>
        <button type="button" class="btn btn-sm btn-ghost" onclick="window.print()">Export PDF</button>
        <a href="<?= e(url('/order-workflow?status=dispatch_report_created&from_card=1')) ?>" class="btn btn-sm btn-ghost">Back to Vendor Fulfillment</a>
    </div>
    <?php endif; ?>
</div>

<?php if ($reportDetail === null): ?>
<div class="empty-state">
    <p>Dispatch report <strong><?= e($batchReference) ?></strong> was not found.</p>
    <a href="<?= e(url('/order-workflow')) ?>" class="btn btn-sm btn-secondary">Back to Vendor Fulfillment</a>
</div>
<?php else: ?>
<?php
$report = $reportDetail['report'] ?? [];
$items = $reportDetail['items'] ?? [];
?>
<div class="card dispatch-report-summary-card">
    <div class="card-body">
        <dl class="info-list dispatch-report-meta">
            <div class="info-row"><dt>Batch No</dt><dd><?= e((string) ($report['dispatch_reference'] ?? '')) ?></dd></div>
            <div class="info-row"><dt>Dispatch Date</dt><dd><?= e((string) ($report['dispatch_date'] ?? $report['created_at'] ?? '—')) ?></dd></div>
            <div class="info-row"><dt>Supplier</dt><dd><?= e((string) ($reportDetail['supplier_name'] ?? '—')) ?></dd></div>
            <div class="info-row"><dt>Prepared By</dt><dd><?= e((string) ($reportDetail['prepared_by'] ?? '—')) ?></dd></div>
            <div class="info-row"><dt>Order Count</dt><dd><?= e((string) ($report['total_orders'] ?? count($items))) ?></dd></div>
            <div class="info-row"><dt>Total Quantity</dt><dd><?= e((string) ($reportDetail['total_quantity'] ?? 0)) ?></dd></div>
            <div class="info-row"><dt>Total Cost Snapshot</dt><dd><?= e((string) ($report['total_product_cost'] ?? '0.00')) ?></dd></div>
        </dl>
    </div>
</div>

<div class="card">
    <div class="card-header"><h2 class="card-title">Orders in Batch</h2></div>
    <div class="card-body">
        <div class="table-scroll">
            <table class="data-table dispatch-report-orders-table">
                <thead>
                    <tr>
                        <th>Order No</th>
                        <th>Customer</th>
                        <th>Products</th>
                        <th>Qty</th>
                        <th>Cost</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?= e((string) ($item['order_no'] ?? '')) ?></td>
                        <td><?= e((string) ($item['customer_name'] ?? '')) ?></td>
                        <td>
                            <?php foreach (($item['product_lines'] ?? []) as $line): ?>
                            <div class="dispatch-report-product-line"><?= e((string) ($line['product_name'] ?? '')) ?><?php if (!empty($line['variant_label'])): ?> <span class="text-muted">(<?= e((string) $line['variant_label']) ?>)</span><?php endif; ?></div>
                            <?php endforeach; ?>
                        </td>
                        <td><?= e((string) ($item['item_count'] ?? '0')) ?></td>
                        <td><?= e((string) ($item['line_cost_total'] ?? $item['product_cost_snapshot'] ?? '0.00')) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>
</div>
