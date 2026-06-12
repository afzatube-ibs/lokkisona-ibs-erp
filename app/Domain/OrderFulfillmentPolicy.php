<?php

namespace App\Domain;

use App\Repositories\DispatchReportRepository;

class OrderFulfillmentPolicy
{
    public static function orderWasDispatched(int $orderId): bool
    {
        if ($orderId <= 0) {
            return false;
        }

        try {
            return (new DispatchReportRepository())->findDispatchItemForOrder($orderId) !== null;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function lockedDispatchItemForOrder(int $orderId): ?array
    {
        if ($orderId <= 0) {
            return null;
        }

        try {
            return (new DispatchReportRepository())->findDispatchItemForOrder($orderId);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * TYPE 2 — post-dispatch customer return creates a Return Report batch.
     * TYPE 1 hub return and warehouse returns are workflow closure only.
     */
    public static function shouldCreateReturnReport(string $returnType, int $orderId): bool
    {
        if ($returnType !== ReturnReceiveType::CUSTOMER_RETURN_TO_SUPPLIER) {
            return false;
        }

        return self::orderWasDispatched($orderId);
    }

    public static function hubReturnBlockedAfterDispatch(int $orderId): bool
    {
        return self::orderWasDispatched($orderId);
    }

    public static function customerReturnRequiresDispatch(int $orderId): bool
    {
        return self::orderWasDispatched($orderId);
    }

    /**
     * Pre-dispatch hub return path — orders in this state cannot enter dispatch.
     *
     * @param array<string, mixed> $order
     */
    public static function isInHubReturnPath(array $order): bool
    {
        $status = OrderWorkflowStatus::normalize((string) ($order['ibs_status'] ?? ''));

        return in_array($status, ['delivery_stop', 'hub_returning', 'hub_return'], true);
    }

    /**
     * Manual / sales orders must never be updated by OpenCart sync.
     *
     * @param array<string, mixed> $order
     */
    public static function isManualSalesOrder(array $order): bool
    {
        $syncSource = strtolower(trim((string) ($order['sync_source'] ?? '')));
        if ($syncSource === 'manual') {
            return true;
        }

        $sourceOrderId = trim((string) ($order['source_order_id'] ?? ''));

        return $sourceOrderId === '' && $syncSource !== 'opencart' && $syncSource !== 'demo' && $syncSource !== 'opencart_demo';
    }
}
