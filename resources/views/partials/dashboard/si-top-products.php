<?php
$top_products = $top_products ?? [];
?>
<section class="card si-top-products">
    <div class="card-header card-header-flex">
        <h2 class="card-title">Top Products</h2>
        <a href="<?= e(url('/reports?report=product_sales')) ?>" class="btn btn-sm btn-ghost">Report</a>
    </div>
    <div class="card-body card-body-flush">
        <?php if ($top_products !== []): ?>
        <div class="table-scroll">
            <table class="data-table data-table-compact si-products-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Model</th>
                        <th>Orders</th>
                        <th>Dispatch</th>
                        <th>Stock</th>
                        <th>Payable</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($top_products as $row): ?>
                    <tr>
                        <td>
                            <div class="si-product-cell">
                                <span class="si-product-thumb" aria-hidden="true"></span>
                                <span><?= e((string) ($row['product'] ?? '')) ?></span>
                            </div>
                        </td>
                        <td><?= e((string) ($row['model'] ?? '—')) ?></td>
                        <td><?= e((string) ($row['orders'] ?? 0)) ?></td>
                        <td><?= e((string) ($row['dispatch'] ?? 0)) ?></td>
                        <td><?= e((string) ($row['stock'] ?? '—')) ?></td>
                        <td><?= e(number_format((float) ($row['payable_bdt'] ?? 0), 0)) ?></td>
                        <td><span class="badge badge-success"><?= e((string) ($row['status'] ?? 'Active')) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <p class="si-empty-note si-empty-pad">No product sales data yet for this period.</p>
        <?php endif; ?>
    </div>
</section>
