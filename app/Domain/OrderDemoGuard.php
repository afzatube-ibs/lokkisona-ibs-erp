<?php

namespace App\Domain;

class OrderDemoGuard
{
    public static function isDemoSyncedOrder(array $order): bool
    {
        $syncSource = strtolower(trim((string) ($order['sync_source'] ?? '')));
        if ($syncSource === 'demo' || $syncSource === 'opencart_demo') {
            return true;
        }

        $sourceRef = strtoupper(trim((string) ($order['source_order_reference'] ?? '')));
        foreach (self::demoSourceReferencePrefixes() as $prefix) {
            if ($prefix !== '' && str_starts_with($sourceRef, strtoupper($prefix))) {
                return true;
            }
        }

        $sourceOrderId = trim((string) ($order['source_order_id'] ?? ''));
        if ($sourceOrderId !== '' && self::isBelowSyncFloor($sourceOrderId)) {
            return true;
        }

        if ((bool) config('opencart.demo_mode', false) && $syncSource === 'opencart') {
            return true;
        }

        return false;
    }

    public static function isBelowSyncFloor(string $sourceOrderId): bool
    {
        $floor = (int) config('opencart.order_sync_min_source_order_id', 0);
        if ($floor <= 0) {
            return false;
        }

        if (!ctype_digit($sourceOrderId)) {
            return false;
        }

        return (int) $sourceOrderId < $floor;
    }

    public static function shouldBlockFromDispatch(array $order): bool
    {
        if (!(bool) config('opencart.block_demo_orders_from_dispatch', true)) {
            return false;
        }

        return self::isDemoSyncedOrder($order);
    }

    public static function shouldHideInWorkflowList(array $order, bool $showDemo): bool
    {
        if ($showDemo) {
            return false;
        }

        if (!(bool) config('opencart.hide_demo_orders_in_workflow', true)) {
            return false;
        }

        return self::isDemoSyncedOrder($order);
    }

    public static function shouldSkipInSyncPreview(array $orderPayload): bool
    {
        if (!(bool) config('opencart.skip_demo_orders_in_sync', true)) {
            return false;
        }

        if ((bool) config('opencart.demo_mode', false)) {
            return false;
        }

        $sourceOrderId = trim((string) ($orderPayload['source_order_id'] ?? ''));
        if ($sourceOrderId !== '' && self::isBelowSyncFloor($sourceOrderId)) {
            return true;
        }

        $sourceRef = strtoupper(trim((string) ($orderPayload['source_order_reference'] ?? '')));
        foreach (self::demoSourceReferencePrefixes() as $prefix) {
            if ($prefix !== '' && str_starts_with($sourceRef, strtoupper($prefix))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    private static function demoSourceReferencePrefixes(): array
    {
        $prefixes = config('opencart.demo_source_order_reference_prefixes', ['OC-1000']);

        return is_array($prefixes) ? array_values(array_filter(array_map('strval', $prefixes))) : ['OC-1000'];
    }
}
