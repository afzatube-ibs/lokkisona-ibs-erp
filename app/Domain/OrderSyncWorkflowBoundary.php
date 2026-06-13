<?php

namespace App\Domain;

/**
 * OpenCart sync stops at Shipped — IBS workflow owns the order afterward.
 */
class OrderSyncWorkflowBoundary
{
    public const SHIPMENT_CEILING = 'shipped';

    /** Statuses that must never be set by OpenCart sync import mapping (default mode). */
    private const POST_SHIPMENT_WORKFLOW_STATUSES = [
        'dispatch_report_created',
        'in_review',
        'in_transit',
        'out_for_delivery',
        'delivered',
        'delivery_stop',
        'hub_returning',
        'hub_return',
        'order_returning',
    ];

    public static function isBeyondShipmentCeiling(string $ibsStatus): bool
    {
        $normalized = OrderWorkflowStatus::normalize(trim($ibsStatus));
        if ($normalized === '') {
            return false;
        }

        if (in_array($normalized, self::POST_SHIPMENT_WORKFLOW_STATUSES, true)) {
            return true;
        }

        $ceilingIndex = array_search(self::SHIPMENT_CEILING, OrderWorkflowStatus::groupOrder(), true);
        $statusIndex = array_search($normalized, OrderWorkflowStatus::groupOrder(), true);
        if ($ceilingIndex === false || $statusIndex === false) {
            return false;
        }

        return $statusIndex > $ceilingIndex;
    }

    public static function isWorkflowOwnedByIbs(string $ibsStatus): bool
    {
        return self::isBeyondShipmentCeiling($ibsStatus);
    }

    public static function syncImportRuleNote(): string
    {
        return 'OpenCart entry mapping imports as NEW only. Supplier progresses Accepted → Packed → Dispatched in IBS. '
            . 'After Dispatched, OpenCart may finalize to Delivered or Returned via Final Result Mapping — forward only.';
    }
}
