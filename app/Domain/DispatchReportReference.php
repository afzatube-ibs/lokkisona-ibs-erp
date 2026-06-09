<?php

namespace App\Domain;

class DispatchReportReference
{
    public const STATUS_LOCKED = 'locked';

    public const PRODUCT_LINE_DEV_NOTE = 'Product/option-level immutable dispatch lines will be added in future migration.';

    public const PAYABLE_CHECKPOINT_NOTE = 'Dispatch Report is the official supplier payable checkpoint. Supplier payable starts from Dispatch Report, not Delivered. v0.4.5.0 creates cost snapshot foundation only — no payable ledger yet.';

    public static function statusLabel(string $status): string
    {
        $normalized = strtolower(trim($status));

        if ($normalized === self::STATUS_LOCKED) {
            return 'Created / Locked';
        }

        if ($normalized === 'created') {
            return 'Created';
        }

        if ($normalized === 'draft') {
            return 'draft';
        }

        return $normalized !== '' ? $status : '—';
    }

    public static function baseForDate(?\DateTimeInterface $date = null): string
    {
        $timezone = new \DateTimeZone((string) config('app.timezone', 'UTC'));
        $date = $date ?? new \DateTimeImmutable('now', $timezone);

        return $date->format('dmY');
    }

    public static function dispatchDate(?\DateTimeInterface $date = null): string
    {
        $timezone = new \DateTimeZone((string) config('app.timezone', 'UTC'));
        $date = $date ?? new \DateTimeImmutable('now', $timezone);

        return $date->format('Y-m-d');
    }

    /**
     * @param array<int, string> $existingReferences References already used today
     */
    public static function nextForToday(array $existingReferences): string
    {
        $base = self::baseForDate();
        $existing = array_flip($existingReferences);

        $part = 1;
        while (isset($existing[$base . '-P' . $part]) || ($part === 1 && isset($existing[$base]))) {
            $part++;
        }

        return $base . '-P' . $part;
    }
}
