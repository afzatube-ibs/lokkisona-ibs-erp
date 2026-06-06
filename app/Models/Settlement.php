<?php

namespace App\Models;

class Settlement extends BaseModel
{
    const TABLE = 'settlements';

    const PRIMARY_KEY = 'settlement_id';

    public static array $columns = [
        'settlement_id',
        'supplier_id',
        'settlement_reference',
        'period_type',
        'period_start',
        'period_end',
        'opening_balance',
        'dispatch_payable',
        'invoice_total',
        'deductions',
        'payments',
        'advances',
        'adjustments',
        'closing_balance',
        'workflow_status',
        'prepared_by',
        'prepared_at',
        'approved_by',
        'approved_at',
        'paid_at',
        'closed_at',
        'notes',
        'created_at',
        'updated_at',
    ];
}
