<?php

namespace App\Services\ReadOnly;

use App\Services\Read\OpenCartReadClient;
use App\Services\Write\SyncPreviewWriteService;

/**
 * Test Sync preview read facade (v0.5.7).
 */
class TestSyncPreviewService
{
    public function preview(): array
    {
        $client = new OpenCartReadClient();
        $connection = $client->connectionStatus();
        $sourceId = (int) config('opencart.business_source_id', 1);
        $latest = (new SyncPreviewWriteService())->latestPreviewData($sourceId);

        $counts = $latest['counts'] !== []
            ? $latest['counts']
            : [
                'eligible_supplier_orders' => 0,
                'blocked_unmapped' => 0,
                'skipped_missing_status' => 0,
                'return_candidates' => 0,
                'duplicate_existing' => 0,
            ];

        $sampleRows = [];
        foreach (array_slice($latest['items'] ?? [], 0, 20) as $item) {
            $sampleRows[] = [
                'source_order_reference' => (string) ($item['source_order_reference'] ?? ''),
                'source_status' => (string) ($item['source_status'] ?? ''),
                'mapped_status' => (string) ($item['mapped_status'] ?? ''),
                'preview_status' => (string) ($item['preview_status'] ?? ''),
                'customer_name' => (string) ($item['customer_name'] ?? ''),
                'order_total' => number_format((float) ($item['order_total'] ?? 0), 2),
            ];
        }

        return [
            'source' => 'Lokkisona.com (OpenCart / PIT)',
            'rules' => [
                'Supplier-handled orders only',
                'Skip Missing / order_status_id = 0',
                'Max 50 orders per request',
                'Unmapped statuses blocked',
                'No background loops',
            ],
            'preview_counts' => $counts,
            'sample_rows' => $sampleRows,
            'latest_preview' => $latest['preview'],
            'status' => $connection['status'],
            'message' => $connection['message'],
            'mode' => $connection['mode'],
        ];
    }
}
