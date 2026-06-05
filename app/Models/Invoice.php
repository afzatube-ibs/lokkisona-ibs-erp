<?php
namespace App\Models;

class Invoice
{
    const TABLE = 'invoices';

    public static array $columns = [
        'invoice_id',
        'order_id',
        'source_id',
        'invoice_number',
        'invoice_date',
        'total_amount',
        'currency_code',
        'status',
        'printed_at',
        'created_at',
        'updated_at',
    ];
}
