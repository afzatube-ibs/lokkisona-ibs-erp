<?php

namespace App\Services\Write;

use App\ActivityLog;
use App\Auth;
use App\Database\Connection;
use App\Database\QueryGuard;
use App\Database\TableName;
use App\Domain\OrderDemoGuard;
use App\Domain\OrderFulfillmentPolicy;
use App\Models\Order;
use App\Models\OrderItem;
use App\Permission;
use App\Repositories\Write\OrderWriteRepository;
use App\Repositories\Write\StatusMappingWriteRepository;
use PDO;

/**
 * ERP-only sync hub reset actions (v2.5.0). No OpenCart writes.
 */
class SyncHubResetWriteService
{
    public function clearProductPreviewSession(array $input = []): WriteResult
    {
        if (!$this->confirmed($input)) {
            return WriteResult::fail('Confirmation checkbox is required.');
        }
        if (!$this->canReset()) {
            return WriteResult::fail('Sync Hub permission required.');
        }

        (new ProductSyncResetWriteService())->clearProductSyncSession();
        ActivityLog::record('sync_hub_reset', 'Product preview session cleared');

        return WriteResult::ok('Product preview session cleared.');
    }

    public function clearOrderPreviewSession(array $input = []): WriteResult
    {
        if (!$this->confirmed($input)) {
            return WriteResult::fail('Confirmation checkbox is required.');
        }
        if (!$this->canReset()) {
            return WriteResult::fail('Sync Hub permission required.');
        }

        unset($_SESSION['ibs_order_sync_preview']);
        ActivityLog::record('sync_hub_reset', 'Order preview session cleared');

        return WriteResult::ok('Order preview session cleared.');
    }

    public function resetProductData(array $input = []): WriteResult
    {
        if (!$this->canReset()) {
            return WriteResult::fail('Sync Hub permission required.');
        }

        return (new ProductSyncResetWriteService())->reset($input);
    }

    public function clearEntryMappings(array $input = []): WriteResult
    {
        if (!$this->confirmed($input)) {
            return WriteResult::fail('Confirmation checkbox is required.');
        }
        if (!$this->canReset()) {
            return WriteResult::fail('Sync Hub permission required.');
        }

        $sourceId = (int) ($input['business_source_id'] ?? config('opencart.business_source_id', 1));
        $count = (new StatusMappingWriteRepository())->deactivateAllQueueMappings($sourceId);
        ActivityLog::record('sync_hub_reset', 'Entry status mappings cleared', ['count' => $count]);

        return WriteResult::ok('Cleared ' . $count . ' entry mapping' . ($count === 1 ? '' : 's') . '.');
    }

    public function clearFinalResultMappings(array $input = []): WriteResult
    {
        if (!$this->confirmed($input)) {
            return WriteResult::fail('Confirmation checkbox is required.');
        }
        if (!$this->canReset()) {
            return WriteResult::fail('Sync Hub permission required.');
        }

        $sourceId = (int) ($input['business_source_id'] ?? config('opencart.business_source_id', 1));
        $count = (new StatusMappingWriteRepository())->deactivateAllFinalResultMappings($sourceId);
        ActivityLog::record('sync_hub_reset', 'Final result mappings cleared', ['count' => $count]);

        return WriteResult::ok('Cleared final result mapping' . ($count === 1 ? '' : 's') . '.');
    }

    public function cleanDemoOrders(array $input = []): WriteResult
    {
        if (!$this->confirmed($input)) {
            return WriteResult::fail('Confirmation checkbox is required.');
        }
        if (!$this->canReset()) {
            return WriteResult::fail('Sync Hub permission required.');
        }

        $orders = new OrderWriteRepository();
        if (!$orders->tableExists()) {
            return WriteResult::ok('No order table — nothing to clean.');
        }

        $deleted = 0;
        $skipped = 0;
        try {
            $pdo = Connection::pdo();
            $orderTable = TableName::forModel(Order::class);
            $itemTable = TableName::forModel(OrderItem::class);
            $sql = 'SELECT order_id, ibs_status, sync_source, source_order_reference, source_order_id '
                . 'FROM `' . str_replace('`', '``', $orderTable) . '` '
                . "WHERE sync_source IN ('opencart', 'demo', 'opencart_demo') ORDER BY order_id ASC LIMIT 500";
            QueryGuard::assertReadOnly($sql);
            $rows = $pdo->query($sql)?->fetchAll(PDO::FETCH_ASSOC) ?: [];

            foreach ($rows as $row) {
                $orderId = (int) ($row['order_id'] ?? 0);
                if ($orderId <= 0 || !OrderDemoGuard::isDemoSyncedOrder($row)) {
                    continue;
                }
                if (OrderFulfillmentPolicy::orderWasDispatched($orderId)) {
                    $skipped++;
                    continue;
                }

                if ($this->deleteOrderRow($pdo, $orderTable, $itemTable, $orderId)) {
                    $deleted++;
                }
            }
        } catch (\Throwable $e) {
            return WriteResult::fail('Demo order cleanup failed: ' . $e->getMessage());
        }

        ActivityLog::record('sync_hub_reset', 'Demo/test synced orders cleaned', [
            'deleted' => $deleted,
            'skipped_dispatched' => $skipped,
        ]);

        return WriteResult::ok(
            'Demo order cleanup: ' . $deleted . ' removed'
            . ($skipped > 0 ? ', ' . $skipped . ' skipped (already dispatched)' : '') . '.'
        );
    }

    private function deleteOrderRow(PDO $pdo, string $orderTable, string $itemTable, int $orderId): bool
    {
        $pdo->beginTransaction();
        try {
            if ($this->tableExists($itemTable)) {
                $delItems = 'DELETE FROM `' . str_replace('`', '``', $itemTable) . '` WHERE order_id = :order_id';
                $stmt = $pdo->prepare($delItems);
                $stmt->execute(['order_id' => $orderId]);
            }
            $delOrder = 'DELETE FROM `' . str_replace('`', '``', $orderTable) . '` WHERE order_id = :order_id LIMIT 1';
            $stmt = $pdo->prepare($delOrder);
            $stmt->execute(['order_id' => $orderId]);
            $pdo->commit();

            return $stmt->rowCount() > 0;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            return false;
        }
    }

    private function tableExists(string $table): bool
    {
        try {
            $pdo = Connection::pdo();
            $stmt = $pdo->query('SHOW TABLES LIKE ' . $pdo->quote($table));

            return $stmt !== false && $stmt->fetch() !== false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function confirmed(array $input): bool
    {
        return in_array((string) ($input['reset_confirmation'] ?? ''), ['1', 'on', 'yes'], true);
    }

    private function canReset(): bool
    {
        return Permission::canSyncHub() || in_array(Auth::role(), ['owner', 'admin'], true);
    }
}
