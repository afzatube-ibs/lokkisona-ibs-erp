<?php

namespace App\Domain;

class ReturnReceiveType
{
    public const HUB_COURIER_RETURN = 'hub_courier_return';

    public const CUSTOMER_RETURN_TO_SUPPLIER = 'customer_return_to_supplier';

    public const LOKKISONA_WAREHOUSE_RETURN = 'lokkisona_warehouse_return';

    public const IBS_STATUS_MAP = [
        self::HUB_COURIER_RETURN => 'hub_return',
        self::CUSTOMER_RETURN_TO_SUPPLIER => 'order_returning',
        self::LOKKISONA_WAREHOUSE_RETURN => 'order_returning',
    ];

    private const LABELS = [
        self::HUB_COURIER_RETURN => 'Hub Return / Courier Return',
        self::CUSTOMER_RETURN_TO_SUPPLIER => 'Customer Return to Supplier',
        self::LOKKISONA_WAREHOUSE_RETURN => 'Lokkisona / Owner Warehouse Return',
    ];

    private const DESTINATION = [
        self::HUB_COURIER_RETURN => 'Supplier Return / Vendor Return',
        self::CUSTOMER_RETURN_TO_SUPPLIER => 'Supplier Return / Vendor Return',
        self::LOKKISONA_WAREHOUSE_RETURN => 'Lokkisona / Owner Warehouse Return',
    ];

    public static function all(): array
    {
        return [
            self::HUB_COURIER_RETURN,
            self::CUSTOMER_RETURN_TO_SUPPLIER,
            self::LOKKISONA_WAREHOUSE_RETURN,
        ];
    }

    public static function supplierTypes(): array
    {
        return [
            self::HUB_COURIER_RETURN,
            self::CUSTOMER_RETURN_TO_SUPPLIER,
        ];
    }

    public static function isKnown(string $code): bool
    {
        return isset(self::LABELS[self::normalize($code)]);
    }

    public static function isSupplierReturn(string $code): bool
    {
        return in_array(self::normalize($code), self::supplierTypes(), true);
    }

    public static function isLokkisonaReturn(string $code): bool
    {
        return self::normalize($code) === self::LOKKISONA_WAREHOUSE_RETURN;
    }

    public static function normalize(string $code): string
    {
        return trim($code);
    }

    public static function label(string $code): string
    {
        $normalized = self::normalize($code);

        return self::LABELS[$normalized] ?? $normalized;
    }

    public static function destinationLabel(string $code): string
    {
        $normalized = self::normalize($code);

        return self::DESTINATION[$normalized] ?? $normalized;
    }

    public static function ibsStatusFor(string $code): ?string
    {
        $normalized = self::normalize($code);

        return self::IBS_STATUS_MAP[$normalized] ?? null;
    }

    public static function referenceCode(string $code): string
    {
        return match (self::normalize($code)) {
            self::HUB_COURIER_RETURN => 'H',
            self::CUSTOMER_RETURN_TO_SUPPLIER => 'C',
            self::LOKKISONA_WAREHOUSE_RETURN => 'L',
            default => 'X',
        };
    }
}
