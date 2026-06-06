<?php

namespace App\Domain;

class ReturnReceivePhysicalConfirmation
{
    public const PRODUCT_RECEIVED = 'product_received';

    public const PRODUCT_NOT_RECEIVED = 'product_not_received';

    private const DESCRIPTIONS = [
        self::PRODUCT_RECEIVED => 'Returned parcel/product matches ERP order details.',
        self::PRODUCT_NOT_RECEIVED => 'Parcel/product missing or does not match order.',
    ];

    private const LABELS = [
        self::PRODUCT_RECEIVED => 'Product received',
        self::PRODUCT_NOT_RECEIVED => 'Product not received / mismatch',
    ];

    public static function all(): array
    {
        return [
            self::PRODUCT_RECEIVED,
            self::PRODUCT_NOT_RECEIVED,
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
