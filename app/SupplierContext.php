<?php

namespace App;

class SupplierContext
{
    public static function isSupplier(): bool
    {
        return Auth::role() === 'supplier';
    }

    public static function supplierId(): int
    {
        if (!self::isSupplier()) {
            return 0;
        }

        Auth::startSession();
        $sessionId = (int) ($_SESSION['ibs_supplier_id'] ?? 0);
        if ($sessionId > 0) {
            return $sessionId;
        }

        return (int) config('app.auth.supplier_id', 1);
    }

    public static function enforceSupplierId(?int $requested = null): int
    {
        if (self::isSupplier()) {
            return self::supplierId();
        }

        return max(0, (int) ($requested ?? 0));
    }

    public static function canSelectSupplier(): bool
    {
        return !self::isSupplier();
    }

    public static function bindSupplierIdOnLogin(): void
    {
        if (!self::isSupplier()) {
            return;
        }

        Auth::startSession();
        $_SESSION['ibs_supplier_id'] = (int) config('app.auth.supplier_id', 1);
    }
}
