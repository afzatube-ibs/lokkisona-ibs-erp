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

        $costProvided = $this->valueProvided($input, 'product_cost');
        $stockProvided = $this->valueProvided($input, 'vendor_stock');
        $note = trim((string) ($input['note'] ?? '')) ?: null;
        $noteProvided = $note !== null;

        if (!$costProvided && !$stockProvided) {
            return WriteResult::fail('Enter cost or stock value before saving history.');
        }

        $oldCost = $product['product_cost'] !== null ? (float) $product['product_cost'] : null;
        $oldStock = (int) $product['vendor_stock'];
        $newCost = $costProvided ? round((float) $input['product_cost'], 2) : $oldCost;
        $newStock = $stockProvided ? (int) $input['vendor_stock'] : $oldStock;
        $supplierId = $product['supplier_id'] !== null ? (int) $product['supplier_id'] : null;

        $costChanged = $costProvided && ($oldCost === null || abs((float) $newCost - (float) $oldCost) > 0.0001);
        $stockChanged = $stockProvided && $newStock !== $oldStock;

        if (!$costChanged && !$stockChanged && !$noteProvided) {
            return WriteResult::fail('No cost/stock change or note was provided.');
        }

        $historyRows = 0;

        if ($costProvided && ($costChanged || $noteProvided)) {
            $this->costHistory->insert([
                'product_id' => $productId,
                'product_variant_id' => null,
                'supplier_id' => $supplierId,
                'old_cost' => $oldCost,
                'new_cost' => $newCost,
                'note' => $note,
            ]);
            $historyRows++;
        }

        if ($stockProvided && ($stockChanged || $noteProvided)) {
            $this->stockHistory->insert([
                'product_id' => $productId,
                'product_variant_id' => null,
                'supplier_id' => $supplierId,
                'old_stock' => $oldStock,
                'new_stock' => $newStock,
                'change_type' => $stockChanged ? 'manual_update' : 'manual_note',
                'note' => $note,
            ]);
            $historyRows++;
        }

        if ($historyRows === 0) {
            return WriteResult::fail('No audit history row was created. Provide a cost/stock change or note with cost/stock value.');
        }

        $this->products->updateCostStock($productId, $newCost ?? $oldCost, $newStock);

        ActivityLog::record('product_cost_stock_updated', 'Product cost/stock audit history saved', [
            'product_id' => $productId,
            'cost_changed' => $costChanged,
            'stock_changed' => $stockChanged,
            'note_saved' => $noteProvided,
            'history_rows' => $historyRows,
        ]);

        return WriteResult::ok('Cost/stock saved and audit history row created. Check the Audit Confirmation table below.', $productId);
    }

    public function updateVariantCostStock(int $variantId, array $input): WriteResult
    {
        if (!$this->variants->tableExists() || !$this->costHistory->tableExists() || !$this->stockHistory->tableExists()) {
            return WriteResult::fail('Variant or history tables not available.');
        }

        $variant = $this->variants->find($variantId);
        if ($variant === null) {
            return WriteResult::fail('Variant not found.');
        }

        $productId = (int) $variant['product_id'];
        $costProvided = $this->valueProvided($input, 'product_cost');
        $stockProvided = $this->valueProvided($input, 'vendor_stock');
        $note = trim((string) ($input['note'] ?? '')) ?: null;
        $noteProvided = $note !== null;

        if (!$costProvided && !$stockProvided) {
            return WriteResult::fail('Enter cost or stock value before saving variant history.');
        }

        $oldCost = $variant['product_cost'] !== null ? (float) $variant['product_cost'] : null;
        $oldStock = (int) $variant['vendor_stock'];
        $newCost = $costProvided ? round((float) $input['product_cost'], 2) : $oldCost;
        $newStock = $stockProvided ? (int) $input['vendor_stock'] : $oldStock;

        $costChanged = $costProvided && ($oldCost === null || abs((float) $newCost - (float) $oldCost) > 0.0001);
        $stockChanged = $stockProvided && $newStock !== $oldStock;

        if (!$costChanged && !$stockChanged && !$noteProvided) {
            return WriteResult::fail('No variant cost/stock change or note was provided.');
        }

        $historyRows = 0;

        if ($costProvided && ($costChanged || $noteProvided)) {
            $this->costHistory->insert([
                'product_id' => $productId,
                'product_variant_id' => $variantId,
                'supplier_id' => null,
                'old_cost' => $oldCost,
                'new_cost' => $newCost,
                'note' => $note,
            ]);
            $historyRows++;
        }

        if ($stockProvided && ($stockChanged || $noteProvided)) {
            $this->stockHistory->insert([
                'product_id' => $productId,
                'product_variant_id' => $variantId,
                'supplier_id' => null,
                'old_stock' => $oldStock,
                'new_stock' => $newStock,
                'change_type' => $stockChanged ? 'manual_update' : 'manual_note',
                'note' => $note,
            ]);
            $historyRows++;
        }

        if ($historyRows === 0) {
            return WriteResult::fail('No audit history row was created. Provide a variant cost/stock change or note with cost/stock value.');
        }

        $this->variants->updateCostStock($variantId, $newCost ?? $oldCost, $newStock);

        ActivityLog::record('variant_cost_stock_updated', 'Variant cost/stock audit history saved', [
            'product_variant_id' => $variantId,
            'cost_changed' => $costChanged,
            'stock_changed' => $stockChanged,
            'note_saved' => $noteProvided,
            'history_rows' => $historyRows,
        ]);

        return WriteResult::ok('Cost/stock saved and audit history row created. Check the Audit Confirmation table below.', $variantId);
    }

    private function valueProvided(array $input, string $key): bool
    {
        return array_key_exists($key, $input) && trim((string) $input[$key]) !== '';
    }
}