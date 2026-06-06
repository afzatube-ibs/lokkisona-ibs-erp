<?php

namespace App\Services\Write;

use App\ActivityLog;
use App\ReadFoundation\WriteGate;
use App\Repositories\Write\PrintLogWriteRepository;

class PrintLogWriteService
{
    private PrintLogWriteRepository $logs;

    public function __construct(?PrintLogWriteRepository $logs = null)
    {
        $this->logs = $logs ?? new PrintLogWriteRepository();
    }

    public function record(array $input): WriteResult
    {
        if (!WriteGate::invoicePrinting()['ready']) {
            return WriteResult::fail(WriteGate::WARNING_MESSAGE);
        }

        $printableType = trim((string) ($input['printable_type'] ?? 'invoice'));
        $printableId = ($input['printable_id'] ?? '') !== '' ? (int) $input['printable_id'] : null;
        $action = trim((string) ($input['action'] ?? 'print'));
        if ($action === '') {
            return WriteResult::fail('Print action is required.');
        }

        $reference = trim((string) ($input['print_reference'] ?? ''));
        if ($reference === '') {
            $reference = 'PL-' . date('YmdHis') . '-' . random_int(100, 999);
        }

        $context = [
            'invoice_type' => $input['invoice_type'] ?? null,
            'template_key' => $input['template_key'] ?? 'lokkisona_default',
            'route_note' => $input['route_note'] ?? null,
        ];

        $id = $this->logs->append([
            'print_reference' => $reference,
            'printable_type' => $printableType,
            'printable_id' => $printableId,
            'action' => $action,
            'user_id' => null,
            'route_path' => (string) ($input['route_path'] ?? '/invoice-printing'),
            'context_json' => json_encode($context, JSON_UNESCAPED_UNICODE),
        ]);

        ActivityLog::record('print_log_created', 'Print/download action logged', [
            'print_log_id' => $id,
            'printable_type' => $printableType,
            'printable_id' => $printableId,
            'action' => $action,
        ]);

        return WriteResult::ok('Print log recorded.', $id);
    }
}
