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
}
