<?php

namespace App\Domain;

/**
 * Order sync mapping safety rules (v1.9.6).
 * Mapping seeds initial IBS status at import only — never overwrites workflow afterward.
 * Order import eligibility depends on status mapping only — not product/cost/stock mapping.
 */
class OrderSyncMappingRules
{
    /** Default allowed IBS initial fulfillment statuses for OpenCart import mapping. */
    public const DEFAULT_INITIAL_STATUSES = [
        'new_order',
        'order_received',
        'packaging',
        'shipped',
        'hold',
        'cancelled',
    ];

    /** Advanced-only targets (owner/admin explicit enable). */
    public const ADVANCED_INITIAL_STATUSES = [
        'out_for_delivery',
        'delivered',
        'delivery_stop',
        'hub_return',
        'order_returning',
    ];

    public static function normalizeIbsStatus(string $code): string
    {
        return OrderWorkflowStatus::normalize(trim($code));
    }

    public static function isAllowedInitialStatus(string $code, bool $advancedMode = false): bool
    {
        $normalized = self::normalizeIbsStatus($code);
        if ($normalized === '') {
            return false;
        }

        if (OrderSyncWorkflowBoundary::isBeyondShipmentCeiling($normalized)) {
            return false;
        }

        if (in_array($normalized, self::DEFAULT_INITIAL_STATUSES, true)) {
            return true;
        }

        return $advancedMode && in_array($normalized, self::ADVANCED_INITIAL_STATUSES, true);
    }

    /**
     * @return array<int, array{code: string, label: string}>
     */
    public static function initialStatusOptions(bool $advancedMode = false): array
    {
        $options = [];
        foreach (self::DEFAULT_INITIAL_STATUSES as $code) {
            $options[] = ['code' => $code, 'label' => OrderWorkflowStatus::label($code)];
        }

        if ($advancedMode) {
            foreach (self::ADVANCED_INITIAL_STATUSES as $code) {
                $options[] = ['code' => $code, 'label' => OrderWorkflowStatus::label($code)];
            }
        }

        return $options;
    }

    public static function advancedModeEnabled(): bool
    {
        return (bool) config('opencart.order_sync_advanced_mapping', false);
    }

    public static function validationMessageForStatus(string $code): ?string
    {
        if (self::isAllowedInitialStatus($code, self::advancedModeEnabled())) {
            return null;
        }

        $normalized = self::normalizeIbsStatus($code);
        if (in_array($normalized, self::ADVANCED_INITIAL_STATUSES, true)) {
            return 'That IBS status requires advanced mapping mode. Enable it in Sync/API Settings or map to New Order.';
        }

        return 'IBS initial status is not allowed for OpenCart import mapping.';
    }

    /**
     * Snapshot fields safe to refresh on re-sync (never includes ibs_status).
     *
     * @return array<int, string>
     */
    public static function snapshotFieldKeys(): array
    {
        return [
            'origin_order_status_id',
            'origin_order_status_name',
            'courier_status',
            'tracking_number',
            'customer_name',
            'customer_phone',
            'customer_address',
            'last_synced_at',
        ];
    }
}
