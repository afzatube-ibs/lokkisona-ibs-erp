<?php

namespace App\Services\Write;

use App\ActivityLog;
use App\ReadFoundation\WriteGate;
use App\Repositories\Write\OrderWriteRepository;
use App\Repositories\Write\StatusMappingWriteRepository;
use App\Repositories\Write\SyncLogWriteRepository;
use App\Repositories\Write\SyncPreviewItemWriteRepository;
use App\Repositories\Write\SyncPreviewWriteRepository;
use App\Services\Read\OpenCartReadClient;
use App\Services\ReadOnly\ProductSyncPreviewService;

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

        if (($preview['bridge_available'] ?? false) !== true) {
            return WriteResult::fail((string) ($preview['bridge_warning'] ?? OpenCartReadClient::BRIDGE_WARNING));
        }

        $rawFetch = $this->client->fetchWarehouseProductsPage($page);
        $importRows = $this->bridgeImportRows($rawFetch['rows'] ?? []);
        $_SESSION['ibs_product_sync_preview'] = [
            'page' => $page,
            'business_source_id' => $sourceId,
            'fetched_at' => time(),
            'products' => $importRows,
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
            'row_count' => count($preview['rows'] ?? []),
        ]);

        $count = count($preview['rows'] ?? []);

        return WriteResult::ok('Product preview loaded: ' . $count . ' warehouse product(s) on page ' . $page . '. Confirm import to update ERP.', $page);
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
            return WriteResult::fail('No Dispatch Location bridge products (from_warehouse = 1) in preview session.');
        }

        return (new ProductWriteService())->upsertWarehouseProducts($sourceId, $products);
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
            if ((int) ($row['from_warehouse'] ?? 0) !== 1) {
                continue;
            }
            $filtered[] = $row;
        }

        return $filtered;
    }

    private function productImportLimit(): int
    {
        return max(1, (int) config('opencart.max_products_per_page', 20));
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
        if ($this->mappings->countActiveForSource($sourceId) === 0) {
            return WriteResult::fail('At least one active status mapping is required before order preview.');
        }

        $page = max(1, (int) ($input['page'] ?? 1));
        $fetch = $this->client->fetchSupplierOrdersPage($page);
        $orders = $fetch['rows'] ?? [];

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
            'eligible_supplier_orders' => 0,
            'blocked_unmapped' => 0,
            'blocked_not_supplier_handled' => 0,
            'skipped_missing_status' => 0,
            'return_candidates' => 0,
            'duplicate_existing' => 0,
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

            $displayRows[] = array_merge([
                'source_order_id' => (string) ($order['source_order_id'] ?? ''),
                'source_order_reference' => $ref,
                'source_status' => (string) ($order['source_status'] ?? ''),
                'mapped_status' => (string) ($classification['mapped_status'] ?? ''),
                'preview_status' => (string) $classification['preview_status'],
                'customer_name' => (string) ($order['customer_name'] ?? ''),
                'customer_phone' => (string) ($order['customer_phone'] ?? ''),
                'order_total' => number_format((float) ($order['order_total'] ?? 0), 2),
                'total_quantity' => (int) ($order['total_quantity'] ?? 0),
                'product_card' => (string) ($extra['product_card'] ?? ''),
                'courier_status' => (string) ($order['courier_status'] ?? ''),
                'consignment_id' => (string) ($order['consignment_id'] ?? ''),
                'supplier_handled' => (string) ($extra['supplier_handled'] ?? ''),
                'supplier_handled_reason' => (string) ($extra['supplier_handled_reason'] ?? ''),
                'already_imported' => $classification['preview_status'] === 'duplicate_existing' ? 'Yes' : 'No',
            ], $extra);
        }

        $totals = [
            'total_found' => count($orders),
            'total_new' => $counts['eligible_supplier_orders'],
            'total_existing' => $counts['duplicate_existing'],
            'total_blocked' => $counts['blocked_unmapped'] + $counts['blocked_not_supplier_handled'] + $counts['skipped_missing_status'],
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
            'Order preview page ' . $page . ': ' . $counts['eligible_supplier_orders'] . ' eligible, '
            . $counts['blocked_unmapped'] . ' unmapped, '
            . $counts['blocked_not_supplier_handled'] . ' not supplier-handled.',
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
            'eligible_supplier_orders' => 0,
            'blocked_unmapped' => 0,
            'blocked_not_supplier_handled' => 0,
            'skipped_missing_status' => 0,
            'return_candidates' => 0,
            'duplicate_existing' => 0,
        ];
        foreach ($items as $item) {
            $status = (string) ($item['preview_status'] ?? '');
            if ($status === 'eligible') {
                $counts['eligible_supplier_orders']++;
            } elseif ($status === 'blocked_unmapped') {
                $counts['blocked_unmapped']++;
            } elseif ($status === 'blocked_not_supplier_handled') {
                $counts['blocked_not_supplier_handled']++;
            } elseif ($status === 'skipped_missing') {
                $counts['skipped_missing_status']++;
            } elseif ($status === 'duplicate_existing') {
                $counts['duplicate_existing']++;
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

    private function classifyOrder(int $sourceId, array $order): array
    {
        $sourceStatusId = (string) ($order['source_status_id'] ?? '');
        $sourceStatus = trim((string) ($order['source_status'] ?? ''));
        $sourceRef = trim((string) ($order['source_order_reference'] ?? ''));
        $extra = $this->orderPreviewExtra($order);

        if ($this->shouldSkipMissing($sourceStatusId, $sourceStatus)) {
            $extra['supplier_handled'] = 'No';
            $extra['supplier_handled_reason'] = 'Missing or status 0 — skipped';

            return [
                'mapped_status' => null,
                'preview_status' => 'skipped_missing',
                'count_key' => 'skipped_missing_status',
                'extra' => $extra,
            ];
        }

        $mapping = $this->resolveMapping($sourceId, $sourceStatusId, $sourceStatus);
        if ($mapping === null) {
            $extra['supplier_handled'] = 'No';
            $extra['supplier_handled_reason'] = 'No active status mapping';

            return [
                'mapped_status' => null,
                'preview_status' => 'blocked_unmapped',
                'count_key' => 'blocked_unmapped',
                'extra' => $extra,
            ];
        }

        if (!$this->isSupplierHandled($mapping)) {
            $extra['supplier_handled'] = 'No';
            $extra['supplier_handled_reason'] = 'Status mapping is courier-only or not supplier-handled';

            return [
                'mapped_status' => (string) $mapping['ibs_status'],
                'preview_status' => 'blocked_not_supplier_handled',
                'count_key' => 'blocked_not_supplier_handled',
                'extra' => $extra,
            ];
        }

        $mappedStatus = (string) $mapping['ibs_status'];
        $extra['supplier_handled'] = 'Yes';
        $extra['supplier_handled_reason'] = 'Active supplier-handled status mapping';

        if ($sourceRef !== '' && $this->orders->findBySourceReference($sourceRef, $sourceId) !== null) {
            return [
                'mapped_status' => $mappedStatus,
                'preview_status' => 'duplicate_existing',
                'count_key' => 'duplicate_existing',
                'extra' => $extra,
            ];
        }

        return [
            'mapped_status' => $mappedStatus,
            'preview_status' => 'eligible',
            'count_key' => 'eligible_supplier_orders',
            'extra' => $extra,
        ];
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

    private function isSupplierHandled(array $mapping): bool
    {
        $group = strtolower(trim((string) ($mapping['workflow_group'] ?? '')));
        $ibsStatus = strtolower(trim((string) ($mapping['ibs_status'] ?? '')));
        $courierGroups = array_map('strtolower', (array) config('opencart.courier_only_workflow_groups', []));
        $supplierGroups = array_map('strtolower', (array) config('opencart.supplier_handled_workflow_groups', []));
        $courierStages = array_map('strtolower', (array) config('opencart.courier_stage_ibs_statuses', []));

        if ($group !== '' && in_array($group, $courierGroups, true)) {
            return false;
        }

        if ($group !== '' && in_array($group, $supplierGroups, true)) {
            return true;
        }

        if (in_array($ibsStatus, $courierStages, true)) {
            return false;
        }

        return $group === '' || $group === 'workflow';
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

    private function resolveMapping(int $sourceId, string $statusId, string $statusName): ?array
    {
        if ($statusName !== '') {
            $byName = $this->mappings->findBySourceStatus($sourceId, $statusName);
            if ($byName !== null) {
                return $byName;
            }
        }

        if ($statusId !== '') {
            return $this->mappings->findBySourceStatus($sourceId, $statusId);
        }

        return null;
    }
}
