<?php

namespace App\Domain;

/**
 * Post-dispatch courier stages — forward-only promotion via sync mapping (never backward).
 */
class OrderCourierWorkflowLane
{
    /** @var array<int, string> */
    private const COURIER_FORWARD_SEQUENCE = [
        'dispatch_report_created',
        'in_review',
        'in_transit',
        'out_for_delivery',
        'delivered',
    ];

    public static function isCourierForwardStage(string $status): bool
    {
        $normalized = OrderWorkflowStatus::normalize(trim($status));

        return in_array($normalized, self::COURIER_FORWARD_SEQUENCE, true)
            || $normalized === 'order_returning';
    }

    /**
     * Resolve a forward-only ibs_status from sync mapping for dispatched orders.
     */
    public static function forwardPromotionTarget(string $currentStatus, string $mappedTarget): ?string
    {
        $current = OrderWorkflowStatus::normalize(trim($currentStatus));
        $mapped = OrderWorkflowStatus::normalize(trim($mappedTarget));
        if ($mapped === '') {
            return null;
        }

        if ($mapped === 'order_returning') {
            return $current === 'out_for_delivery' ? 'order_returning' : null;
        }

        $currentIndex = self::stageIndex($current);
        $mappedIndex = self::stageIndex($mapped);
        if ($currentIndex === null || $mappedIndex === null) {
            return null;
        }

        if ($mappedIndex > $currentIndex) {
            return $mapped;
        }

        return null;
    }

    private static function stageIndex(string $status): ?int
    {
        $index = array_search($status, self::COURIER_FORWARD_SEQUENCE, true);

        return $index === false ? null : (int) $index;
    }
}
