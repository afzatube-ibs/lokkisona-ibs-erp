<?php

namespace App\Models;

class SupplierOpeningBalanceAudit extends BaseModel
{
    const TABLE = 'supplier_opening_balance_audits';

    const PRIMARY_KEY = 'audit_id';

    public static array $columns = [
        'audit_id',
        'supplier_opening_balance_id',
        'action',
        'changed_by',
        'changed_at',
        'notes',
        'created_at',
    ];
}
