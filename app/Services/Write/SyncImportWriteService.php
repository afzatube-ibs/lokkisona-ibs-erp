<?php

namespace App\Services\Write;

use App\ActivityLog;
use App\ReadFoundation\WriteGate;
use App\Repositories\Write\OrderItemWriteRepository;
use App\Repositories\Write\OrderWorkflowHistoryWriteRepository;
use App\Repositories\Write\OrderWriteRepository;
use App\Repositories\Write\ProductWriteRepository;
use App\Repositories\Write\ProductVariantWriteRepository;
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
            return WriteResult::fail('No sync preview found. Run Test Sync first.');
        }

        $preview = $this->previews->find($previewId);
        if ($preview === null) {
            return WriteResult::fail('Sync preview not found.');
        }

        $eligibleItems = $this->previewItems->forPreview($previewId, 'eligible');
        if ($eligibleItems === []) {
            return WriteResult::fail('No eligible preview rows to import.');
        }

        $eligibleItems = array_slice($eligibleItems, 0, 50);
        $sourceOrders = $this->indexOrdersByReference($this->client->fetchSupplierOrders());
        $sourceId = (int) ($preview['business_source_id'] ?? config('opencart.business_source_id', 1));

        $importRef = 'IMP-' . date('YmdHis') . '-' . random_int(100, 999);
        $importId = $this->imports->create([
            'sync_preview_id' => $previewId,
            'business_source_id' => $sourceId,
            'import_reference' => $importRef,
            'total_selected' => count($eligibleItems),
            'total_imported' => 0,
            'total_failed' => 0,
            'status' => 'running',
            'approved_by' => null,
        ]);

        $imported = 0;
        $failed = 0;
        foreach ($eligibleItems as $item) {
            $ref = trim((string) ($item['source_order_reference'] ?? ''));
            if ($ref === '') {
                $failed++;
                continue;
            }

            if ($this->orders->findBySourceReference($ref, $sourceId) !== null) {
                $failed++;
                continue;
            }

            $orderPayload = $sourceOrders[$ref] ?? null;
            if ($orderPayload === null) {
                $failed++;
                continue;
            }

            $ibsStatus = (string) ($item['mapped_status'] ?? 'new_order');
            $costTotal = 0.0;
            $lineItems = [];
            foreach (($orderPayload['items'] ?? []) as $line) {
                $productId = isset($line['product_id']) ? (int) $line['product_id'] : null;
                if ($productId !== null && $productId <= 0) {
                    $productId = null;
                }
                $qty = max(1, (int) ($line['quantity'] ?? 1));
                $sellingPrice = round((float) ($line['selling_price'] ?? 0), 2);
                $costSnapshot = $this->resolveCostSnapshot($productId, null);
                $lineTotal = round($sellingPrice * $qty, 2);
                $costTotal += round($costSnapshot * $qty, 2);
                $lineItems[] = [
                    'product_id' => $productId,
                    'product_variant_id' => null,
                    'product_name' => (string) ($line['product_name'] ?? 'Synced item'),
                    'variant_label' => $line['variant_label'] ?? null,
                    'quantity' => $qty,
                    'selling_price' => $sellingPrice,
                    'supplier_cost_snapshot' => $costSnapshot,
                    'line_total' => $lineTotal,
                ];
            }

            $orderReference = 'IBS-SYNC-' . $ref;
            $orderId = $this->orders->createFromSync([
                'business_source_id' => $sourceId,
                'supplier_id' => null,
                'source_order_id' => (string) ($orderPayload['source_order_id'] ?? ''),
                'source_order_reference' => $ref,
                'source_invoice_reference' => $orderPayload['source_invoice_reference'] ?? null,
                'order_reference' => $orderReference,
                'customer_name' => (string) ($orderPayload['customer_name'] ?? 'Synced customer'),
                'customer_phone' => $orderPayload['customer_phone'] ?? null,
                'customer_address' => $orderPayload['customer_address'] ?? null,
                'order_total' => round((float) ($orderPayload['order_total'] ?? 0), 2),
                'ibs_status' => $ibsStatus,
                'cost_snapshot_total' => round($costTotal, 2),
                'status' => 'active',
            ]);

            foreach ($lineItems as $line) {
                $this->orderItems->create(array_merge($line, ['order_id' => $orderId]));
            }

            if ($this->history->tableExists()) {
                $this->history->insert($orderId, null, null, $ibsStatus, 'Imported from OpenCart Test Sync preview ' . ($preview['preview_reference'] ?? ''), null);
            }

            $imported++;
        }

        $status = $failed > 0 && $imported > 0 ? 'partial' : ($imported > 0 ? 'completed' : 'failed');
        $this->imports->finish($importId, $imported, $failed, $status);

        if ($this->logs->tableExists()) {
            $this->logs->append($sourceId, $previewId, $importId, 'controlled_import', $status, 'Controlled import finished', [
                'imported' => $imported,
                'failed' => $failed,
            ]);
        }

        ActivityLog::record('sync_import', 'Controlled sync import completed', [
            'sync_import_id' => $importId,
            'sync_preview_id' => $previewId,
            'imported' => $imported,
            'failed' => $failed,
        ]);

        return WriteResult::ok('Import finished: ' . $imported . ' order(s) created, ' . $failed . ' failed.', $importId);
    }

    private function indexOrdersByReference(array $orders): array
    {
        $indexed = [];
        foreach ($orders as $order) {
            $ref = trim((string) ($order['source_order_reference'] ?? ''));
            if ($ref !== '') {
                $indexed[$ref] = $order;
            }
        }

        return $indexed;
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
}
