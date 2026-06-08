<?php



namespace App\Services\ReadOnly;



use App\Services\Write\SyncPreviewWriteService;

use App\Support\OrderSyncPreviewPresenter;



/**

 * Order sync preview read facade with pagination (v1.7.1).

 */

class TestSyncPreviewService

{

    public function preview(int $orderPage = 1, ?array $sessionPreview = null): array

    {

        $client = new \App\Services\Read\OpenCartReadClient();

        $connection = $client->connectionStatus();

        $sourceId = (int) config('opencart.business_source_id', 1);

        $latest = (new SyncPreviewWriteService())->latestPreviewData($sourceId);



        $counts = $latest['counts'] !== []

            ? $latest['counts']

            : [

                'fetched' => 0,

                'eligible' => 0,

                'updated_snapshot' => 0,

                'blocked_unmapped' => 0,

                'blocked_invalid_mapping' => 0,

                'skipped_missing_status' => 0,

                'return_candidates' => 0,

            ];



        $displayRows = [];

        $pagination = [

            'page' => max(1, $orderPage),

            'per_page' => (int) config('opencart.max_rows_per_page', 20),

            'has_previous' => false,

            'has_next' => false,

        ];



        $activePreviewId = (int) ($latest['preview']['sync_preview_id'] ?? 0);



        if (is_array($sessionPreview) && (int) ($sessionPreview['page'] ?? 0) === $pagination['page']) {

            $displayRows = $sessionPreview['display_rows'] ?? [];

            $pagination = array_merge($pagination, $sessionPreview['pagination'] ?? []);

            if ($sessionPreview['counts'] ?? []) {

                $counts = $sessionPreview['counts'];

            }

            if ((int) ($sessionPreview['preview_id'] ?? 0) > 0) {

                $activePreviewId = (int) $sessionPreview['preview_id'];

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



        $importableCount = (int) ($counts['eligible'] ?? 0) + (int) ($counts['updated_snapshot'] ?? 0);



        return [

            'source' => 'Lokkisona.com (OpenCart / PIT)',

            'active_preview_id' => $activePreviewId,

            'importable_count' => $importableCount,

            'rules' => [

                'Order import eligibility = Status Mapping only',

                'Follow Up → New Order imports even when product cost / vendor stock is missing',

                'Product Control handles cost, payables, and health warnings — never blocks order import',

                'Skip Missing / order_status_id = 0',

                'Max 20 orders per preview request — preview before import',

            ],

            'preview_counts' => OrderSyncPreviewPresenter::labeledPreviewCounts($counts),

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



        return OrderSyncPreviewPresenter::enrichDisplayRow(array_merge([

            'source_order_id' => (string) ($item['source_order_id'] ?? ''),

            'source_order_reference' => (string) ($item['source_order_reference'] ?? ''),

            'source_status' => (string) ($item['source_status'] ?? ''),

            'mapped_status' => (string) ($item['mapped_status'] ?? ''),

            'preview_status' => (string) ($item['preview_status'] ?? ''),

        ], $extra));

    }

}


