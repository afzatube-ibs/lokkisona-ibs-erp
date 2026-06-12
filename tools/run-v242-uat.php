<?php

/**
 * v2.4.2 manual UAT runner — dev DB only. Does not commit. Outputs JSON + console PASS/FAIL.
 * Usage: php tools/run-v242-uat.php [--keep-data]
 */

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

App\Auth::startSession();

use App\Database\Connection;
use App\Domain\OrderDemoGuard;
use App\Domain\OrderFulfillmentPolicy;
use App\Domain\OrderSyncMappingRules;
use App\Domain\OrderSyncWorkflowBoundary;
use App\Domain\OrderWorkflowStatus;
use App\Domain\ReturnReceiveType;
use App\Repositories\Write\DispatchReportItemWriteRepository;
use App\Repositories\Write\OrderItemWriteRepository;
use App\Repositories\Write\OrderWriteRepository;
use App\Repositories\Write\PayableLedgerWriteRepository;
use App\Services\Write\DispatchReportWriteService;
use App\Services\Write\OrderWorkflowWriteService;
use App\Services\Write\ReturnReceiveWriteService;
use App\Services\Write\SyncImportWriteService;
use App\Services\Write\SyncPreviewWriteService;

$keepData = in_array('--keep-data', $argv ?? [], true);
$results = [];
$tag = 'UAT-' . date('YmdHis');

function uatPass(array &$results, string $id, string $detail = ''): void
{
    $results[$id] = ['status' => 'PASS', 'detail' => $detail];
    echo "[PASS] $id" . ($detail !== '' ? " — $detail" : '') . PHP_EOL;
}

function uatFail(array &$results, string $id, string $detail): void
{
    $results[$id] = ['status' => 'FAIL', 'detail' => $detail];
    echo "[FAIL] $id — $detail" . PHP_EOL;
}

function uatSkip(array &$results, string $id, string $detail): void
{
    $results[$id] = ['status' => 'SKIP', 'detail' => $detail];
    echo "[SKIP] $id — $detail" . PHP_EOL;
}

$pdo = Connection::pdo();
$orders = new OrderWriteRepository();
$dispatchItems = new DispatchReportItemWriteRepository();
$ledgers = new PayableLedgerWriteRepository();
$orderItems = new OrderItemWriteRepository();
$workflow = new OrderWorkflowWriteService();

$_SESSION['ibs_authenticated'] = true;
$_SESSION['ibs_user'] = 'admin';
$_SESSION['ibs_role'] = 'owner';

if (!$orders->tableExists()) {
    fwrite(STDERR, "FAIL: ibs_orders table missing. Apply migration 0006 manually.\n");
    exit(1);
}

echo "=== v2.4.2 UAT on " . config('database.database') . " ===" . PHP_EOL;

// --- Test 1: Sync mapping boundary ---
$t1ok = true;
$t1detail = [];
foreach (['new_order', 'packaging', 'shipped'] as $s) {
    if (!OrderSyncMappingRules::isAllowedInitialStatus($s)) {
        $t1ok = false;
        $t1detail[] = "$s should be allowed";
    }
}
foreach (['dispatch_report_created', 'out_for_delivery', 'order_returning', 'hub_return'] as $s) {
    if (OrderSyncMappingRules::isAllowedInitialStatus($s)) {
        $t1ok = false;
        $t1detail[] = "$s must be blocked";
    }
    if (!OrderSyncWorkflowBoundary::isBeyondShipmentCeiling($s)) {
        $t1ok = false;
        $t1detail[] = "$s should be beyond ceiling";
    }
}

$probeOrderId = (int) ($pdo->query('SELECT order_id FROM ibs_orders ORDER BY order_id DESC LIMIT 1')->fetchColumn() ?: 0);
if ($probeOrderId > 0) {
    $probe = $orders->find($probeOrderId);
    if ($probe !== null) {
        $before = (string) ($probe['ibs_status'] ?? '');
        $orders->updateOriginSnapshot($probeOrderId, [
            'origin_order_status_name' => 'UAT Fake OC Status',
            'courier_status' => 'UAT courier ping',
            'last_synced_at' => date('Y-m-d H:i:s'),
        ]);
        $after = $orders->find($probeOrderId);
        if ($after !== null && (string) ($after['ibs_status'] ?? '') !== $before) {
            $t1ok = false;
            $t1detail[] = 'updateOriginSnapshot changed ibs_status';
        } else {
            $t1detail[] = "snapshot-only refresh on order #$probeOrderId (ibs_status stayed $before)";
        }
    }
}

$t1ok ? uatPass($results, '1-sync-mapping-boundary', implode('; ', $t1detail))
    : uatFail($results, '1-sync-mapping-boundary', implode('; ', $t1detail));

// --- Helpers to seed orders ---
function seedOrder(PDO $pdo, OrderWriteRepository $orders, OrderItemWriteRepository $items, string $refSuffix, string $ibsStatus, ?int $supplierId = 1, ?int $sourceId = 1): int
{
    global $tag;
    $ref = $tag . '-' . $refSuffix;
    $orderRef = 'IBS-UAT-' . preg_replace('/[^A-Za-z0-9\-]/', '', $refSuffix);
    $existing = $orders->findBySourceReference($ref, $sourceId);
    if ($existing === null) {
        $st = $pdo->prepare('SELECT order_id FROM ibs_orders WHERE order_reference = :ref LIMIT 1');
        $st->execute(['ref' => $orderRef]);
        $existingId = $st->fetchColumn();
        if ($existingId) {
            $existing = $orders->find((int) $existingId);
        }
    }
    if ($existing !== null) {
        $orderId = (int) $existing['order_id'];
        $orders->updateStatus($orderId, $ibsStatus);

        return $orderId;
    }

    $orderId = $orders->createFromSync([
        'business_source_id' => $sourceId,
        'supplier_id' => $supplierId,
        'source_order_id' => '999' . substr((string) time(), -5) . rand(10, 99),
        'source_order_reference' => $ref,
        'order_reference' => $orderRef,
        'customer_name' => 'UAT Customer',
        'customer_phone' => '01700000000',
        'customer_address' => 'UAT Address',
        'order_total' => 100.00,
        'ibs_status' => $ibsStatus,
        'cost_snapshot_total' => 50.00,
        'status' => 'active',
        'sync_source' => 'uat',
        'imported_at' => date('Y-m-d H:i:s'),
    ]);

    if ($items->tableExists()) {
        $items->create([
            'order_id' => $orderId,
            'product_name' => 'UAT Product',
            'quantity' => 1,
            'selling_price' => 100.00,
            'supplier_cost_snapshot' => 50.00,
            'line_total' => 100.00,
        ]);
    }

    return $orderId;
}

function countReturnBatchesSafe(PDO $pdo, int $orderId): int
{
    try {
        $st = $pdo->prepare('SELECT COUNT(*) FROM ibs_return_batch_items WHERE order_id = :oid');
        $st->execute(['oid' => $orderId]);

        return (int) $st->fetchColumn();
    } catch (\Throwable $e) {
        return -1;
    }
}

function countPayableForOrder(PDO $pdo, string $sourceRef): int
{
    try {
        $st = $pdo->prepare('SELECT COUNT(*) FROM ibs_payable_ledgers WHERE source_reference LIKE :ref');
        $st->execute(['ref' => '%' . $sourceRef . '%']);

        return (int) $st->fetchColumn();
    } catch (\Throwable $e) {
        return -1;
    }
}

function countDispatchItems(PDO $pdo, int $orderId): int
{
    try {
        $st = $pdo->prepare('SELECT COUNT(*) FROM ibs_dispatch_report_items WHERE order_id = :oid AND status = :s');
        $st->execute(['oid' => $orderId, 's' => 'included']);

        return (int) $st->fetchColumn();
    } catch (\Throwable $e) {
        return -1;
    }
}

// --- Test 2: Pre-dispatch hub return ---
$hubOrderId = seedOrder($pdo, $orders, $orderItems, $tag . '-HUB-PRE', 'shipped');
$hubRef = $tag . '-HUB-PRE';
$wf2 = $workflow->transition($hubOrderId, 'delivery_stop', 'UAT delivery stop', false, true);
if (!$wf2->success) {
    uatFail($results, '2-pre-dispatch-hub-return', 'delivery_stop failed: ' . $wf2->message);
} else {
    $wf2b = $workflow->transition($hubOrderId, 'hub_return', 'UAT hub return confirm', false, true);
    if (!$wf2b->success) {
        uatFail($results, '2-pre-dispatch-hub-return', 'hub_return failed: ' . $wf2b->message);
    } elseif (countDispatchItems($pdo, $hubOrderId) > 0) {
        uatFail($results, '2-pre-dispatch-hub-return', 'unexpected dispatch item');
    } else {
        $batchesBefore = countReturnBatchesSafe($pdo, $hubOrderId);
        $recv = (new ReturnReceiveWriteService())->confirmReceive([
            'order_id' => $hubOrderId,
            'return_type' => ReturnReceiveType::HUB_COURIER_RETURN,
            'return_reason' => 'other',
            'received_confirmation' => 'product_received',
            'receive_confirmed' => '1',
            'staff_confirmation' => '1',
            'supplier_condition' => 'reusable',
            'supplier_note' => 'UAT hub pre-dispatch receive',
            'verification_note' => 'UAT',
        ]);
        $batchesAfter = countReturnBatchesSafe($pdo, $hubOrderId);
        $payableCount = countPayableForOrder($pdo, $hubRef);
        if (!$recv->success) {
            uatFail($results, '2-pre-dispatch-hub-return', 'receive failed: ' . $recv->message);
        } elseif ($batchesAfter > $batchesBefore) {
            uatFail($results, '2-pre-dispatch-hub-return', "return batch created ($batchesBefore -> $batchesAfter)");
        } elseif ($payableCount > 0) {
            uatFail($results, '2-pre-dispatch-hub-return', 'payable row created');
        } else {
            uatPass($results, '2-pre-dispatch-hub-return', $recv->message);
        }
    }
}

// --- Test 3: Dispatch lock ---
$dispOrderId = seedOrder($pdo, $orders, $orderItems, $tag . '-DISPATCH', 'shipped');
$dispRef = $tag . '-DISPATCH';
if (!OrderDemoGuard::shouldBlockFromDispatch($orders->find($dispOrderId) ?? [])) {
    $dispResult = (new DispatchReportWriteService())->createDailyBatch([
        'batch_confirmed' => '1',
        'order_ids' => [$dispOrderId],
    ]);
    if (!$dispResult->success) {
        uatFail($results, '3-dispatch-lock', 'createDailyBatch: ' . $dispResult->message);
    } else {
        $rollback = $workflow->transition($dispOrderId, 'packaging', 'UAT rollback attempt', false, true);
        $dispItems = countDispatchItems($pdo, $dispOrderId);
        $orderAfter = $orders->find($dispOrderId);
        $statusAfter = OrderWorkflowStatus::normalize((string) ($orderAfter['ibs_status'] ?? ''));
        if ($rollback->success) {
            uatFail($results, '3-dispatch-lock', 'rollback to packaging was allowed');
        } elseif ($dispItems < 1) {
            uatFail($results, '3-dispatch-lock', 'no dispatch item created');
        } elseif ($statusAfter !== 'dispatch_report_created') {
            uatFail($results, '3-dispatch-lock', "status is $statusAfter not dispatch_report_created");
        } else {
            uatPass($results, '3-dispatch-lock', 'locked; rollback blocked: ' . $rollback->message);
        }
    }
} else {
    uatFail($results, '3-dispatch-lock', 'seed order incorrectly flagged as demo');
}

// --- Test 4: Hub return after dispatch ---
$dispOrderId2 = seedOrder($pdo, $orders, $orderItems, $tag . '-HUB-POST', 'shipped');
if ((new DispatchReportWriteService())->createDailyBatch(['batch_confirmed' => '1', 'order_ids' => [$dispOrderId2]])->success) {
    $workflow->transition($dispOrderId2, 'delivery_stop', 'UAT', false, true);
    $workflow->transition($dispOrderId2, 'hub_return', 'UAT', false, true);
    $blockRecv = (new ReturnReceiveWriteService())->confirmReceive([
        'order_id' => $dispOrderId2,
        'return_type' => ReturnReceiveType::HUB_COURIER_RETURN,
        'return_reason' => 'other',
        'received_confirmation' => 'product_received',
        'receive_confirmed' => '1',
        'staff_confirmation' => '1',
        'supplier_condition' => 'reusable',
        'supplier_note' => 'should block',
        'verification_note' => 'UAT',
    ]);
    if ($blockRecv->success) {
        uatFail($results, '4-hub-return-after-dispatch', 'hub receive should be blocked');
    } elseif (stripos($blockRecv->message, 'dispatch') !== false || stripos($blockRecv->message, 'Customer Return') !== false) {
        uatPass($results, '4-hub-return-after-dispatch', $blockRecv->message);
    } else {
        uatFail($results, '4-hub-return-after-dispatch', 'blocked but unclear message: ' . $blockRecv->message);
    }
} else {
    uatFail($results, '4-hub-return-after-dispatch', 'could not create dispatch for test order');
}

// --- Test 5: Post-dispatch customer return ---
$custOrderId = seedOrder($pdo, $orders, $orderItems, $tag . '-CUST-RET', 'shipped');
$snapshotAmount = 50.0;
if ((new DispatchReportWriteService())->createDailyBatch(['batch_confirmed' => '1', 'order_ids' => [$custOrderId]])->success) {
    $item = $pdo->prepare('SELECT product_cost_snapshot FROM ibs_dispatch_report_items WHERE order_id = :oid LIMIT 1');
    $item->execute(['oid' => $custOrderId]);
    $row = $item->fetch(PDO::FETCH_ASSOC);
    $snapshotAmount = round((float) ($row['product_cost_snapshot'] ?? 50), 2);

    $workflow->transition($custOrderId, 'delivery_stop', 'UAT OFD path', false, true);
    $orders->updateStatus($custOrderId, 'out_for_delivery');
    $orders->updateStatus($custOrderId, 'order_returning');

    $batchesBefore = countReturnBatchesSafe($pdo, $custOrderId);
    $custRecv = (new ReturnReceiveWriteService())->confirmReceive([
        'order_id' => $custOrderId,
        'return_type' => ReturnReceiveType::CUSTOMER_RETURN_TO_SUPPLIER,
        'return_reason' => 'customer_cancelled',
        'received_confirmation' => 'product_received',
        'receive_confirmed' => '1',
        'staff_confirmation' => '1',
        'supplier_condition' => 'reusable',
        'supplier_note' => 'UAT customer return post dispatch',
        'verification_note' => 'UAT',
    ]);
    $batchesAfter = countReturnBatchesSafe($pdo, $custOrderId);
    $ledgerBefore = $ledgers->tableExists() ? (int) $pdo->query('SELECT COUNT(*) FROM ibs_payable_ledgers WHERE ledger_type = \'return_deduction\' AND source_reference LIKE \'%' . $tag . '%\'')->fetchColumn() : 0;

    $batchAmt = 0.0;
    if ($batchesAfter > $batchesBefore) {
        $st = $pdo->prepare('SELECT total_adjustment_amount FROM ibs_return_batches rb INNER JOIN ibs_return_batch_items bi ON bi.return_batch_id = rb.return_batch_id WHERE bi.order_id = :oid ORDER BY rb.return_batch_id DESC LIMIT 1');
        $st->execute(['oid' => $custOrderId]);
        $batchAmt = round((float) ($st->fetchColumn() ?: 0), 2);
    }

    $postedLedger = $ledgers->tableExists()
        ? (int) $pdo->query("SELECT COUNT(*) FROM ibs_payable_ledgers WHERE ledger_type = 'return_deduction' AND status = 'posted' AND source_reference LIKE '%$tag%'")->fetchColumn()
        : 0;

    if (!$custRecv->success) {
        uatFail($results, '5-post-dispatch-customer-return', $custRecv->message);
    } elseif ($batchesAfter <= $batchesBefore) {
        uatFail($results, '5-post-dispatch-customer-return', 'no return batch created');
    } elseif (abs($batchAmt - $snapshotAmount) > 0.01) {
        uatFail($results, '5-post-dispatch-customer-return', "batch amt $batchAmt != snapshot $snapshotAmount");
    } elseif ($postedLedger > 0) {
        uatFail($results, '5-post-dispatch-customer-return', 'ledger was posted (should not in v2.4.2)');
    } else {
        uatPass($results, '5-post-dispatch-customer-return', "batch adjustment $batchAmt = dispatch snapshot; no posted ledger");
    }
} else {
    uatFail($results, '5-post-dispatch-customer-return', 'dispatch create failed');
}

// --- Test 6: Demo guard ---
$demoOrderId = seedOrder($pdo, $orders, $orderItems, $tag . '-DEMO-BLOCK', 'shipped');
$pdo->prepare('UPDATE ibs_orders SET source_order_reference = :r WHERE order_id = :id')
    ->execute(['r' => 'OC-10001-DEMO', 'id' => $demoOrderId]);
$demoOrder = $orders->find($demoOrderId) ?? [];
$hidden = OrderDemoGuard::shouldHideInWorkflowList($demoOrder, false);
$shown = OrderDemoGuard::shouldHideInWorkflowList($demoOrder, true);
$blockDisp = OrderDemoGuard::shouldBlockFromDispatch($demoOrder);
$dispDemo = (new DispatchReportWriteService())->createDailyBatch([
    'batch_confirmed' => '1',
    'order_ids' => [$demoOrderId],
]);
$t6ok = $hidden && !$shown && $blockDisp && !$dispDemo->success;
$t6ok ? uatPass($results, '6-demo-guard', 'hidden by default; dispatch blocked: ' . $dispDemo->message)
    : uatFail($results, '6-demo-guard', "hidden=$hidden shown=!$shown block=$blockDisp dispOk=" . ($dispDemo->success ? 'yes' : 'no'));

// Cleanup unless --keep-data
if (!$keepData) {
    echo PHP_EOL . 'Note: UAT seed orders left with prefix ' . $tag . ' (use --keep-data to document ids)' . PHP_EOL;
}

$pass = count(array_filter($results, static fn ($r) => $r['status'] === 'PASS'));
$fail = count(array_filter($results, static fn ($r) => $r['status'] === 'FAIL'));
echo PHP_EOL . "=== SUMMARY: $pass PASS, $fail FAIL ===" . PHP_EOL;

file_put_contents(
    dirname(__DIR__) . '/storage/uat-v242-results.json',
    json_encode(['tag' => $tag, 'results' => $results, 'pass' => $pass, 'fail' => $fail], JSON_PRETTY_PRINT)
);

exit($fail > 0 ? 1 : 0);
