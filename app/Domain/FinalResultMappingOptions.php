<?php

namespace App\Domain;

/**
 * v2.5.0 — OpenCart final-result mapping after IBS Dispatched.
 */
class FinalResultMappingOptions
{
    public const TARGET_DELIVERED = 'delivered';
    public const TARGET_RETURNED = 'order_returning';

    /**
     * @return array<int, array{code: string, label: string}>
     */
    public static function targetOptions(): array
    {
        return [
            ['code' => self::TARGET_DELIVERED, 'label' => 'Delivered'],
            ['code' => self::TARGET_RETURNED, 'label' => 'Returned'],
        ];
    }

    public static function isValidTarget(string $target): bool
    {
        return in_array(OrderWorkflowStatus::normalize(trim($target)), [
            self::TARGET_DELIVERED,
            self::TARGET_RETURNED,
        ], true);
    }

    public static function targetLabel(string $target): string
    {
        return match (OrderWorkflowStatus::normalize(trim($target))) {
            self::TARGET_DELIVERED => 'Delivered',
            self::TARGET_RETURNED => 'Returned',
            default => '—',
        };
    }
}
