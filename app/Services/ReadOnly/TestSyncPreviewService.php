<?php

namespace App\Services\ReadOnly;

use App\Services\Write\SyncPreviewWriteService;

/**
 * Order sync preview read facade with pagination (v1.7.1).
 */
class TestSyncPreviewService
{    public function preview(int $orderPage = 1, ?array $sessionPreview = null): array
    {
        $client = new \App\Services\Read\OpenCartReadClient();
        $connection = $client->connectionStatus();
        $sourceId = (int) config('opencart.business_source_id', 1);
        $latest = (new SyncPreviewWriteService())->latestPreviewData($sourceId);

        $counts = $latest['counts'] !== []
            ? $latest['counts']
            : [
                'eligible_supplier_orders' => 0,
                'blocked_unmapped' => 0,
                'blocked_not_supplier_handled' => 0,
                'skipped_missing_status' => 0,
                'return_candidates' => 0,
                'duplicate_existing' => 0,
            ];

        $displayRows = [];
        $pagination = [
            'page' => max(1, $orderPage),
            'per_page' => (int) config('opencart.max_rows_per_page', 20),
            'has_previous' => false,
            'has_next' => false,
        ];

        if (is_array($sessionPreview) && (int) ($sessionPreview['page'] ?? 0) === $pagination['page']) {
            $displayRows = $sessionPreview['display_rows'] ?? [];
            $pagination = array_merge($pagination, $sessionPreview['pagination'] ?? []);
            if ($sessionPreview['counts'] ?? []) {
                $counts = $sessionPreview['counts'];
            }
        } elseif ($latest['preview'] !== null) {
            $allItems = $latest['items'] ?? [];
            $offset = ($pagination['page'] - 1) * $pagination['per_page'];
            $slice = array_slice($allItems, $offset, $pagination['per_page']);
            foreach ($slice as $item) {
                $displayRows[] = $this->formatDisplayRow($item);
            }
            $pagination['has_previous'] = $pagination['page'] > 1;
            $pagination['has_next'] = ($offset + $pagination['per_page']) < count($allItems);
        }

        return [
            'source' => 'Lokkisona.com (OpenCart / PIT)',
            'rules' => [
                'Supplier-handled orders only (status mapping workflow group)',
                'Skip Missing / order_status_id = 0',
                'Max 20 orders per preview request',
                'Unmapped statuses blocked — product mapping alone does not import orders',
                'Preview before import — no background loops',
            ],
            'preview_counts' => $counts,
            'display_rows' => $displayRows,
            'pagination' => $pagination,
            'latest_preview' => $latest['preview'],
            'status' => $connection['status'],
            'message' => $connection['message'],
            'mode' => $connection['mode'],
        ];
    }

    private function formatDisplayRow(array $item): array
    {
        $extra = [];
        if (!empty($item['issue_summary']) && str_starts_with((string) $item['issue_summary'], '{')) {
            $decoded = json_decode((string) $item['issue_summary'], true);
            if (is_array($decoded)) {
                $extra = $decoded;
            }
        }

        return array_merge([
            'source_order_id' => (string) ($item['source_order_id'] ?? ''),
            'source_order_reference' => (string) ($item['source_order_reference'] ?? ''),
            'source_status' => (string) ($item['source_status'] ?? ''),
            'mapped_status' => (string) ($item['mapped_status'] ?? ''),
            'preview_status' => (string) ($item['preview_status'] ?? ''),
            'customer_name' => (string) ($item['customer_name'] ?? ''),
            'customer_phone' => (string) ($extra['customer_phone'] ?? ''),
            'order_total' => number_format((float) ($item['order_total'] ?? 0), 2),
            'total_quantity' => (int) ($extra['total_quantity'] ?? $item['item_count'] ?? 0),
            'product_card' => (string) ($extra['product_card'] ?? ''),
            'courier_status' => (string) ($extra['courier_status'] ?? ''),
            'consignment_id' => (string) ($extra['consignment_id'] ?? ''),
            'supplier_handled' => (string) ($extra['supplier_handled'] ?? ''),
            'supplier_handled_reason' => (string) ($extra['supplier_handled_reason'] ?? ''),
            'already_imported' => ($item['preview_status'] ?? '') === 'duplicate_existing' ? 'Yes' : 'No',
        ], $extra);
    }
}
