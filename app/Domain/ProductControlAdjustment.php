<?php

namespace App\Domain;

class ProductControlAdjustment
{
    public static function applyCost(?float $current, string $type, float $amount): ?float
    {
        $current = $current ?? 0.0;
        $type = strtolower(trim($type));
        $amount = round($amount, 2);

        $result = match ($type) {
            'fixed_plus' => $current + $amount,
            'fixed_minus' => $current - $amount,
            'percent_plus' => $current * (1 + ($amount / 100)),
            'percent_minus' => $current * (1 - ($amount / 100)),
            default => $amount,
        };

        return round(max(0, $result), 2);
    }

    public static function applyStock(int $current, string $type, int $amount): int
    {
        $type = strtolower(trim($type));
        $amount = max(0, $amount);

        $result = match ($type) {
            'increase' => $current + $amount,
            'decrease' => $current - $amount,
            default => $amount,
        };

        return max(0, $result);
    }

    public static function costDelta(?float $old, ?float $new): float
    {
        return round((float) ($new ?? 0) - (float) ($old ?? 0), 2);
    }

    public static function stockDelta(int $old, int $new): int
    {
        return $new - $old;
    }
}
