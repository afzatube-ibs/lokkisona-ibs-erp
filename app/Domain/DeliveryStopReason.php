<?php

namespace App\Domain;

class DeliveryStopReason
{
    public const CUSTOMER_CANCELLED = 'customer_cancelled';

    public const CUSTOMER_REFUSED = 'customer_refused';

    public const WRONG_ADDRESS = 'wrong_address';

    public const COURIER_ISSUE = 'courier_issue';

    public const OTHER = 'other';

    private const LABELS = [
        self::CUSTOMER_CANCELLED => 'Customer Cancelled',
        self::CUSTOMER_REFUSED => 'Customer Refused Delivery',
        self::WRONG_ADDRESS => 'Wrong Address / Not Reachable',
        self::COURIER_ISSUE => 'Courier Issue',
        self::OTHER => 'Other',
    ];

    public static function all(): array
    {
        return [
            self::CUSTOMER_CANCELLED,
            self::CUSTOMER_REFUSED,
            self::WRONG_ADDRESS,
            self::COURIER_ISSUE,
            self::OTHER,
        ];
    }

    public static function isKnown(string $code): bool
    {
        return isset(self::LABELS[self::normalize($code)]);
    }

    public static function normalize(string $code): string
    {
        return strtolower(trim($code));
    }

    public static function label(string $code): string
    {
        $normalized = self::normalize($code);

        return self::LABELS[$normalized] ?? $normalized;
    }

    /**
     * @return array<int, array{code: string, label: string}>
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::all() as $code) {
            $options[] = ['code' => $code, 'label' => self::label($code)];
        }

        return $options;
    }

    public static function formatActionNote(string $reasonCode, ?string $extraNote = null): string
    {
        $label = self::label($reasonCode);
        $note = 'Delivery Stop: ' . $label;
        $extra = trim((string) $extraNote);
        if ($extra !== '') {
            $note .= ' | ' . $extra;
        }

        return $note;
    }
}
