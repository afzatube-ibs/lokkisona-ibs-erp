<?php

namespace App\Models;

class SyncPreviewItem extends BaseModel
{
    const TABLE = 'sync_preview_items';

    const PRIMARY_KEY = 'sync_preview_item_id';

    public static array $columns = [
        'sync_preview_item_id',
        'sync_preview_id',
        'source_order_id',
        'source_order_reference',
        'source_invoice_reference',
        'source_status',
        'mapped_status',
        'customer_name',
        'order_total',
        'item_count',
        'preview_status',
        'issue_summary',
        'created_at',
    ];
}
