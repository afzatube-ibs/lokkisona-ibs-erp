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
}
