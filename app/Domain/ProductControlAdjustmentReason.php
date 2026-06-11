<?php

namespace App\Domain;

class ProductControlAdjustmentReason
{
    public const FORWARD_TO_WHOLESALE = 'Forward to Wholesale';
    public const MANUAL_CORRECTION = 'Manual Correction';

    /**
     * @return list<string>
     */
    public static function allowed(): array
    {
        return [
            self::FORWARD_TO_WHOLESALE,
            self::MANUAL_CORRECTION,
        ];
    }

    public static function isAllowed(string $reason): bool
    {
        return in_array(trim($reason), self::allowed(), true);
    }

    public static function isAdjustmentType(string $type): bool
    {
        return in_array(strtolower(trim($type)), [
            'fixed_plus',
            'fixed_minus',
            'percent_plus',
            'percent_minus',
            'increase',
            'decrease',
        ], true);
    }

    /**
     * @param array{type: string, amount: float|int, note: string}|null $meta
     */
    public static function validateMeta(?array $meta): ?string
    {
        if ($meta === null) {
            return null;
        }

        $type = (string) ($meta['type'] ?? '');
        if (!self::isAdjustmentType($type)) {
            return null;
        }

        $note = trim((string) ($meta['note'] ?? ''));
        if ($note === '') {
            return 'Adjustment reason is required.';
        }

        if (!self::isAllowed($note)) {
            return 'Invalid adjustment reason. Choose Forward to Wholesale or Manual Correction.';
        }

        return null;
    }
}
