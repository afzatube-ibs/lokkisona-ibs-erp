<?php

namespace App\Domain;

class ReturnReceiveReference
{
    public const DEV_NOTE = 'Return Receive reads ERP order/workflow data (ibs_orders, ibs_order_items, dispatch snapshot). Confirmation saves against order_id. Reason, condition, and notes are stored in workflow history/activity log in v0.4.6.0. Dedicated return columns will be added in a future return/payable migration.';

    public const ACCOUNTING_NOTE = 'Supplier accounting from returns will be handled in a later Return Batch stage.';

    public const STAGE_NOTE = 'This stage is return receiving confirmation only — no payable, stock, invoice, or sync.';

    public static function forOrder(int $orderId, string $returnType, ?\DateTimeInterface $date = null): string
    {
        $timezone = new \DateTimeZone((string) config('app.timezone', 'UTC'));
        $date = $date ?? new \DateTimeImmutable('now', $timezone);
        $typeCode = ReturnReceiveType::referenceCode($returnType);

        return 'RR-' . $orderId . '-' . $typeCode . '-' . $date->format('dmY');
    }
}
