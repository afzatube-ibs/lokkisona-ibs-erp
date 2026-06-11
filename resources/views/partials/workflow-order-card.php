<?php
$displayActionNote = $displayActionNote ?? static function (?string $note): string {
    $note = trim((string) $note);

    return $note !== '' ? $note : '-';
};
?>
<div class="card workflow-order-card">
    <div class="card-body">
        <dl class="info-list" style="margin-bottom: 0.75rem;">
            <div class="info-row"><dt>Order No</dt><dd><strong><?= e($order['order_reference']) ?></strong></dd></div>
            <div class="info-row"><dt>Customer</dt><dd><?= e($order['customer_name']) ?></dd></div>
            <?php if (!empty($order['source_order_reference'])): ?>
            <div class="info-row"><dt>Source / OC Ref</dt><dd><?= e($order['source_order_reference']) ?></dd></div>
            <?php endif; ?>
            <?php if (!empty($order['oc_order_status'])): ?>
            <div class="info-row"><dt>OC Order Status</dt><dd><?= e($order['oc_order_status']) ?></dd></div>
            <?php endif; ?>
            <?php if (!empty($order['tracking_number'])): ?>
            <div class="info-row"><dt>Consignment / Tracking</dt><dd><?= e($order['tracking_number']) ?></dd></div>
            <?php endif; ?>
            <?php if (!empty($order['courier_status'])): ?>
            <div class="info-row"><dt>Courier Status</dt><dd><?= e($order['courier_status']) ?></dd></div>
            <?php endif; ?>
            <?php if (!empty($order['dispatch_report_reference'])): ?>
            <div class="info-row"><dt>Created Report</dt><dd><strong><?= e($order['dispatch_report_reference']) ?></strong></dd></div>
            <?php endif; ?>
            <div class="info-row"><dt>Fulfillment Status</dt><dd><?= e($order['ibs_status_label']) ?><?php if (!empty($order['legacy_status'])): ?> <span class="badge badge-warn">legacy: <?= e($order['legacy_status']) ?></span><?php endif; ?></dd></div>
        </dl>

        <?php if (!empty($order['product_lines'])): ?>
        <div class="product-card-grid" style="margin-bottom: 0.5rem;">
            <?php foreach ($order['product_lines'] as $line): ?>
            <div class="card product-line-card">
                <div class="card-body">
                    <p style="margin: 0 0 0.25rem;"><strong><?= e((string) ($line['product_name'] ?? '')) ?></strong></p>
                    <span class="page-description">ID <?= e((string) ($line['product_id'] ?? '')) ?> · <?= e((string) ($line['variant_label'] ?? '-')) ?> · Qty <?= e((string) ($line['quantity'] ?? '0')) ?><?php if ((float) ($line['cost_snapshot'] ?? 0) > 0): ?> · <?= !empty($isSupplierView) ? 'Sale' : 'Cost' ?> <?= e(number_format((float) $line['cost_snapshot'], 2)) ?> × <?= e((string) ($line['quantity'] ?? '0')) ?> = <strong><?= e(number_format((float) ($line['line_cost_total'] ?? 0), 2)) ?></strong><?php endif; ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="workflow-order-totals" style="display:flex; gap:1.25rem; flex-wrap:wrap; margin-bottom:0.75rem; padding:0.5rem 0.75rem; border-top:1px solid var(--color-border); border-bottom:1px solid var(--color-border);">
            <span class="page-description" style="margin:0;">Total Qty: <strong><?= e((string) ($order['total_quantity'] ?? 0)) ?></strong></span>
            <span class="page-description" style="margin:0;"><?= !empty($isSupplierView) ? 'Total Sale (snapshot)' : 'Total Cost (snapshot)' ?>: <strong><?= e(number_format((float) ($order['total_cost_snapshot'] ?? 0), 2)) ?> BDT</strong></span>
        </div>
        <?php endif; ?>

        <?php if (!empty($order['status_info_note'])): ?>
        <p class="workflow-info-banner"><?= e($order['status_info_note']) ?></p>
        <?php endif; ?>
        <?php if (!empty($order['dispatch_redirect_note'])): ?>
        <p class="workflow-info-banner"><?= e($order['dispatch_redirect_note']) ?> <a href="<?= e(url('/dispatch-reports')) ?>">Daily Dispatch</a></p>
        <?php endif; ?>

        <?php if (!empty($order['actions'])): ?>
        <div class="workflow-action-grid">
            <?php foreach ($order['actions'] as $action): ?>
            <form method="post" action="<?= e(url('/order-workflow/action')) ?>" class="workflow-action-form js-workflow-action-form" data-confirm-label="<?= e($action['label']) ?>">
                <?= $csrfField ?? '' ?>
                <input type="hidden" name="order_id" value="<?= e((string) $order['order_id']) ?>">
                <?php if (!empty($statusFilter)): ?>
                <input type="hidden" name="return_status" value="<?= e($statusFilter) ?>">
                <?php endif; ?>
                <input type="hidden" name="to_status" value="<?= e($action['code']) ?>">
                <input type="hidden" name="action_confirmed" value="0" class="js-action-confirmed">
                <strong><?= e($action['label']) ?></strong>
                <?php if (!empty($action['requires_checkbox']) && !empty($action['checkbox_label'])): ?>
                <label class="workflow-confirm-checkbox">
                    <input type="checkbox" name="staff_confirmation" value="1" required>
                    <span><?= e($action['checkbox_label']) ?></span>
                </label>
                <?php endif; ?>
                <?php if (!empty($action['is_delivery_stop'])): ?>
                <?php view('partials.choice-cards', [
                    'name' => 'delivery_stop_reason',
                    'legend' => 'Delivery Stop reason',
                    'options' => $deliveryStopReasonOptions ?? [],
                    'required' => true,
                ]); ?>
                <label style="margin-top: 0.5rem;">
                    Extra note (optional)
                    <textarea name="action_note" class="form-input" placeholder="Optional detail for courier or customer"></textarea>
                </label>
                <?php else: ?>
                <label>
                    Action note<?= !empty($action['requires_note']) ? ' *' : ' (optional)' ?>
                    <textarea name="action_note" class="form-input" <?= !empty($action['requires_note']) ? 'required' : '' ?>></textarea>
                </label>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary" style="margin-top: 0.5rem;"><?= e($action['label']) ?></button>
            </form>
            <?php endforeach; ?>
        </div>
        <?php elseif (empty($order['status_info_note'])): ?>
        <p class="page-description">No supplier action at this stage.</p>
        <?php endif; ?>

        <?php if (!empty($order['histories'])): ?>
        <h4 style="margin: 1rem 0 0.5rem;">Workflow History</h4>
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>When</th>
                        <th>From</th>
                        <th>To</th>
                        <th>Action Note</th>
                        <th>Changed By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($order['histories'] as $history): ?>
                    <tr>
                        <td><?= e((string) ($history['changed_at'] ?? '')) ?></td>
                        <td><?= e((string) ($history['from_label'] ?? $history['from_status'] ?? '')) ?></td>
                        <td><?= e((string) ($history['to_label'] ?? $history['to_status'] ?? '')) ?></td>
                        <td><strong class="audit-note"><?= e($displayActionNote((string) ($history['action_note'] ?? ''))) ?></strong></td>
                        <td><?= e((string) ($history['changed_by'] ?? '')) !== '' ? e((string) $history['changed_by']) : '-' ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
