<dl class="info-list" style="margin-bottom: 0.75rem;">
    <div class="info-row"><dt>Order No / Reference</dt><dd><strong><?= e((string) ($order['order_reference'] ?? '')) ?></strong></dd></div>
    <div class="info-row"><dt>Order ID</dt><dd><?= e((string) ($order['erp_order_id'] ?? $order['order_id'] ?? '')) ?></dd></div>
    <div class="info-row"><dt>Customer</dt><dd><?= e((string) ($order['customer_name'] ?? '')) ?></dd></div>
    <div class="info-row"><dt>Fulfillment Status</dt><dd><?= e((string) ($order['fulfillment_status'] ?? '')) ?></dd></div>
    <div class="info-row"><dt>Courier Status</dt><dd><?= e((string) ($order['courier_status'] ?? '-')) ?></dd></div>
    <div class="info-row"><dt>Consignment ID / Tracking</dt><dd><?= e((string) ($order['consignment_id'] ?? '-')) ?></dd></div>
    <div class="info-row"><dt>OC Order Status</dt><dd><?= e((string) ($order['oc_order_status'] ?? '-')) ?></dd></div>
    <?php if (!empty($order['dispatch_report_reference']) && ($order['dispatch_report_reference'] ?? '-') !== '-'): ?>
    <div class="info-row"><dt>Dispatch Snapshot</dt><dd><?= e((string) $order['dispatch_report_reference']) ?></dd></div>
    <?php endif; ?>
    <div class="info-row"><dt>Total Qty</dt><dd><?= e((string) ($order['preview_item_count'] ?? '0')) ?></dd></div>
    <div class="info-row"><dt>Cost Snapshot Preview</dt><dd><?= e((string) ($order['preview_cost_snapshot'] ?? '0.00')) ?></dd></div>
</dl>

<?php if (!empty($order['product_lines'])): ?>
<div class="product-card-grid" style="margin-bottom: 1rem;">
    <?php foreach ($order['product_lines'] as $line): ?>
    <div class="card product-line-card">
        <div class="card-body">
            <p class="product-line-name" style="margin: 0 0 0.35rem;"><strong><?= e((string) ($line['product_name'] ?? '')) ?></strong></p>
            <dl class="info-list compact-info-list">
                <div class="info-row"><dt>Product ID</dt><dd><?= e((string) ($line['product_id'] ?? '')) ?></dd></div>
                <div class="info-row"><dt>Variant / Option</dt><dd><?= e((string) ($line['variant_label'] ?? '-')) ?></dd></div>
                <div class="info-row"><dt>Qty</dt><dd><?= e((string) ($line['quantity'] ?? '0')) ?></dd></div>
                <div class="info-row"><dt>Supplier Cost</dt><dd><?= e((string) ($line['supplier_cost_snapshot'] ?? '0.00')) ?></dd></div>
            </dl>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="card verification-panel" style="margin-bottom: 1rem; background: var(--surface-muted, #f6f8fa);">
    <div class="card-body">
        <h4 style="margin: 0 0 0.5rem;">ERP Verification</h4>
        <p class="page-description" style="margin: 0;">Confirm against ERP order/workflow data shown above.</p>
        <ul class="feature-list" style="margin-top: 0.5rem;">
            <li>Order Reference: <strong><?= e((string) ($order['order_reference'] ?? '')) ?></strong></li>
            <li>Consignment / Tracking: <strong><?= e((string) ($order['consignment_id'] ?? '-')) ?></strong></li>
            <li>Products: <strong><?= e((string) ($order['product_summary'] ?? '-')) ?></strong></li>
        </ul>
    </div>
</div>
