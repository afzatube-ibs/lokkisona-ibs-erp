<?php

namespace App\Domain;

class SettlementWorkflowStatus
{
    public const DRAFT = 'draft';
    public const PREPARED = 'prepared';
    public const APPROVED = 'approved';
    public const PAID = 'paid';
    public const CLOSED = 'closed';

    public static function labels(): array
    {
        return [
            self::DRAFT => 'Draft',
            self::PREPARED => 'Prepared',
            self::APPROVED => 'Owner Approved',
            self::PAID => 'Paid',
            self::CLOSED => 'Closed',
        ];
    }

    public static function periodTypes(): array
    {
        return [
            'daily' => 'Daily',
            'weekly' => 'Weekly',
            'first_half' => '1–15',
            'second_half' => '16–30',
            'monthly' => 'Monthly',
            'custom' => 'Custom',
        ];
    }
}
