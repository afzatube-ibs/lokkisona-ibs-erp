<?php

namespace App\Domain;

class ReturnReceiveReason
{
    public const FAULTY_PRODUCT = 'faulty_product';

    public const CUSTOMER_CANCELLED = 'customer_cancelled';

    public const DOUBLE_ORDER = 'double_order';

    public const OTHER = 'other';

    private const DESCRIPTIONS = [
        self::FAULTY_PRODUCT => 'Product fault reported by customer or courier.',
        self::CUSTOMER_CANCELLED => 'Customer cancelled before or after dispatch.',
        self::DOUBLE_ORDER => 'Duplicate order or extra parcel returned.',
        self::OTHER => 'Other owner-side return reason.',
    ];

    private const LABELS = [
        self::FAULTY_PRODUCT => 'Faulty Product',
        self::CUSTOMER_CANCELLED => 'Customer Cancelled',
        self::DOUBLE_ORDER => 'Double Order',
        self::OTHER => 'Other',
    ];

    public static function all(): array
    {
        return [
            self::FAULTY_PRODUCT,
            self::CUSTOMER_CANCELLED,
            self::DOUBLE_ORDER,
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
    public static function description(string $code): string
    {
        $normalized = self::normalize($code);

        return self::DESCRIPTIONS[$normalized] ?? '';
    }

    public static function options(): array
    {
        $options = [];
        foreach (self::all() as $code) {
            $options[] = [
                'code' => $code,
                'label' => self::label($code),
                'description' => self::description($code),
            ];
        }

        return $options;
    }
}
