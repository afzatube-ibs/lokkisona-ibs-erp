<?php
$reportDetail = $reportDetail ?? null;
$printMode = !empty($printMode);
$batchReference = $batchReference ?? '';
$productRows = $reportDetail['product_rows'] ?? [];
?>
<div class="dispatch-report-view-page<?= $printMode ? ' dispatch-report-view-page--print' : '' ?>">
<?php if ($printMode && $reportDetail !== null): ?>
<div class="dispatch-report-print-header">
    <h1>Dispatch Report</h1>
    <p><?= e((string) ($reportDetail['report']['dispatch_reference'] ?? $batchReference)) ?> · <?= e((string) ($reportDetail['supplier_name'] ?? '')) ?> · <?= e((string) ($reportDetail['report']['created_at'] ?? '')) ?></p>
</div>
<?php endif; ?>

<div class="page-header page-header-compact no-print">
    <h1 class="page-title">Dispatch Report</h1>
    <?php if ($reportDetail !== null): ?>
    <div class="page-header-actions">
        <a href="<?= e(url('/dispatch-report/' . rawurlencode((string) ($reportDetail['report']['dispatch_reference'] ?? $batchReference)) . '?print=1')) ?>" class="btn btn-sm btn-secondary" target="_blank" rel="noopener">Print</a>
        <a href="<?= e(url('/dispatch-reports')) ?>" class="btn btn-sm btn-ghost">Back to List</a>
        <a href="<?= e(url('/order-workflow?status=dispatch_report_created&from_card=1')) ?>" class="btn btn-sm btn-ghost">Vendor Fulfillment</a>
    </div>
    <?php endif; ?>
</div>

<?php if ($reportDetail === null): ?>
<div class="empty-state">
    <p>Dispatch report<?= $batchReference !== '' ? ' <strong>' . e($batchReference) . '</strong>' : '' ?> was not found.</p>
    <a href="<?= e(url('/dispatch-reports')) ?>" class="btn btn-sm btn-secondary">Back to Dispatch Reports</a>
</div>
<?php else: ?>
<?php
$report = $reportDetail['report'] ?? [];
?>
<?php if (!empty($reportDetail['payable_notice'])): ?>
<div class="workflow-info-banner payable-notice--screen-only no-print" style="margin-bottom: 1rem;">
    <?= e((string) $reportDetail['payable_notice']) ?>
</div>
<?php endif; ?>

<div class="card dispatch-report-summary-card">
    <div class="card-body">
        <dl class="info-list dispatch-report-meta">
            <div class="info-row"><dt>Batch Reference</dt><dd><?= e((string) ($report['dispatch_reference'] ?? '')) ?></dd></div>
            <div class="info-row"><dt>Supplier</dt><dd><?= e((string) ($reportDetail['supplier_name'] ?? '—')) ?></dd></div>
            <div class="info-row"><dt>Business Source</dt><dd><?= e((string) ($reportDetail['business_source_name'] ?? '—')) ?></dd></div>
            <div class="info-row"><dt>Created Date</dt><dd><?= e((string) ($report['created_at'] ?? $report['dispatch_date'] ?? '—')) ?></dd></div>
            <div class="info-row"><dt>Created By</dt><dd><?= e((string) ($reportDetail['prepared_by'] ?? '—')) ?></dd></div>
            <div class="info-row"><dt>Total Orders</dt><dd><?= e((string) ($report['total_orders'] ?? count($reportDetail['items'] ?? []))) ?></dd></div>
            <div class="info-row"><dt>Total Qty</dt><dd><?= e((string) ($reportDetail['total_quantity'] ?? 0)) ?></dd></div>
            <div class="info-row"><dt>Total Cost Snapshot</dt><dd><?= e((string) ($report['total_product_cost'] ?? '0.00')) ?></dd></div>
            <div class="info-row"><dt>Status</dt><dd><span class="badge badge-ok"><?= e((string) ($report['status_label'] ?? 'Created / Locked')) ?></span></dd></div>
        </dl>
    </div>
</div>

<div class="card">
    <div class="card-header"><h2 class="card-title">Orders &amp; Products</h2></div>
    <div class="card-body">
        <div class="table-scroll">
            <table class="data-table dispatch-report-orders-table">
                <thead>
                    <tr>
                        <th>Order No</th>
                        <th>Customer</th>
                        <th>Phone</th>
                        <th>Product</th>
                        <th>Model</th>
                        <th>Options</th>
                        <th>Qty</th>
                        <th>Unit Cost</th>
                        <th>Line Cost</th>
                        <th>Courier</th>
                        <th>Consignment</th>
                        <th>OC Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($productRows as $row): ?>
                    <tr>
                        <td><?= e((string) ($row['order_no'] ?? '')) ?></td>
                        <td><?= e((string) ($row['customer_name'] ?? '')) ?></td>
                        <td><?= e((string) ($row['customer_phone'] ?? '')) ?></td>
                        <td class="dispatch-report-product-cell">
                            <?php if (!empty($row['image_url'])): ?>
                            <img src="<?= e((string) $row['image_url']) ?>" alt="" class="dispatch-report-product-thumb" loading="lazy">
                            <?php else: ?>
                            <span class="dispatch-report-product-thumb-empty">—</span>
                            <?php endif; ?>
                        </td>
                        <td><?= e((string) ($row['model'] ?? '')) ?></td>
                        <td>
                            <?php foreach (($row['option_chips'] ?? []) as $chip): ?>
                            <span class="dispatch-report-option-chip"><?= e((string) ($chip['label'] ?? '')) ?></span>
                            <?php endforeach; ?>
                        </td>
                        <td><?= e((string) ($row['quantity'] ?? '0')) ?></td>
                        <td><?= e(number_format((float) ($row['unit_cost_snapshot'] ?? 0), 2)) ?></td>
                        <td><?= e(number_format((float) ($row['line_cost_snapshot'] ?? 0), 2)) ?></td>
                        <td><?= e((string) ($row['courier_status'] ?? '—')) ?></td>
                        <td><?= e((string) ($row['consignment_id'] ?? '—')) ?></td>
                        <td><?= e((string) ($row['oc_order_status'] ?? '—')) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>
</div>

<?php if ($printMode && $reportDetail !== null): ?>
<script>window.addEventListener('load', function () { window.print(); });</script>
<?php endif; ?>
