<?php

namespace App\Domain;

class ReturnReportReference
{
    public const STATUS_LOCKED = 'locked';

    public const LEDGER_HOOK_NOTE = 'Return Amount = locked dispatch-era cost snapshot (ibs_return_report_items.product_cost_snapshot). v2.5 Supplier Ledger return_deduction must use this locked amount — not current product catalog cost. No ledger posting in v2.4.x.';

    public static function statusLabel(string $status): string
    {
        $normalized = strtolower(trim($status));

        if ($normalized === self::STATUS_LOCKED) {
            return 'Created / Locked';
        }

        return $normalized !== '' ? $status : '—';
    }

    public static function returnDate(?\DateTimeInterface $date = null): string
    {
        $timezone = new \DateTimeZone((string) config('app.timezone', 'UTC'));
        $date = $date ?? new \DateTimeImmutable('now', $timezone);

        return $date->format('Y-m-d');
    }

    public static function baseForDate(?\DateTimeInterface $date = null): string
    {
        $timezone = new \DateTimeZone((string) config('app.timezone', 'UTC'));
        $date = $date ?? new \DateTimeImmutable('now', $timezone);

        return 'RR-' . $date->format('dmY');
    }

    /**
     * @param array<int, string> $existingReferences
     */
    public static function nextForToday(array $existingReferences): string
    {
        $base = self::baseForDate();
        $existing = array_flip($existingReferences);

        if (!isset($existing[$base])) {
            return $base;
        }

        $part = 1;
        while (isset($existing[$base . '-P' . $part])) {
            $part++;
        }

        return $base . '-P' . $part;
    }
}
