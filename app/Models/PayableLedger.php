<?php

namespace App\Models;

class PayableLedger extends BaseModel
{
    const TABLE = 'payable_ledgers';

    const PRIMARY_KEY = 'payable_ledger_id';

    public static array $columns = [
        'payable_ledger_id',
        'supplier_id',
        'ledger_reference',
        'ledger_type',
        'source_reference',
        'debit_amount',
        'credit_amount',
        'balance_after',
        'status',
        'created_by',
        'created_at',
    ];
}
