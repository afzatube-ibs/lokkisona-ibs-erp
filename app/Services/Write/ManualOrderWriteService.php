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

        $confirmed = in_array((string) ($input['dev_test_confirmation'] ?? ''), ['1', 'on', 'yes'], true);
        if (!$confirmed) {
            return WriteResult::fail('Manual order confirmation is required.');
        }

        $confirmationNote = trim((string) ($input['confirmation_note'] ?? ''));
        if ($confirmationNote === '') {
            return WriteResult::fail('Confirmation note is required.');
        }

        $sourceId = (int) ($input['business_source_id'] ?? 0);
        if ($sourceId <= 0) {
            return WriteResult::fail('Business source is required.');
        }

        $externalRef = trim((string) ($input['external_order_reference'] ?? ''));
        if ($externalRef !== '' && $this->manualOrders->findByExternalReference($externalRef, $sourceId) !== null) {
            return WriteResult::fail('Duplicate external order reference blocked. This source/reference already exists.');
        }

        $ref = trim((string) ($input['manual_order_reference'] ?? ''));
        if ($ref === '') {
            return WriteResult::fail('Order No / sales invoice number is required for manual orders.');
        }

        $customerName = trim((string) ($input['customer_name'] ?? ''));
        if ($customerName === '') {
            return WriteResult::fail('Customer name is required.');
        }

        $lineItems = $this->parseLineItems($input);
        if ($lineItems === null) {
            return WriteResult::fail('At least one valid product line is required.');
        }

        $orderTotal = 0.0;
        $costSnapshotTotal = 0.0;
        foreach ($lineItems as $line) {
            $orderTotal += $line['line_total'];
            $costSnapshotTotal += round($line['cost_snapshot'] * $line['quantity'], 2);
        }
        $orderTotal = round($orderTotal, 2);
        $costSnapshotTotal = round($costSnapshotTotal, 2);

        $manualId = $this->manualOrders->create([
            'business_source_id' => $sourceId,
            'supplier_id' => ($input['supplier_id'] ?? '') !== '' ? (int) $input['supplier_id'] : null,
            'manual_order_reference' => $ref,
            'external_order_reference' => $externalRef ?: null,
            'external_invoice_reference' => trim((string) ($input['external_invoice_reference'] ?? '')) ?: null,
            'customer_name' => $customerName,
            'customer_phone' => trim((string) ($input['customer_phone'] ?? '')) ?: null,
            'customer_address' => trim((string) ($input['customer_address'] ?? '')) ?: null,
            'order_total' => $orderTotal,
            'ibs_status' => 'new_order',
            'entry_status' => 'confirmed',
        ]);

        if ($this->manualItems->tableExists()) {
            foreach ($lineItems as $line) {
                $this->manualItems->create([
                    'manual_order_id' => $manualId,
                    'product_id' => $line['product_id'],
                    'product_variant_id' => $line['variant_id'],
                    'product_name' => $line['product_name'],
                    'variant_label' => $line['variant_label'],
                    'quantity' => $line['quantity'],
                    'selling_price' => $line['selling_price'],
                    'supplier_cost_snapshot' => $line['cost_snapshot'],
                    'line_total' => $line['line_total'],
                ]);
            }
        }

        $orderId = null;
        $bridgeReady = $this->orders->tableExists() && $this->orderItems->tableExists();
        if ($bridgeReady) {
            $orderId = $this->orders->createFromManual([
                'business_source_id' => $sourceId,
                'supplier_id' => ($input['supplier_id'] ?? '') !== '' ? (int) $input['supplier_id'] : null,
                'source_order_reference' => $externalRef ?: $ref,
                'order_reference' => $ref,
                'customer_name' => $customerName,
                'customer_phone' => trim((string) ($input['customer_phone'] ?? '')) ?: null,
                'customer_address' => trim((string) ($input['customer_address'] ?? '')) ?: null,
                'order_total' => $orderTotal,
                'ibs_status' => 'new_order',
                'cost_snapshot_total' => $costSnapshotTotal,
                'status' => 'active',
            ]);

            foreach ($lineItems as $line) {
                $this->orderItems->create([
                    'order_id' => $orderId,
                    'product_id' => $line['product_id'],
                    'product_variant_id' => $line['variant_id'],
                    'source_product_id' => null,
                    'product_name' => $line['product_name'],
                    'variant_label' => $line['variant_label'],
                    'quantity' => $line['quantity'],
                    'selling_price' => $line['selling_price'],
                    'supplier_cost_snapshot' => $line['cost_snapshot'],
                    'line_total' => $line['line_total'],
                ]);
            }
        }

        ActivityLog::record('manual_order_created', 'Manual order created', [
            'manual_order_id' => $manualId,
            'order_id' => $orderId,
            'confirmation_note' => $confirmationNote,
            'item_count' => count($lineItems),
        ]);

        $message = 'Manual order created with ' . count($lineItems) . ' line(s) and cost snapshots. No payable, stock deduction, invoice, or sync was created.';
        if ($orderId !== null) {
            $message .= ' Open in Order Workflow: /order-workflow?status=new_order';
        }

        return WriteResult::ok($message, $manualId);
    }

    /**
     * @return array<int, array{product_id: int, variant_id: ?int, product_name: string, variant_label: ?string, quantity: int, selling_price: float, cost_snapshot: float, line_total: float}>|null
     */
    private function parseLineItems(array $input): ?array
    {
        $rawItems = $input['items'] ?? null;
        if (!is_array($rawItems) || $rawItems === []) {
            $legacyProductId = (int) ($input['product_id'] ?? 0);
            if ($legacyProductId <= 0) {
                return null;
            }
            $rawItems = [[
                'product_id' => $legacyProductId,
                'product_variant_id' => $input['product_variant_id'] ?? '',
                'variant_label' => $input['variant_label'] ?? '',
                'quantity' => $input['quantity'] ?? 0,
                'selling_price' => $input['selling_price'] ?? '',
            ]];
        }

        $lines = [];
        foreach ($rawItems as $row) {
            if (!is_array($row)) {
                continue;
            }

            $productId = (int) ($row['product_id'] ?? 0);
            if ($productId <= 0) {
                return null;
            }

            $qty = (int) ($row['quantity'] ?? 0);
            if ($qty <= 0) {
                return null;
            }

            if (($row['selling_price'] ?? '') === '' || !is_numeric($row['selling_price'])) {
                return null;
            }
            $sellingPrice = round((float) $row['selling_price'], 2);

            $variantId = ($row['product_variant_id'] ?? $row['variant_id'] ?? '') !== ''
                ? (int) ($row['product_variant_id'] ?? $row['variant_id'])
                : null;

            $costSnapshot = $this->resolveCostSnapshot($productId, $variantId);
            $lineTotal = round($sellingPrice * $qty, 2);
            $productName = $this->resolveProductName($productId);
            $variantLabel = trim((string) ($row['variant_label'] ?? ''));
            if ($variantLabel === '' && $variantId !== null) {
                $variantLabel = $this->resolveVariantLabel($variantId);
            }

            $lines[] = [
                'product_id' => $productId,
                'variant_id' => $variantId,
                'product_name' => $productName,
                'variant_label' => $variantLabel !== '' ? $variantLabel : null,
                'quantity' => $qty,
                'selling_price' => $sellingPrice,
                'cost_snapshot' => $costSnapshot,
                'line_total' => $lineTotal,
            ];
        }

        return $lines !== [] ? $lines : null;
    }

    private function resolveProductName(int $productId): string
    {
        if ($productId > 0 && $this->products->tableExists()) {
            $product = $this->products->find($productId);
            if ($product !== null) {
                $name = trim((string) ($product['product_name'] ?? ''));
                if ($name !== '') {
                    return $name;
                }
            }
        }

        return 'Manual order item';
    }

    private function resolveVariantLabel(int $variantId): string
    {
        if ($this->variants->tableExists()) {
            $variant = $this->variants->find($variantId);
            if ($variant !== null) {
                $optionName = trim((string) ($variant['option_name'] ?? ''));
                $optionValue = trim((string) ($variant['option_value'] ?? ''));

                return trim($optionName . ($optionValue !== '' ? ': ' . $optionValue : ''));
            }
        }

        return '';
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
