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

    public function runTestSync(array $input = []): WriteResult
    {
        if (!WriteGate::syncPreviewImport()['ready']) {
            return WriteResult::fail(WriteGate::WARNING_MESSAGE);
        }

        $sourceId = (int) ($input['business_source_id'] ?? config('opencart.business_source_id', 1));
        if ($this->mappings->countActiveForSource($sourceId) === 0) {
            return WriteResult::fail('At least one active status mapping is required before Test Sync.');
        }

        $orders = $this->client->fetchSupplierOrders();
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
            'skipped_missing_status' => 0,
            'return_candidates' => 0,
            'duplicate_existing' => 0,
        ];

        foreach ($orders as $order) {
            $sourceStatusId = (string) ($order['source_status_id'] ?? '');
            $sourceStatus = trim((string) ($order['source_status'] ?? ''));
            $sourceRef = trim((string) ($order['source_order_reference'] ?? ''));

            if ($this->shouldSkipMissing($sourceStatusId, $sourceStatus)) {
                $counts['skipped_missing_status']++;
                $this->previewItems->create($this->previewItemRow($previewId, $order, null, 'skipped_missing', 'Missing or status 0 — skipped'));
                continue;
            }

            $mapping = $this->resolveMapping($sourceId, $sourceStatusId, $sourceStatus);
            if ($mapping === null) {
                $counts['blocked_unmapped']++;
                $this->previewItems->create($this->previewItemRow($previewId, $order, null, 'blocked_unmapped', 'No active status mapping'));
                continue;
            }

            $mappedStatus = (string) $mapping['ibs_status'];
            if ($mappedStatus === 'order_returning') {
                $counts['return_candidates']++;
            }

            if ($sourceRef !== '' && $this->orders->findBySourceReference($sourceRef, $sourceId) !== null) {
                $counts['duplicate_existing']++;
                $this->previewItems->create($this->previewItemRow($previewId, $order, $mappedStatus, 'duplicate_existing', 'Source reference already in ERP'));
                continue;
            }

            $counts['eligible_supplier_orders']++;
            $this->previewItems->create($this->previewItemRow($previewId, $order, $mappedStatus, 'eligible', null));
        }

        $totals = [
            'total_found' => count($orders),
            'total_new' => $counts['eligible_supplier_orders'],
            'total_existing' => $counts['duplicate_existing'],
            'total_blocked' => $counts['blocked_unmapped'] + $counts['skipped_missing_status'],
        ];
        $this->previews->finish($previewId, $totals, 'completed');

        if ($this->logs->tableExists()) {
            $this->logs->append($sourceId, $previewId, null, 'test_sync', 'completed', 'Test Sync preview completed', $counts);
        }

        ActivityLog::record('sync_test_preview', 'Test Sync preview run completed', [
            'sync_preview_id' => $previewId,
            'preview_reference' => $previewRef,
            'counts' => $counts,
        ]);

        return WriteResult::ok('Test Sync preview completed: ' . $counts['eligible_supplier_orders'] . ' eligible, ' . $counts['blocked_unmapped'] . ' blocked unmapped.', $previewId);
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
