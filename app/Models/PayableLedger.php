<?php
namespace App\Models;

class PayableLedger
{
    const TABLE = 'payable_ledgers';

    public static array $columns = [
        'ledger_id',
        'supplier_id',
        'entry_type',
        'amount',
        'currency_code',
        'reference_type',
        'reference_id',
        'entry_date',
        'notes',
        'created_by',
        'created_at',
        'updated_at',
    ];
}
