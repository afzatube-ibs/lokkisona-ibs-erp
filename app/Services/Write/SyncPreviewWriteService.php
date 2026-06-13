<?php

namespace App\Services\Write;

use App\ActivityLog;
use App\Domain\EntryMappingOptions;
use App\Domain\FinalResultMappingOptions;
use App\Domain\OrderFulfillmentPolicy;
use App\Domain\OrderWorkflowStatus;
use App\ReadFoundation\WriteGate;
use App\Repositories\Write\OrderWriteRepository;
use App\Repositories\Write\StatusMappingWriteRepository;
use App\Repositories\Write\SyncLogWriteRepository;
use App\Repositories\Write\SyncPreviewItemWriteRepository;
use App\Repositories\Write\SyncPreviewWriteRepository;
use App\Services\Read\OpenCartReadClient;
use App\Services\ReadOnly\ProductSyncPreviewService;
use App\Support\OrderSyncPreviewPresenter;

class SyncPreviewWriteService
{
    private SyncPreviewWriteRepository $previews;
    private SyncPreviewItemWriteRepository $previewItems;
    private StatusMappingWriteRepository $mappings;
    private OrderWriteRepository $orders;
    private SyncLogWriteRepository $logs;
    private OpenCartReadClient $client;

    public function __construct(
        ?SyncPreviewWriteRepository $previews = null,
        ?SyncPreviewItemWriteRepository $previewItems = null,
        ?StatusMappingWriteRepository $mappings = null,
        ?OrderWriteRepository $orders = null,
        ?SyncLogWriteRepository $logs = null,
        ?OpenCartReadClient $client = null
    ) {
        $this->previews = $previews ?? new SyncPreviewWriteRepository();
        $this->previewItems = $previewItems ?? new SyncPreviewItemWriteRepository();
        $this->mappings = $mappings ?? new StatusMappingWriteRepository();
        $this->orders = $orders ?? new OrderWriteRepository();
        $this->logs = $logs ?? new SyncLogWriteRepository();
        $this->client = $client ?? new OpenCartReadClient();
    }

    public function previewProducts(array $input = []): WriteResult
    {
        if (!(bool) config('opencart.product_sync_enabled', true)) {
            return WriteResult::fail('Product sync is disabled in System → Sync/API Settings.');
        }

        if (!WriteGate::productSyncImport()['ready']) {
            return WriteResult::fail(WriteGate::WARNING_MESSAGE);
        }

        $page = max(1, (int) ($input['page'] ?? 1));
        $sourceId = (int) ($input['business_source_id'] ?? config('opencart.business_source_id', 1));
        $preview = (new ProductSyncPreviewService())->previewPage($page, $sourceId);
        $previewMessage = trim((string) ($preview['message'] ?? ''));
        $displayRows = $preview['rows'] ?? [];
        $importRows = $this->bridgeImportRows($preview['import_rows'] ?? []);

        if ($displayRows === [] && $previewMessage !== '') {
            return WriteResult::fail($previewMessage);
        }

        if ($importRows === [] && $displayRows === []) {
            $_SESSION['ibs_product_sync_preview'] = [
                'page' => $page,
                'business_source_id' => $sourceId,
                'fetched_at' => time(),
                'empty_result' => true,
                'products' => [],
                'skip_stats' => $preview['skip_stats'] ?? OpenCartReadClient::emptySkipStats(),
                'pagination' => [
                    'page' => $page,
                    'per_page' => (int) ($preview['per_page'] ?? 20),
                    'has_previous' => $page > 1,
                    'has_next' => false,
                ],
                'display' => ['rows' => []],
            ];

            $skipStats = is_array($preview['skip_stats'] ?? null)
                ? $preview['skip_stats']
                : OpenCartReadClient::emptySkipStats();

            return WriteResult::ok(
                OpenCartReadClient::formatSupplierSkipMessage($skipStats, 0) . ' Page ' . $page . '.',
                $page
            );
        }

        $_SESSION['ibs_product_sync_preview'] = [
            'page' => $page,
            'business_source_id' => $sourceId,
            'fetched_at' => time(),
            'products' => $importRows,
            'skip_stats' => $preview['skip_stats'] ?? OpenCartReadClient::emptySkipStats(),
            'pagination' => [
                'page' => $preview['page'],
                'per_page' => $preview['per_page'],
                'has_previous' => $preview['has_previous'],
                'has_next' => $preview['has_next'],
            ],
            'display' => $preview,
        ];

        ActivityLog::record('product_sync_preview', 'Product sync preview loaded (read-only)', [
            'page' => $page,
            'row_count' => count($displayRows),
            'skip_stats' => $preview['skip_stats'] ?? [],
        ]);

        $skipStats = is_array($preview['skip_stats'] ?? null)
            ? $preview['skip_stats']
            : OpenCartReadClient::emptySkipStats();
        $message = OpenCartReadClient::formatSupplierSkipMessage($skipStats, count($displayRows));
        if ($displayRows !== []) {
            $message .= ' Confirm import to sync ERP.';
        }

        return WriteResult::ok($message, $page);
    }

    public function importProductsFromPreview(array $input = []): WriteResult
    {
        if (!(bool) config('opencart.product_sync_enabled', true)) {
            return WriteResult::fail('Product sync is disabled in System → Sync/API Settings.');
        }

        if (!WriteGate::productSyncImport()['ready']) {
            return WriteResult::fail(WriteGate::WARNING_MESSAGE);
        }

        $confirmed = in_array((string) ($input['import_confirmation'] ?? ''), ['1', 'on', 'yes'], true);
        if (!$confirmed) {
            return WriteResult::fail('Owner import confirmation is required.');
        }

        $session = $_SESSION['ibs_product_sync_preview'] ?? null;
        if (!is_array($session) || ($session['products'] ?? []) === []) {
            return WriteResult::fail('No product preview in session. Load product preview first.');
        }

        $page = max(1, (int) ($input['page'] ?? ($session['page'] ?? 1)));
        if ((int) ($session['page'] ?? 0) !== $page) {
            return WriteResult::fail('Product preview page mismatch. Reload preview for page ' . $page . ' before import.');
        }

        $sourceId = (int) ($session['business_source_id'] ?? config('opencart.business_source_id', 1));
        $products = $this->bridgeImportRows($session['products'] ?? []);
        $products = array_slice($products, 0, $this->productImportLimit());

        if ($products === []) {
            return WriteResult::fail('No supplier products (from_warehouse = 1) in preview session.');
        }

        $result = (new ProductWriteService())->upsertWarehouseProducts($sourceId, $products);
        if ($result->success) {
            $skipStats = is_array($session['skip_stats'] ?? null) ? $session['skip_stats'] : OpenCartReadClient::emptySkipStats();
            $skippedNonSupplier = (int) ($skipStats['skipped_not_supplier'] ?? 0)
                + (int) ($skipStats['skipped_missing_from_warehouse'] ?? 0);
            if ($skippedNonSupplier > 0) {
                $result = WriteResult::ok(
                    $result->message . ' Non-supplier rows skipped at preview: ' . $skippedNonSupplier . '.',
                    $result->id
                );
            }
        }

        return $result;
    }

    /**
     * @param array<int, mixed> $rows
     * @return array<int, array<string, mixed>>
     */
    private function bridgeImportRows(array $rows): array
    {
        $filtered = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            if (!OpenCartReadClient::isStrictSupplierProduct($row)) {
                continue;
            }
            $filtered[] = $row;
        }

        return $filtered;
    }

    private function productImportLimit(): int
    {
        return max(1, min((int) config('opencart.max_products_per_request', 20), 20));
    }

    public function runTestSync(array $input = []): WriteResult
    {
        if (!(bool) config('opencart.order_sync_enabled', true)) {
            return WriteResult::fail('Order sync is disabled in System → Sync/API Settings.');
        }

        if (!WriteGate::syncPreviewImport()['ready']) {
            return WriteResult::fail(WriteGate::WARNING_MESSAGE);
        }

        $sourceId = (int) ($input['business_source_id'] ?? config('opencart.business_source_id', 1));
        if ($this->mappings->countActiveQueueMappings($sourceId) === 0) {
            return WriteResult::fail('Configure Supplier Order Queue mappings in System → Sync Settings before order preview.');
        }

        $page = max(1, (int) ($input['page'] ?? 1));
        $fetch = $this->client->fetchSupplierOrdersPage($page);
        $orders = $fetch['rows'] ?? [];
        $fetchMessage = trim((string) ($fetch['message'] ?? ''));
        if ($orders === [] && $fetchMessage !== '') {
            return WriteResult::fail($fetchMessage);
        }

        $previewRef = 'TS-' . date('YmdHis') . '-' . random_int(100, 999);
        $previewId = $this->previews->create([
            'business_source_id' => $sourceId,
            'preview_reference' => $previewRef,
            'preview_type' => 'test',
            'total_found' => 0,
            'total_new' => 0,
            'total_existing' => 0,
            'total_blocked' => 0,
            'status' => 'running',
            'requested_by' => null,
        ]);

        $counts = [
            'fetched' => count($orders),
            'eligible' => 0,
            'updated_snapshot' => 0,
            'blocked_unmapped' => 0,
            'blocked_invalid_mapping' => 0,
            'blocked_demo_order' => 0,
            'skipped_missing_status' => 0,
            'return_candidates' => 0,
        ];

        $ordersByRef = [];
        $displayRows = [];

        foreach ($orders as $order) {
            $classification = $this->classifyOrder($sourceId, $order);
            $extra = $classification['extra'];
            $this->previewItems->create($this->previewItemRow(
                $previewId,
                $order,
                $classification['mapped_status'],
                $classification['preview_status'],
                json_encode($extra, JSON_UNESCAPED_UNICODE)
            ));

            $counts[$classification['count_key']]++;
            if ($classification['mapped_status'] === 'order_returning') {
                $counts['return_candidates']++;
            }

            $ref = trim((string) ($order['source_order_reference'] ?? ''));
            if ($ref !== '') {
                $ordersByRef[$ref] = $order;
            }

            $displayRows[] = OrderSyncPreviewPresenter::enrichDisplayRow(array_merge([
                'source_order_id' => (string) ($order['source_order_id'] ?? ''),
                'source_order_reference' => $ref,
                'source_status' => (string) ($order['source_status'] ?? ''),
                'mapped_status' => (string) ($classification['mapped_status'] ?? ''),
                'preview_status' => (string) $classification['preview_status'],
            ], $extra));
        }

        $totals = [
            'total_found' => count($orders),
            'total_new' => $counts['eligible'],
            'total_existing' => $counts['updated_snapshot'],
            'total_blocked' => $counts['blocked_unmapped'] + $counts['blocked_invalid_mapping'] + $counts['skipped_missing_status'],
        ];
        $this->previews->finish($previewId, $totals, 'completed');

        if ($this->logs->tableExists()) {
            $this->logs->append($sourceId, $previewId, null, 'test_sync', 'completed', 'Order sync preview completed', [
                'counts' => $counts,
                'page' => $page,
                'orders_by_ref' => $ordersByRef,
            ]);
        }

        $_SESSION['ibs_order_sync_preview'] = [
            'page' => $page,
            'preview_id' => $previewId,
            'business_source_id' => $sourceId,
            'fetched_at' => time(),
            'orders_by_ref' => $ordersByRef,
            'display_rows' => $displayRows,
            'counts' => $counts,
            'pagination' => [
                'page' => $page,
                'per_page' => (int) ($fetch['per_page'] ?? 20),
                'has_previous' => (bool) ($fetch['has_previous'] ?? false),
                'has_next' => (bool) ($fetch['has_next'] ?? false),
            ],
        ];

        ActivityLog::record('sync_test_preview', 'Order sync preview run completed', [
            'sync_preview_id' => $previewId,
            'preview_reference' => $previewRef,
            'counts' => $counts,
            'page' => $page,
        ]);

        return WriteResult::ok(
            'Order preview page ' . $page . ': ' . $counts['eligible'] . ' new import, '
            . $counts['updated_snapshot'] . ' snapshot update, '
            . $counts['blocked_unmapped'] . ' blocked unmapped status, '
            . $counts['blocked_invalid_mapping'] . ' blocked invalid mapping, '
            . $counts['skipped_missing_status'] . ' missing status skipped. '
            . 'Product/cost/stock does not affect order import eligibility.',
            $previewId
        );
    }

    public function latestPreviewData(int $businessSourceId): array
    {
        $preview = $this->previews->findLatestForSource($businessSourceId);
        if ($preview === null) {
            return [
                'preview' => null,
                'items' => [],
                'counts' => [],
            ];
        }

        $items = $this->previewItems->forPreview((int) $preview['sync_preview_id']);
        $counts = [
            'fetched' => count($items),
            'eligible' => 0,
            'updated_snapshot' => 0,
            'blocked_unmapped' => 0,
            'blocked_invalid_mapping' => 0,
            'skipped_missing_status' => 0,
            'return_candidates' => 0,
        ];
        foreach ($items as $item) {
            $status = (string) ($item['preview_status'] ?? '');
            if ($status === 'eligible') {
                $counts['eligible']++;
            } elseif ($status === 'snapshot_update') {
                $counts['updated_snapshot']++;
            } elseif ($status === 'blocked_unmapped') {
                $counts['blocked_unmapped']++;
            } elseif ($status === 'blocked_invalid_mapping') {
                $counts['blocked_invalid_mapping']++;
            } elseif ($status === 'blocked_not_supplier_handled') {
                $counts['eligible']++;
            } elseif ($status === 'skipped_missing') {
                $counts['skipped_missing_status']++;
            }
            if (($item['mapped_status'] ?? '') === 'order_returning') {
                $counts['return_candidates']++;
            }
        }

        return ['preview' => $preview, 'items' => $items, 'counts' => $counts];
    }

    public function pullWarehouseProducts(array $input = []): WriteResult
    {
        return WriteResult::fail(
            'Direct product pull is disabled in v1.7.1. Load product preview first, then confirm import.'
        );
    }

    public function refreshWarehouseProductsFromApi(array $input = []): WriteResult
    {
        if (!(bool) config('opencart.product_sync_enabled', true)) {
            return WriteResult::fail('Product sync is disabled in System → Sync/API Settings.');
        }

        if (!WriteGate::productSyncImport()['ready']) {
            return WriteResult::fail(WriteGate::WARNING_MESSAGE);
        }

        if (!$this->client->warehouseProductPullAvailable()) {
            return WriteResult::fail('Product API route is not configured or unavailable.');
        }

        $sourceId = (int) ($input['business_source_id'] ?? config('opencart.business_source_id', 1));
        $previewService = new ProductSyncPreviewService();
        $page = 1;
        $totalProducts = 0;
        $totalVariants = 0;
        $skippedNonSupplier = 0;
        $lastMessage = '';

        $preview = $previewService->previewPage($page, $sourceId);
        $previewMessage = trim((string) ($preview['message'] ?? ''));
        if ($previewMessage !== '' && ($preview['import_rows'] ?? []) === [] && ($preview['rows'] ?? []) === []) {
            return WriteResult::fail($previewMessage);
        }

        $importRows = $this->bridgeImportRows($preview['import_rows'] ?? []);
        if ($importRows !== []) {
            $result = (new ProductWriteService())->upsertWarehouseProducts($sourceId, $importRows);
            if (!$result->success) {
                return $result;
            }

            if (preg_match('/Products imported: (\d+)/', $result->message, $m)) {
                $totalProducts += (int) $m[1];
            }
            if (preg_match('/Variants imported: (\d+)/', $result->message, $m)) {
                $totalVariants += (int) $m[1];
            }
            $lastMessage = $result->message;
        }

        $skipStats = is_array($preview['skip_stats'] ?? null)
            ? $preview['skip_stats']
            : OpenCartReadClient::emptySkipStats();
        $skippedNonSupplier += (int) ($skipStats['skipped_not_supplier'] ?? 0)
            + (int) ($skipStats['skipped_missing_from_warehouse'] ?? 0);

        ActivityLog::record('product_sync_refresh', 'Product Control warehouse refresh from API', [
            'pages' => 1,
            'products' => $totalProducts,
            'variants' => $totalVariants,
        ]);

        $message = 'Products refreshed from Dispatch Location (from_warehouse = 1). Max 20 per request.';
        if ($totalProducts > 0 || $totalVariants > 0) {
            $message .= ' Products updated: ' . $totalProducts . '. Variants updated: ' . $totalVariants . '.';
        } elseif ($lastMessage !== '') {
            $message .= ' ' . $lastMessage;
        } else {
            $message .= ' No supplier products returned on this refresh.';
        }
        if ($skippedNonSupplier > 0) {
            $message .= ' Non-supplier rows skipped: ' . $skippedNonSupplier . '.';
        }
        if (!empty($preview['has_next'])) {
            $message .= ' More products available — run refresh again for the next page (max 20 per request).';
        }

        return WriteResult::ok($message, 1);
    }

    private function classifyOrder(int $sourceId, array $order): array
    {
        if (\App\Domain\OrderDemoGuard::shouldSkipInSyncPreview($order)) {
            return [
                'mapped_status' => null,
                'preview_status' => 'blocked_demo_order',
                'count_key' => 'blocked_demo_order',
                'extra' => $this->orderPreviewExtra($order),
            ];
        }

        $queueStatusId = trim((string) ($order['connector_queue_status'] ?? $order['source_status_id'] ?? ''));
        $queueStatusLabel = trim((string) ($order['connector_queue_label'] ?? $order['source_status'] ?? ''));
        $sourceRef = trim((string) ($order['source_order_reference'] ?? ''));
        $extra = $this->orderPreviewExtra($order);
        $matchProbe = $this->mappings->probeQueueMapping($sourceId, $queueStatusId);
        $extra['origin_status_id'] = $queueStatusId;
        $extra['origin_status_name'] = $queueStatusLabel;
        $extra['connector_queue_status'] = $queueStatusId;
        $extra['connector_queue_label'] = $queueStatusLabel;
        $extra['mapping_matched'] = !empty($matchProbe['matched']) ? 'YES' : 'NO';
        $extra['mapping_match_mode'] = (string) ($matchProbe['match_mode'] ?? '');
        $extra['mapping_matched_key'] = (string) ($matchProbe['matched_key'] ?? '');

        if ($this->shouldSkipMissing($queueStatusId, $queueStatusLabel)) {
            return [
                'mapped_status' => null,
                'preview_status' => 'skipped_missing',
                'count_key' => 'skipped_missing_status',
                'extra' => $extra,
            ];
        }

        $mapping = $this->resolveQueueMapping($sourceId, $queueStatusId);
        $entryAction = $mapping !== null ? EntryMappingOptions::IMPORT_NEW : EntryMappingOptions::IGNORE;
        $extra['entry_action'] = $entryAction;
        $extra['entry_action_label'] = $entryAction === EntryMappingOptions::IMPORT_NEW ? 'Import as NEW' : 'Ignore';

        if ($mapping === null) {
            return [
                'mapped_status' => null,
                'preview_status' => 'blocked_unmapped',
                'count_key' => 'blocked_unmapped',
                'extra' => array_merge($extra, [
                    'block_reason' => 'Map queue status #' . $queueStatusId . ' in Sync & Mapping Settings → Entry Status Mapping.',
                ]),
            ];
        }

        $mappedStatus = EntryMappingOptions::IBS_STATUS_FOR_IMPORT;
        $extra['mapped_status_label'] = 'Import as NEW';

        $sourceOrderId = trim((string) ($order['source_order_id'] ?? ''));
        $existing = $this->orders->findExistingForSync($sourceId, $sourceOrderId, $sourceRef);
        if ($existing !== null) {
            $finalMapping = $this->mappings->resolveFinalResultMapping($sourceId, $queueStatusId);
            $finalTarget = $finalMapping !== null
                ? OrderWorkflowStatus::normalize((string) ($finalMapping['ibs_status'] ?? ''))
                : '';
            $extra['resync_mode'] = 'snapshot_only';
            $extra['resync_mode_label'] = 'Snapshot only';

            if ($finalTarget !== '' && $this->orderReadyForFinalResult($existing)) {
                $extra['final_result'] = $finalTarget;
                $extra['final_result_label'] = FinalResultMappingOptions::targetLabel($finalTarget);
                $extra['resync_mode'] = 'final_result';
                $extra['resync_mode_label'] = '→ ' . FinalResultMappingOptions::targetLabel($finalTarget);
            }

            return [
                'mapped_status' => $finalTarget !== '' && ($extra['resync_mode'] ?? '') === 'final_result'
                    ? $finalTarget
                    : $mappedStatus,
                'preview_status' => 'snapshot_update',
                'count_key' => 'updated_snapshot',
                'extra' => $extra,
            ];
        }

        return [
            'mapped_status' => $mappedStatus,
            'preview_status' => 'eligible',
            'count_key' => 'eligible',
            'extra' => $extra,
        ];
    }

    /**
     * @param array<string, mixed> $existing
     */
    private function orderReadyForFinalResult(array $existing): bool
    {
        $orderId = (int) ($existing['order_id'] ?? 0);
        if ($orderId > 0 && OrderFulfillmentPolicy::orderWasDispatched($orderId)) {
            return true;
        }

        $status = OrderWorkflowStatus::normalize((string) ($existing['ibs_status'] ?? ''));
        $group = OrderWorkflowStatus::groupOrder();
        $ceilingIndex = array_search('shipped', $group, true);
        $statusIndex = array_search($status, $group, true);
        if ($ceilingIndex === false || $statusIndex === false) {
            return false;
        }

        return $statusIndex >= (int) $ceilingIndex;
    }

    private function orderPreviewExtra(array $order): array
    {
        $items = $order['items'] ?? [];
        $first = is_array($items) && isset($items[0]) && is_array($items[0]) ? $items[0] : [];
        $label = trim((string) ($first['product_name'] ?? ''));
        if (!empty($first['variant_label'])) {
            $label .= ' · ' . trim((string) $first['variant_label']);
        }
        $totalQty = (int) ($order['total_quantity'] ?? 0);
        if ($totalQty === 0) {
            foreach ($items as $item) {
                if (is_array($item)) {
                    $totalQty += (int) ($item['quantity'] ?? 0);
                }
            }
        }

        return [
            'customer_phone' => (string) ($order['customer_phone'] ?? ''),
            'courier_status' => (string) ($order['courier_status'] ?? ''),
            'consignment_id' => (string) ($order['consignment_id'] ?? ''),
            'total_quantity' => $totalQty,
            'product_card' => $label !== '' ? $label : '—',
        ];
    }

    private function previewItemRow(int $previewId, array $order, ?string $mappedStatus, string $previewStatus, ?string $issue): array
    {
        return [
            'sync_preview_id' => $previewId,
            'source_order_id' => (string) ($order['source_order_id'] ?? ''),
            'source_order_reference' => (string) ($order['source_order_reference'] ?? ''),
            'source_invoice_reference' => $order['source_invoice_reference'] ?? null,
            'source_status' => (string) ($order['source_status'] ?? ''),
            'mapped_status' => $mappedStatus,
            'customer_name' => (string) ($order['customer_name'] ?? ''),
            'order_total' => (float) ($order['order_total'] ?? 0),
            'item_count' => count($order['items'] ?? []),
            'preview_status' => $previewStatus,
            'issue_summary' => $issue,
        ];
    }

    private function shouldSkipMissing(string $statusId, string $statusName): bool
    {
        $skipIds = config('opencart.skip_status_ids', ['0']);
        $skipNames = config('opencart.skip_status_names', ['Missing']);
        if (in_array($statusId, $skipIds, true)) {
            return true;
        }

        return in_array(strtolower($statusName), array_map('strtolower', $skipNames), true);
    }

    private function resolveQueueMapping(int $sourceId, string $queueStatusId): ?array
    {
        return $this->mappings->resolveQueueMapping($sourceId, $queueStatusId);
    }
}
