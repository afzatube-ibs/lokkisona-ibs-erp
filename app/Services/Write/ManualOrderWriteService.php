<?php

namespace App\Services\Write;

use App\ActivityLog;
use App\Repositories\Write\ManualOrderItemWriteRepository;
use App\Repositories\Write\ManualOrderWriteRepository;
use App\Repositories\Write\OrderItemWriteRepository;
use App\Repositories\Write\OrderWriteRepository;
use App\Repositories\Write\ProductVariantWriteRepository;
use App\Repositories\Write\ProductWriteRepository;

class ManualOrderWriteService
{
    private ManualOrderWriteRepository $manualOrders;
    private ManualOrderItemWriteRepository $manualItems;
    private OrderWriteRepository $orders;
    private OrderItemWriteRepository $orderItems;
    private ProductWriteRepository $products;
    private ProductVariantWriteRepository $variants;

    public function __construct(
        ?ManualOrderWriteRepository $manualOrders = null,
        ?ManualOrderItemWriteRepository $manualItems = null,
        ?OrderWriteRepository $orders = null,
        ?OrderItemWriteRepository $orderItems = null,
        ?ProductWriteRepository $products = null,
        ?ProductVariantWriteRepository $variants = null
    ) {
        $this->manualOrders = $manualOrders ?? new ManualOrderWriteRepository();
        $this->manualItems = $manualItems ?? new ManualOrderItemWriteRepository();
        $this->orders = $orders ?? new OrderWriteRepository();
        $this->orderItems = $orderItems ?? new OrderItemWriteRepository();
        $this->products = $products ?? new ProductWriteRepository();
        $this->variants = $variants ?? new ProductVariantWriteRepository();
    }

    public function tableReady(): bool
    {
        return $this->manualOrders->tableExists();
    }

    public function create(array $input): WriteResult
    {
        if (!$this->manualOrders->tableExists()) {
            return WriteResult::fail('Manual order tables not available. Apply migration 0005 first.');
        }

        $sourceId = (int) ($input['business_source_id'] ?? 0);
        if ($sourceId <= 0) {
            return WriteResult::fail('Business source is required.');
        }

        $externalRef = trim((string) ($input['external_order_reference'] ?? ''));
        if ($externalRef !== '' && $this->manualOrders->findByExternalReference($externalRef) !== null) {
            return WriteResult::fail('Duplicate external order reference blocked.');
        }

        $ref = trim((string) ($input['manual_order_reference'] ?? ''));
        if ($ref === '') {
            $ref = 'MO-' . date('YmdHis') . '-' . random_int(100, 999);
        }

        $productId = (int) ($input['product_id'] ?? 0);
        $variantId = ($input['product_variant_id'] ?? '') !== '' ? (int) $input['product_variant_id'] : null;
        $qty = max(1, (int) ($input['quantity'] ?? 1));
        $sellingPrice = round((float) ($input['selling_price'] ?? 0), 2);
        $costSnapshot = $this->resolveCostSnapshot($productId, $variantId);
        $lineTotal = round($sellingPrice * $qty, 2);
        $productName = trim((string) ($input['product_name'] ?? 'Manual order item'));
        if ($productId > 0 && $this->products->tableExists()) {
            $product = $this->products->find($productId);
            if ($product !== null) {
                $productName = $product['product_name'];
            }
        }

        $manualId = $this->manualOrders->create([
            'business_source_id' => $sourceId,
            'supplier_id' => ($input['supplier_id'] ?? '') !== '' ? (int) $input['supplier_id'] : null,
            'manual_order_reference' => $ref,
            'external_order_reference' => $externalRef ?: null,
            'external_invoice_reference' => trim((string) ($input['external_invoice_reference'] ?? '')) ?: null,
            'customer_name' => trim((string) ($input['customer_name'] ?? '')) ?: null,
            'customer_phone' => trim((string) ($input['customer_phone'] ?? '')) ?: null,
            'customer_address' => trim((string) ($input['customer_address'] ?? '')) ?: null,
            'order_total' => $lineTotal,
            'ibs_status' => 'new_order',
            'entry_status' => 'confirmed',
        ]);

        if ($this->manualItems->tableExists()) {
            $this->manualItems->create([
                'manual_order_id' => $manualId,
                'product_id' => $productId > 0 ? $productId : null,
                'product_variant_id' => $variantId,
                'product_name' => $productName,
                'variant_label' => trim((string) ($input['variant_label'] ?? '')) ?: null,
                'quantity' => $qty,
                'selling_price' => $sellingPrice,
                'supplier_cost_snapshot' => $costSnapshot,
                'line_total' => $lineTotal,
            ]);
        }

        $orderId = null;
        if ($this->orders->tableExists() && $this->orderItems->tableExists()) {
            $orderId = $this->orders->createFromManual([
                'business_source_id' => $sourceId,
                'supplier_id' => ($input['supplier_id'] ?? '') !== '' ? (int) $input['supplier_id'] : null,
                'source_order_reference' => $externalRef ?: $ref,
                'order_reference' => $ref,
                'customer_name' => trim((string) ($input['customer_name'] ?? '')) ?: null,
                'customer_phone' => trim((string) ($input['customer_phone'] ?? '')) ?: null,
                'customer_address' => trim((string) ($input['customer_address'] ?? '')) ?: null,
                'order_total' => $lineTotal,
                'ibs_status' => 'new_order',
                'cost_snapshot_total' => round($costSnapshot * $qty, 2),
                'status' => 'active',
            ]);

            $this->orderItems->create([
                'order_id' => $orderId,
                'product_id' => $productId > 0 ? $productId : null,
                'product_variant_id' => $variantId,
                'product_name' => $productName,
                'variant_label' => trim((string) ($input['variant_label'] ?? '')) ?: null,
                'quantity' => $qty,
                'selling_price' => $sellingPrice,
                'supplier_cost_snapshot' => $costSnapshot,
                'line_total' => $lineTotal,
            ]);
        }

        ActivityLog::record('manual_order_created', 'Manual order created', [
            'manual_order_id' => $manualId,
            'order_id' => $orderId,
        ]);

        return WriteResult::ok('Manual order created with cost snapshot. No workflow action yet.', $manualId);
    }

    private function resolveCostSnapshot(int $productId, ?int $variantId): float
    {
        if ($variantId !== null && $this->variants->tableExists()) {
            $variant = $this->variants->find($variantId);
            if ($variant !== null && $variant['product_cost'] !== null) {
                return (float) $variant['product_cost'];
            }
        }

        if ($productId > 0 && $this->products->tableExists()) {
            $product = $this->products->find($productId);
            if ($product !== null && $product['product_cost'] !== null) {
                return (float) $product['product_cost'];
            }
        }

        return 0.0;
    }
}
