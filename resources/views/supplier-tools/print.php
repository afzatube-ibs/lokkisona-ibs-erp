<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($invoice['quick_invoice_reference'] ?? 'Quick Invoice') ?> — Print</title>
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="quick-invoice-print-body">
<div class="quick-invoice-print no-print-actions-top">
    <div class="qip-actions no-print">
        <button type="button" class="btn btn-primary" onclick="window.print()">Print</button>
        <form method="post" action="<?= e(url('/supplier-tools/quick-invoice/download')) ?>" style="display:inline;">
            <?= $csrfField ?? '' ?>
            <input type="hidden" name="supplier_quick_invoice_id" value="<?= e((string) ($invoice['supplier_quick_invoice_id'] ?? '')) ?>">
            <button type="submit" class="btn btn-secondary">Log Download &amp; Close Access</button>
        </form>
    </div>

    <header class="qip-header">
        <div>
            <h1 class="qip-supplier"><?= e((string) ($invoice['supplier_name'] ?? 'Iqbal & Brothers')) ?></h1>
            <p class="qip-sub">Supplier Quick Invoice — independent engagement document</p>
        </div>
        <div class="qip-meta">
            <div><strong>Invoice #</strong> <?= e((string) ($invoice['quick_invoice_reference'] ?? '')) ?></div>
            <div><strong>Date</strong> <?= e(date('d M Y', strtotime((string) ($invoice['generated_at'] ?? $invoice['created_at'] ?? 'now')))) ?></div>
        </div>
    </header>

    <section class="qip-customer">
        <h2>Bill To</h2>
        <p class="qip-customer-name"><?= e((string) ($invoice['customer_name'] ?? '')) ?></p>
        <?php if (!empty($invoice['customer_phone'])): ?>
            <p><?= e((string) $invoice['customer_phone']) ?></p>
        <?php endif; ?>
        <?php if (!empty($invoice['customer_address'])): ?>
            <p><?= e((string) $invoice['customer_address']) ?></p>
        <?php endif; ?>
    </section>

    <table class="qip-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Product</th>
                <th class="num">Qty</th>
                <th class="num">Unit Price</th>
                <th class="num">Line Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $i => $item): ?>
            <tr>
                <td><?= e((string) ($i + 1)) ?></td>
                <td><?= e((string) ($item['item_name'] ?? '')) ?></td>
                <td class="num"><?= e((string) ($item['quantity'] ?? '')) ?></td>
                <td class="num"><?= e(number_format((float) ($item['unit_price'] ?? 0), 2)) ?></td>
                <td class="num"><?= e(number_format((float) ($item['line_total'] ?? 0), 2)) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="qip-totals">
        <div class="qip-total-row"><span>Subtotal</span><span><?= e(number_format((float) ($invoice['subtotal'] ?? 0), 2)) ?> BDT</span></div>
        <div class="qip-total-row"><span>Discount</span><span>− <?= e(number_format((float) ($invoice['discount_amount'] ?? 0), 2)) ?> BDT</span></div>
        <div class="qip-total-row"><span>After Discount</span><span><?= e(number_format((float) ($invoice['invoice_total'] ?? 0), 2)) ?> BDT</span></div>
        <div class="qip-total-row"><span>Advance Paid</span><span>− <?= e(number_format((float) ($invoice['advance_amount'] ?? 0), 2)) ?> BDT</span></div>
        <div class="qip-total-row qip-balance"><span>Balance Due</span><span><?= e(number_format((float) ($invoice['balance_due'] ?? 0), 2)) ?> BDT</span></div>
    </div>

    <?php if (!empty($invoice['notes'])): ?>
    <section class="qip-notes">
        <strong>Notes:</strong> <?= e((string) $invoice['notes']) ?>
    </section>
    <?php endif; ?>

    <footer class="qip-footer">
        <p>This is a supplier engagement quick invoice. It does not affect IBS-LK ERP payables, orders, stock, or official invoice records.</p>
    </footer>
</div>
</body>
</html>
