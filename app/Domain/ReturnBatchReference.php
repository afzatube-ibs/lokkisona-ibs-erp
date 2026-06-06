<?php

namespace App\Domain;

class ReturnBatchReference
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_OWNER_APPROVED = 'owner_approved';

    public const DEDUCTION_GATE_NOTE = 'Owner-approved return batch becomes eligible for a Return / Damage Deduction draft on Supplier Payables. Deduction is never automatic — it still requires a separate owner approval there.';

    public static function statusLabel(string $status): string
    {
        $normalized = strtolower(trim($status));

        if ($normalized === self::STATUS_OWNER_APPROVED) {
            return 'Owner Approved';
        }

        if ($normalized === self::STATUS_DRAFT) {
            return 'Draft';
        }

        return $normalized !== '' ? $status : '—';
    }

    public static function baseForDate(?\DateTimeInterface $date = null): string
    {
        $timezone = new \DateTimeZone((string) config('app.timezone', 'UTC'));
        $date = $date ?? new \DateTimeImmutable('now', $timezone);

        return 'R' . $date->format('dmY');
    }

    /**
     * @param array<int, string> $existingReferences References already used today
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
