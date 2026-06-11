<?php
/**
 * Dev verify: mixed supplier/source guards + locked snapshot reconciliation.
 */
require dirname(__DIR__) . '/app/bootstrap.php';

use App\Auth;
use App\Services\Write\ReturnReportWriteService;
use App\Support\ReturnStatementLinePresenter;

Auth::startSession();
$_SESSION['user'] = 'admin';

$pdo = new PDO(
    'mysql:host=' . config('database.host') . ';dbname=' . config('database.database') . ';charset=utf8mb4',
    config('database.username'),
    config('database.password'),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

echo "=== Return Report Guards + Snapshot Verify ===\n";

$svc = new ReturnReportWriteService();

$mixedSupplier = $svc->createStatement([
    'batch_confirmed' => '1',
    'return_receive_ids' => [1, 99],
]);
echo 'Mixed supplier (expect fail if ids differ): '
    . ($mixedSupplier->success ? 'UNEXPECTED OK' : 'BLOCKED: ' . $mixedSupplier->message) . "\n";

$mixedSource = $svc->createStatement([
    'batch_confirmed' => '1',
    'return_receive_ids' => [1, 2],
]);
echo 'Duplicate/mixed (expect fail): '
    . ($mixedSource->success ? 'UNEXPECTED OK' : 'BLOCKED: ' . $mixedSource->message) . "\n";

$item = $pdo->query(
    'SELECT rri.*, o.order_reference AS erp_order_reference, o.source_order_reference, o.business_source_id '
    . 'FROM ibs_return_report_items rri '
    . 'LEFT JOIN ibs_orders o ON o.order_id = rri.order_id '
    . 'ORDER BY rri.return_report_item_id DESC LIMIT 1'
)->fetch(PDO::FETCH_ASSOC);

if (!$item) {
    echo "No report items — skip snapshot test.\n";
    exit(0);
}

$locked = (float) ($item['product_cost_snapshot'] ?? 0);
$lines = ReturnStatementLinePresenter::build(
    [$item],
    [1 => 'Lokkisona.com'],
    [],
    [],
    [],
    ['business_source_id' => 1, 'total_returns' => 1]
);

$displayTotal = (float) ($lines['total_amount'] ?? 0);
echo "Locked item snapshot: $locked\n";
echo "Presenter total_amount: $displayTotal\n";
echo abs($locked - $displayTotal) < 0.01 ? "[OK] Totals match locked snapshot\n" : "[FAIL] Totals diverged\n";

$productId = (int) $pdo->query(
    'SELECT product_id FROM ibs_order_items WHERE order_id = ' . (int) ($item['order_id'] ?? 0) . ' LIMIT 1'
)->fetchColumn();
if ($productId > 0) {
    $before = (float) $pdo->query('SELECT product_cost FROM ibs_products WHERE product_id = ' . $productId)->fetchColumn();
    $pdo->exec('UPDATE ibs_products SET product_cost = product_cost + 999 WHERE product_id = ' . $productId);
    $linesAfter = ReturnStatementLinePresenter::build(
        [$item],
        [1 => 'Lokkisona.com'],
        [],
        [],
        [],
        ['business_source_id' => 1, 'total_returns' => 1]
    );
    $pdo->exec('UPDATE ibs_products SET product_cost = ' . $before . ' WHERE product_id = ' . $productId);
    $afterTotal = (float) ($linesAfter['total_amount'] ?? 0);
    echo "After catalog cost +999: presenter total=$afterTotal "
        . (abs($locked - $afterTotal) < 0.01 ? "[OK] Still locked\n" : "[FAIL] Followed catalog\n");
}

echo "Done.\n";
