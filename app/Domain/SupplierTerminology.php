<?php

namespace App\Domain;

/**
 * Supplier-facing labels — sale amount is what Lokkisona owes the supplier (not owner "product cost" wording).
 */
class SupplierTerminology
{
    public static function salesMtd(): string
    {
        return 'Sales (MTD)';
    }

    public static function saleAmount(): string
    {
        return 'Sale Amount';
    }

    public static function totalSaleSnapshot(): string
    {
        return 'Total Sale (snapshot)';
    }

    public static function lineSaleSnapshot(): string
    {
        return 'Line Sale (snapshot)';
    }

    public static function salesTrend(): string
    {
        return 'Sales';
    }

    public static function paymentsTrend(): string
    {
        return 'Payments';
    }

    public static function ledgerTrendFootnote(): string
    {
        return 'Posted sales vs payments received — last 6 months';
    }

    public static function dailyDispatchStatement(): string
    {
        return 'Daily Dispatch Statement';
    }

    public static function totalCostSnapshot(): string
    {
        return 'Total Cost Snapshot';
    }

    public static function unitCostSnapshot(): string
    {
        return 'Unit Cost';
    }

    public static function lineCostSnapshot(): string
    {
        return 'Line Cost';
    }

    public static function costSnapshotLabel(bool $supplierView): string
    {
        return $supplierView ? self::totalSaleSnapshot() : self::totalCostSnapshot();
    }

    public static function unitSnapshotLabel(bool $supplierView): string
    {
        return $supplierView ? 'Unit Sale' : self::unitCostSnapshot();
    }

    public static function lineSnapshotLabel(bool $supplierView): string
    {
        return $supplierView ? self::lineSaleSnapshot() : self::lineCostSnapshot();
    }

    public static function dispatchRateLabel(): string
    {
        return 'Rate';
    }

    public static function dispatchLineTotalLabel(): string
    {
        return 'Line Total';
    }

    public static function totalDispatchedAmount(): string
    {
        return 'Total Dispatched Amount';
    }
}
