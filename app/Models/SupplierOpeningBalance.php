<?php

namespace App\Models;

class SupplierOpeningBalance extends BaseModel
{
    const TABLE = 'supplier_opening_balances';

    const PRIMARY_KEY = 'supplier_opening_balance_id';

    public static array $columns = [
        'supplier_opening_balance_id',
        'supplier_id',
        'business_source_id',
        'applies_to_all_sources',
        'balance_type',
        'amount',
        'currency_code',
        'cutoff_date',
        'calculation_summary',
        'reference_note',
        'proof_file_path',
        'proof_file_name',
        'owner_approval_status',
        'owner_approved_by',
        'owner_approved_at',
        'entered_by',
        'entered_at',
        'locked_after_launch',
        'status',
        'created_at',
        'updated_at',
    ];
}
