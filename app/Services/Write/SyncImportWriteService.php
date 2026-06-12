<?php

namespace App\Services\Write;

use App\ActivityLog;
use App\Database\Connection;
use App\Domain\OrderCourierWorkflowLane;
use App\Domain\OrderFulfillmentPolicy;
use App\Domain\OrderSyncMappingRules;
use App\Domain\OrderSyncWorkflowBoundary;
use App\Domain\OrderWorkflowStatus;
use App\ReadFoundation\WriteGate;
use App\Repositories\Write\OrderItemWriteRepository;
use App\Repositories\Write\OrderWorkflowHistoryWriteRepository;
use App\Repositories\Write\OrderWriteRepository;
use App\Repositories\Write\ProductWriteRepository;
use App\Repositories\Write\ProductVariantWriteRepository;
use App\Repositories\Write\StatusMappingWriteRepository;
use App\Repositories\Write\SyncImportWriteRepository;
use App\Repositories\Write\SyncLogWriteRepository;
use App\Repositories\Write\SyncPreviewItemWriteRepository;
use App\Repositories\Write\SyncPreviewWriteRepository;
use App\Services\Read\OpenCartReadClient;

class SyncImportWriteService
{
    private SyncPreviewWriteRepository $previews;
    private SyncPreviewItemWriteRepository $previewItems;
    private SyncImportWriteRepository $imports;
    private SyncLogWriteRepository $logs;
    private OrderWriteRepository $orders;
    private OrderItemWriteRepository $orderItems;
    private OrderWorkflowHistoryWriteRepository $history;
    private ProductWriteRepository $products;
    private ProductVariantWriteRepository $variants;
    private StatusMappingWriteRepository $mappings;
    private OpenCartReadClient $client;

    public function __construct(
        ?SyncPreviewWriteRepository $previews = null,
        ?SyncPreviewItemWriteRepository $previewItems = null,
        ?SyncImportWriteRepository $imports = null,
        ?SyncLogWriteRepository $logs = null,
        ?OrderWriteRepository $orders = null,
        ?OrderItemWriteRepository $orderItems = null,
        ?OrderWorkflowHistoryWriteRepository $history = null,
        ?ProductWriteRepository $products = null,
        ?ProductVariantWriteRepository $variants = null,
        ?StatusMappingWriteRepository $mappings = null,
        ?OpenCartReadClient $client = null
    ) {
        $this->previews = $previews ?? new SyncPreviewWriteRepository();
        $this->previewItems = $previewItems ?? new SyncPreviewItemWriteRepository();
        $this->imports = $imports ?? new SyncImportWriteRepository();
        $this->logs = $logs ?? new SyncLogWriteRepository();
        $this->orders = $orders ?? new OrderWriteRepository();
        $this->orderItems = $orderItems ?? new OrderItemWriteRepository();
        $this->history = $history ?? new OrderWorkflowHistoryWriteRepository();
        $this->products = $products ?? new ProductWriteRepository();
        $this->variants = $variants ?? new ProductVariantWriteRepository();
        $this->mappings = $mappings ?? new StatusMappingWriteRepository();
        $this->client = $client ?? new OpenCartReadClient();
    }

    public function importFromPreview(array $input): WriteResult
    {
        if (!WriteGate::syncPreviewImport()['ready']) {
            return WriteResult::fail(WriteGate::WARNING_MESSAGE);
        }

        $confirmed = in_array((string) ($input['import_confirmation'] ?? ''), ['1', 'on', 'yes'], true);
        if (!$confirmed) {
            return WriteResult::fail('Owner import confirmation is required.');
        }

        $previewId = (int) ($input['sync_preview_id'] ?? 0);
        if ($previewId <= 0) {
            $sourceId = (int) ($input['business_source_id'] ?? config('opencart.business_source_id', 1));
            $latest = $this->previews->findLatestForSource($sourceId);
            $previewId = (int) ($latest['sync_preview_id'] ?? 0);
        }

        if ($previewId <= 0) {
            return WriteResult::fail('No sync preview found. Run Test Order Sync first.');
        }

        $preview = $this->previews->find($previewId);
        if ($preview === null) {
            return WriteResult::fail('Sync preview not found.');
        }

        $importItems = array_merge(
            $this->previewItems->forPreview($previewId, 'eligible'),
            $this->previewItems->forPreview($previewId, 'snapshot_update'),
            $this->previewItems->forPreview($previewId, 'blocked_not_supplier_handled')
        );

        if ($importItems === []) {
            return WriteResult::fail($this->noImportableRowsMessage($previewId, $preview));
        }

        $page = max(1, (int) ($input['page'] ?? 0));
        $session = $_SESSION['ibs_order_sync_preview'] ?? null;
        if (is_array($session) && (int) ($session['preview_id'] ?? 0) === $previewId) {
            $sourceOrders = is_array($session['orders_by_ref'] ?? null) ? $session['orders_by_ref'] : [];
            if ($page > 0 && (int) ($session['page'] ?? 0) !== $page) {
                return WriteResult::fail('Order preview page mismatch. Reload order preview for page ' . $page . ' before import.');
            }
        } else {
            $sourceOrders = $this->ordersFromSyncLog($previewId);
            if ($sourceOrders === []) {
                return WriteResult::fail('No order preview snapshot found. Run order preview again before import.');
            }
        }

        $limit = max(1, min((int) config('opencart.max_orders_per_request', 50), 50));
        $importItems = array_slice($importItems, 0, $limit);
        $sourceId = (int) ($preview['business_source_id'] ?? config('opencart.business_source_id', 1));

        $importRef = 'IMP-' . date('YmdHis') . '-' . random_int(100, 999);
        $importId = $this->imports->create([
            'sync_preview_id' => $previewId,
            'business_source_id' => $sourceId,
            'import_reference' => $importRef,
            'total_selected' => count($importItems),
            'total_imported' => 0,
            'total_failed' => 0,
            'status' => 'running',
            'approved_by' => null,
        ]);

        $summary = [
            'fetched' => (int) ($preview['total_found'] ?? 0),
            'eligible' => 0,
            'imported' => 0,
            'updated_snapshot' => 0,
            'skipped_unmapped' => 0,
            'skipped_missing_status' => 0,
            'skipped_duplicate' => 0,
            'errors' => 0,
        ];

        $pdo = Connection::pdo();
        $useTransaction = true;
        if ($useTransaction) {
            try {
                $pdo->beginTransaction();
            } catch (\Throwable $e) {
                $useTransaction = false;
            }
        }

        foreach ($importItems as $item) {
            $previewStatus = (string) ($item['preview_status'] ?? '');
            try {
                $result = $this->processImportItem($item, $sourceOrders, $sourceId, $preview, $previewStatus);
                $summary[$result['key']]++;
            } catch (\Throwable $e) {
                $summary['errors']++;
            }
        }

        if ($useTransaction) {
            try {
                $pdo->commit();
            } catch (\Throwable $e) {
                try {
                    $pdo->rollBack();
                } catch (\Throwable $rollbackError) {
                }

                return WriteResult::fail('Import transaction failed: ' . $e->getMessage());
            }
        }

        $imported = $summary['imported'];
        $updated = $summary['updated_snapshot'];
        $failed = $summary['errors'];
        $status = $failed > 0 && ($imported + $updated) > 0 ? 'partial' : (($imported + $updated) > 0 ? 'completed' : 'failed');
        $this->imports->finish($importId, $imported + $updated, $failed, $status);

        if ($this->logs->tableExists()) {
            $this->logs->append($sourceId, $previewId, $importId, 'controlled_import', $status, 'Controlled import finished', [
                'summary' => $summary,
            ]);
        }

        ActivityLog::record('sync_import', 'Controlled sync import completed', [
            'sync_import_id' => $importId,
            'sync_preview_id' => $previewId,
            'summary' => $summary,
        ]);

        return WriteResult::ok(
            'Import finished: ' . $imported . ' new, ' . $updated . ' snapshot update(s), ' . $failed . ' error(s). IBS workflow was not reset on existing orders.',
            $importId
        );
    }

    /**
     * @param array<string, mixed> $item
     * @param array<string, array<string, mixed>> $sourceOrders
     * @param array<string, mixed> $preview
     * @return array{key: string}
     */
    private function processImportItem(array $item, array $sourceOrders, int $sourceId, array $preview, string $previewStatus): array
    {
        $ref = trim((string) ($item['source_order_reference'] ?? ''));
        if ($ref === '') {
            return ['key' => 'errors'];
        }

        $orderPayload = $sourceOrders[$ref] ?? null;
        if ($orderPayload === null) {
            return ['key' => 'errors'];
        }

        $sourceOrderId = trim((string) ($orderPayload['source_order_id'] ?? ''));
        $existing = $this->orders->findExistingForSync($sourceId, $sourceOrderId, $ref);
        $now = date('Y-m-d H:i:s');
        $snapshot = $this->buildSnapshotPayload($orderPayload, $now);

        if ($previewStatus === 'blocked_not_supplier_handled') {
            $previewStatus = 'eligible';
        }

        if ($existing !== null || $previewStatus === 'snapshot_update') {
            if ($existing === null) {
                return ['key' => 'skipped_duplicate'];
            }

            $orderId = (int) $existing['order_id'];
            $this->orders->updateOriginSnapshot($orderId, $snapshot);
            $this->recordMappingMatch($sourceId, $orderPayload);

            if (!OrderFulfillmentPolicy::isManualSalesOrder($existing)) {
                $this->maybePromoteCourierStage($orderId, $existing, (string) ($item['mapped_status'] ?? ''));
            }

            return ['key' => 'updated_snapshot'];
        }

        $ibsStatus = OrderSyncMappingRules::normalizeIbsStatus((string) ($item['mapped_status'] ?? 'new_order'));
        if (OrderSyncWorkflowBoundary::isBeyondShipmentCeiling($ibsStatus)) {
            return ['key' => 'errors'];
        }
        if (!OrderSyncMappingRules::isAllowedInitialStatus($ibsStatus, OrderSyncMappingRules::advancedModeEnabled())) {
            return ['key' => 'errors'];
        }

        $costTotal = 0.0;
        $lineItems = $this->buildLineItems($orderPayload, $sourceId, $costTotal);
        $orderReference = 'IBS-SYNC-' . $ref;
        $sourceStatusLabel = trim((string) ($orderPayload['source_status'] ?? ''));
        $sourceStatusId = trim((string) ($orderPayload['source_status_id'] ?? ''));
        $historyNote = 'imported_from_mapping: OpenCart preview ' . ($preview['preview_reference'] ?? '')
            . '. Origin status: ' . ($sourceStatusLabel !== '' ? $sourceStatusLabel : '-')
            . ($sourceStatusId !== '' ? ' (id ' . $sourceStatusId . ')' : '')
            . ' → IBS ' . $ibsStatus;

        $orderId = $this->orders->createFromSync(array_merge($snapshot, [
            'business_source_id' => $sourceId,
            'supplier_id' => null,
            'source_order_id' => $sourceOrderId !== '' ? $sourceOrderId : null,
            'source_order_reference' => $ref,
            'source_invoice_reference' => $orderPayload['source_invoice_reference'] ?? null,
            'order_reference' => $orderReference,
            'customer_name' => (string) ($orderPayload['customer_name'] ?? 'Synced customer'),
            'customer_phone' => $orderPayload['customer_phone'] ?? null,
            'customer_address' => $orderPayload['customer_address'] ?? null,
            'order_total' => round((float) ($orderPayload['order_total'] ?? 0), 2),
            'ibs_status' => $ibsStatus,
            'courier_name' => $orderPayload['courier_name'] ?? null,
            'tracking_number' => $orderPayload['consignment_id'] ?? null,
            'courier_status' => $orderPayload['courier_status'] ?? null,
            'cost_snapshot_total' => round($costTotal, 2),
            'status' => 'active',
            'sync_source' => 'opencart',
            'imported_at' => $now,
        ]));

        foreach ($lineItems as $line) {
            $this->orderItems->create(array_merge($line, ['order_id' => $orderId]));
        }

        if ($this->history->tableExists()) {
            $this->history->insert($orderId, null, null, $ibsStatus, $historyNote, null);
        }

        $this->recordMappingMatch($sourceId, $orderPayload);

        return ['key' => 'imported'];
    }

    /**
     * @param array<string, mixed> $orderPayload
     * @return array<string, mixed>
     */
    private function buildSnapshotPayload(array $orderPayload, string $syncedAt): array
    {
        return [
            'origin_order_status_id' => trim((string) ($orderPayload['source_status_id'] ?? '')) ?: null,
            'origin_order_status_name' => trim((string) ($orderPayload['source_status'] ?? '')) ?: null,
            'courier_status' => $orderPayload['courier_status'] ?? null,
            'tracking_number' => $orderPayload['consignment_id'] ?? null,
            'customer_name' => (string) ($orderPayload['customer_name'] ?? 'Synced customer'),
            'customer_phone' => $orderPayload['customer_phone'] ?? null,
            'customer_address' => $orderPayload['customer_address'] ?? null,
            'last_synced_at' => $syncedAt,
        ];
    }

    /**
     * @param array<string, mixed> $orderPayload
     * @return array<int, array<string, mixed>>
     */
    private function buildLineItems(array $orderPayload, int $sourceId, float &$costTotal): array
    {
        $lineItems = [];
        $sourceOrderId = trim((string) ($orderPayload['source_order_id'] ?? ''));
        foreach (($orderPayload['items'] ?? []) as $line) {
            if (!is_array($line)) {
                continue;
            }
            $sourceProductId = null;
            if (isset($line['product_id']) && (int) $line['product_id'] > 0) {
                $sourceProductId = (string) (int) $line['product_id'];
            }
            $erpProductId = null;
            if ($sourceProductId !== null) {
                $erpProduct = $this->products->findBySourceProductId($sourceId, $sourceProductId);
                if ($erpProduct !== null) {
                    $erpProductId = (int) ($erpProduct['product_id'] ?? 0);
                    if ($erpProductId <= 0) {
                        $erpProductId = null;
                    }
                }
            }
            $qty = max(1, (int) ($line['quantity'] ?? 1));
            $sellingPrice = round((float) ($line['selling_price'] ?? 0), 2);
            $costSnapshot = $this->resolveCostSnapshot($erpProductId, null);
            $lineTotal = round($sellingPrice * $qty, 2);
            $costTotal += round($costSnapshot * $qty, 2);
            $lineKey = $this->buildSourceLineKey($sourceOrderId, $line);
            $row = [
                'product_id' => $erpProductId,
                'product_variant_id' => null,
                'source_product_id' => $sourceProductId,
                'product_name' => (string) ($line['product_name'] ?? 'Synced item'),
                'variant_label' => $line['variant_label'] ?? null,
                'quantity' => $qty,
                'selling_price' => $sellingPrice,
                'supplier_cost_snapshot' => $costSnapshot,
                'line_total' => $lineTotal,
            ];
            if ($lineKey !== '') {
                $row['source_line_key'] = $lineKey;
            }
            $lineItems[] = $row;
        }

        return $lineItems;
    }

    /**
     * @param array<string, mixed> $line
     */
    private function buildSourceLineKey(string $sourceOrderId, array $line): string
    {
        $productId = (string) (int) ($line['product_id'] ?? 0);
        $model = trim((string) ($line['model'] ?? ''));
        $variant = trim((string) ($line['variant_label'] ?? ''));
        $qty = (int) ($line['quantity'] ?? 1);
        $optionSig = trim((string) ($line['option_signature'] ?? $variant));

        if ($sourceOrderId === '' && $productId === '0') {
            return '';
        }

        return sha1($sourceOrderId . '|' . $productId . '|' . $model . '|' . $optionSig . '|' . $qty);
    }

    /**
     * @param array<string, mixed> $orderPayload
     */
    private function recordMappingMatch(int $sourceId, array $orderPayload): void
    {
        $statusId = trim((string) ($orderPayload['source_status_id'] ?? ''));
        $statusName = trim((string) ($orderPayload['source_status'] ?? ''));
        $mapping = null;
        if ($statusName !== '') {
            $mapping = $this->mappings->findBySourceStatus($sourceId, $statusName);
        }
        if ($mapping === null && $statusId !== '') {
            $mapping = $this->mappings->findBySourceStatus($sourceId, $statusId);
        }
        if ($mapping !== null) {
            $this->mappings->recordMatch((int) ($mapping['status_mapping_id'] ?? 0));
        }
    }

    private function ordersFromSyncLog(int $previewId): array
    {
        $context = $this->logs->findContextByPreviewId($previewId, 'test_sync');

        return is_array($context['orders_by_ref'] ?? null) ? $context['orders_by_ref'] : [];
    }

    /**
     * @param array<string, mixed> $existingOrder
     */
    private function maybePromoteCourierStage(int $orderId, array $existingOrder, string $mappedStatus): void
    {
        if ($orderId <= 0 || !OrderFulfillmentPolicy::orderWasDispatched($orderId)) {
            return;
        }

        $currentStatus = OrderWorkflowStatus::normalize((string) ($existingOrder['ibs_status'] ?? ''));
        $targetStatus = OrderCourierWorkflowLane::forwardPromotionTarget($currentStatus, $mappedStatus);
        if ($targetStatus === null || $targetStatus === $currentStatus) {
            return;
        }

        $this->orders->updateStatus($orderId, $targetStatus);

        if ($this->history->tableExists()) {
            $this->history->insert(
                $orderId,
                null,
                $currentStatus,
                $targetStatus,
                'courier_sync_forward: mapping → ' . OrderWorkflowStatus::normalize($mappedStatus),
                null
            );
        }
    }

    private function resolveCostSnapshot(?int $productId, ?int $variantId): float
    {
        if ($variantId !== null && $this->variants->tableExists()) {
            $variant = $this->variants->find($variantId);
            if ($variant !== null && $variant['product_cost'] !== null) {
                return (float) $variant['product_cost'];
            }
        }

        if ($productId !== null && $productId > 0 && $this->products->tableExists()) {
            $product = $this->products->find($productId);
            if ($product !== null && $product['product_cost'] !== null) {
                return (float) $product['product_cost'];
            }
        }

        return 0.0;
    }

    /**
     * @param array<string, mixed>|null $preview
     */
    private function noImportableRowsMessage(int $previewId, ?array $preview): string
    {
        $items = $this->previewItems->forPreview($previewId);
        $byStatus = [];
        foreach ($items as $item) {
            $status = (string) ($item['preview_status'] ?? 'unknown');
            $byStatus[$status] = ($byStatus[$status] ?? 0) + 1;
        }

        $parts = [];
        foreach ($byStatus as $status => $count) {
            $parts[] = $status . '=' . $count;
        }

        $summary = $parts !== [] ? implode(', ', $parts) : 'no preview rows stored';
        $fetched = (int) ($preview['total_found'] ?? count($items));

        return 'No eligible preview rows to import or update. '
            . 'Preview #' . $previewId . ' has ' . $fetched . ' row(s): ' . $summary . '. '
            . 'Order import eligibility is status-mapping-only — product/cost/stock never blocks import. '
            . 'Add matching status mappings at Status Mapping, run Test Order Sync again, and check Import Result.';
    }
}
