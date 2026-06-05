<?php

namespace App\Services\Write;

use App\ActivityLog;
use App\Repositories\Write\ProductCostHistoryWriteRepository;
use App\Repositories\Write\ProductStockHistoryWriteRepository;
use App\Repositories\Write\ProductVariantWriteRepository;
use App\Repositories\Write\ProductWriteRepository;

class ProductCostStockWriteService
{
    private ProductWriteRepository $products;
    private ProductVariantWriteRepository $variants;
    private ProductCostHistoryWriteRepository $costHistory;
    private ProductStockHistoryWriteRepository $stockHistory;

    public function __construct(
        ?ProductWriteRepository $products = null,
        ?ProductVariantWriteRepository $variants = null,
        ?ProductCostHistoryWriteRepository $costHistory = null,
        ?ProductStockHistoryWriteRepository $stockHistory = null
    ) {
        $this->products = $products ?? new ProductWriteRepository();
        $this->variants = $variants ?? new ProductVariantWriteRepository();
        $this->costHistory = $costHistory ?? new ProductCostHistoryWriteRepository();
        $this->stockHistory = $stockHistory ?? new ProductStockHistoryWriteRepository();
    }

    public function updateProductCostStock(int $productId, array $input): WriteResult
    {
        if (!$this->products->tableExists() || !$this->costHistory->tableExists() || !$this->stockHistory->tableExists()) {
            return WriteResult::fail('Product or history tables not available.');
        }

        $product = $this->products->find($productId);
        if ($product === null) {
            return WriteResult::fail('Product not found.');
        }

        $newCost = isset($input['product_cost']) && $input['product_cost'] !== '' ? round((float) $input['product_cost'], 2) : null;
        $newStock = isset($input['vendor_stock']) ? (int) $input['vendor_stock'] : (int) $product['vendor_stock'];
        $oldCost = $product['product_cost'] !== null ? (float) $product['product_cost'] : null;
        $oldStock = (int) $product['vendor_stock'];
        $supplierId = $product['supplier_id'] !== null ? (int) $product['supplier_id'] : null;
        $note = trim((string) ($input['note'] ?? '')) ?: null;

        if ($newCost !== null && $newCost !== $oldCost) {
            $this->costHistory->insert([
                'product_id' => $productId,
                'product_variant_id' => null,
                'supplier_id' => $supplierId,
                'old_cost' => $oldCost,
                'new_cost' => $newCost,
                'note' => $note,
            ]);
        }

        if ($newStock !== $oldStock) {
            $this->stockHistory->insert([
                'product_id' => $productId,
                'product_variant_id' => null,
                'supplier_id' => $supplierId,
                'old_stock' => $oldStock,
                'new_stock' => $newStock,
                'change_type' => 'manual_update',
                'note' => $note,
            ]);
        }

        $this->products->updateCostStock($productId, $newCost ?? $oldCost, $newStock);

        ActivityLog::record('product_cost_stock_updated', 'Product cost/stock updated with history', ['product_id' => $productId]);

        return WriteResult::ok('Product cost and stock updated with audit history.', $productId);
    }

    public function updateVariantCostStock(int $variantId, array $input): WriteResult
    {
        if (!$this->variants->tableExists()) {
            return WriteResult::fail('Variant table not available.');
        }

        $variant = $this->variants->find($variantId);
        if ($variant === null) {
            return WriteResult::fail('Variant not found.');
        }

        $productId = (int) $variant['product_id'];
        $newCost = isset($input['product_cost']) && $input['product_cost'] !== '' ? round((float) $input['product_cost'], 2) : null;
        $newStock = isset($input['vendor_stock']) ? (int) $input['vendor_stock'] : (int) $variant['vendor_stock'];
        $oldCost = $variant['product_cost'] !== null ? (float) $variant['product_cost'] : null;
        $oldStock = (int) $variant['vendor_stock'];
        $note = trim((string) ($input['note'] ?? '')) ?: null;

        if ($newCost !== null && $newCost !== $oldCost) {
            $this->costHistory->insert([
                'product_id' => $productId,
                'product_variant_id' => $variantId,
                'supplier_id' => null,
                'old_cost' => $oldCost,
                'new_cost' => $newCost,
                'note' => $note,
            ]);
        }

        if ($newStock !== $oldStock) {
            $this->stockHistory->insert([
                'product_id' => $productId,
                'product_variant_id' => $variantId,
                'supplier_id' => null,
                'old_stock' => $oldStock,
                'new_stock' => $newStock,
                'change_type' => 'manual_update',
                'note' => $note,
            ]);
        }

        $this->variants->updateCostStock($variantId, $newCost ?? $oldCost, $newStock);

        ActivityLog::record('variant_cost_stock_updated', 'Variant cost/stock updated', ['product_variant_id' => $variantId]);

        return WriteResult::ok('Variant cost and stock updated.', $variantId);
    }
}
