<?php

namespace App\Domain;

class PayableLedgerType
{
    public const OPENING_BALANCE = 'opening_balance';
    public const PRODUCT_COST_PAYABLE = 'product_cost_payable';
    public const SUPPLIER_INVOICE = 'supplier_invoice';
    public const ADDITIONAL_PAYABLE = 'additional_payable';
    public const RETURN_DEDUCTION = 'return_deduction';
    public const PAYMENT_MADE = 'payment_made';
    public const ADVANCE_RECEIVED = 'advance_received';
    public const DEBIT_ADJUSTMENT = 'debit_adjustment';
    public const CREDIT_ADJUSTMENT = 'credit_adjustment';

    public static function labels(): array
    {
        return [
            self::OPENING_BALANCE => 'Opening Balance',
            self::PRODUCT_COST_PAYABLE => 'Product Cost Payable',
            self::SUPPLIER_INVOICE => 'Supplier Invoice',
            self::ADDITIONAL_PAYABLE => 'Additional Payable',
            self::RETURN_DEDUCTION => 'Return / Damage Deduction',
            self::PAYMENT_MADE => 'Payment Made to Supplier',
            self::ADVANCE_RECEIVED => 'Advance Received from Supplier',
            self::DEBIT_ADJUSTMENT => 'Debit Adjustment',
            self::CREDIT_ADJUSTMENT => 'Credit Adjustment',
        ];
    }

    public static function label(string $type): string
    {
        return self::labels()[$type] ?? ucwords(str_replace('_', ' ', $type));
    }

    public static function manualEntryTypes(): array
    {
        return [
            self::SUPPLIER_INVOICE,
            self::ADDITIONAL_PAYABLE,
            self::PAYMENT_MADE,
            self::ADVANCE_RECEIVED,
            self::DEBIT_ADJUSTMENT,
            self::CREDIT_ADJUSTMENT,
            self::RETURN_DEDUCTION,
        ];
    }

    public static function isDebitType(string $type): bool
    {
        return in_array($type, [
            self::OPENING_BALANCE,
            self::PRODUCT_COST_PAYABLE,
            self::SUPPLIER_INVOICE,
            self::ADDITIONAL_PAYABLE,
            self::DEBIT_ADJUSTMENT,
        ], true);
    }

    public static function isCreditType(string $type): bool
    {
        return in_array($type, [
            self::RETURN_DEDUCTION,
            self::PAYMENT_MADE,
            self::ADVANCE_RECEIVED,
            self::CREDIT_ADJUSTMENT,
        ], true);
    }

    public static function descriptionFor(string $type, ?string $sourceReference = null, ?string $note = null): string
    {
        $label = self::label($type);
        $parts = [$label];
        if ($sourceReference !== null && $sourceReference !== '') {
            $parts[] = 'Ref: ' . $sourceReference;
        }
        if ($note !== null && $note !== '') {
            $parts[] = $note;
        }

        return implode(' — ', $parts);
    }
}
