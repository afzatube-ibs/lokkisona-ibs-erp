<?php

namespace App\Services\Write;

use App\ActivityLog;
use App\Repositories\Write\ProductCostHistoryWriteRepository;
use App\Repositories\Write\ProductStockHistoryWriteRepository;
use App\Repositories\Write\ProductVariantWriteRepository;
use App\Repositories\Write\ProductWriteRepository;

class ProductWorkspaceWriteService
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

    public function save(int $productId, array $input): WriteResult
    {
        if (!$this->products->tableExists()) {
            return WriteResult::fail('Product table not available.');
        }

        $product = $this->products->find($productId);
        if ($product === null) {
            return WriteResult::fail('Product not found.');
        }

        $supplierFields = [
            'supplier_model' => array_key_exists('supplier_model', $input)
                ? $this->nullableString($input, 'supplier_model')
                : ($product['supplier_model'] ?? null),
            'product_cost' => array_key_exists('product_cost', $input)
                ? $this->nullableDecimal($input, 'product_cost', $product['product_cost'] ?? null)
                : ($product['product_cost'] !== null ? (float) $product['product_cost'] : null),
            'vendor_stock' => array_key_exists('vendor_stock', $input)
                ? (int) ($input['vendor_stock'] ?? 0)
                : (int) ($product['vendor_stock'] ?? 0),
            'low_warning_threshold' => array_key_exists('low_warning_threshold', $input)
                ? $this->nullableInt($input, 'low_warning_threshold', $product['low_warning_threshold'] ?? null)
                : ($product['low_warning_threshold'] !== null ? (int) $product['low_warning_threshold'] : null),
            'status' => $this->status($input, (string) ($product['status'] ?? 'active')),
        ];

        if ($this->products->supplierProductCategoryColumnReady()) {
            $supplierFields['supplier_product_category'] = array_key_exists('supplier_product_category', $input)
                ? $this->nullableString($input, 'supplier_product_category')
                : ($product['supplier_product_category'] ?? null);
        }

        if ($this->products->supplierNoteColumnReady() && array_key_exists('supplier_note', $input)) {
            $supplierFields['supplier_note'] = $this->nullableString($input, 'supplier_note');
        }

        $this->products->updateSupplierControlFields($productId, $supplierFields);

        if (array_key_exists('supplier_id', $input) && trim((string) $input['supplier_id']) !== '') {
            $supplierId = (int) $input['supplier_id'];
            $this->products->updateSupplierAssignment($productId, $supplierId > 0 ? $supplierId : null);
        }

        $this->recordProductHistoryIfChanged($productId, $product, $supplierFields);

        $variantRows = $input['variants'] ?? [];
        $updatedVariants = 0;
        if (is_array($variantRows) && $this->variants->tableExists()) {
            foreach ($variantRows as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $variantId = (int) ($row['product_variant_id'] ?? 0);
                if ($variantId <= 0) {
                    continue;
                }
                $existing = $this->variants->find($variantId);
                if ($existing === null || (int) ($existing['product_id'] ?? 0) !== $productId) {
                    continue;
                }

                $variantFields = [
                    'supplier_model' => array_key_exists('supplier_model', $row)
                        ? $this->nullableString($row, 'supplier_model')
                        : ($existing['supplier_model'] ?? null),
                    'product_cost' => array_key_exists('product_cost', $row)
                        ? $this->nullableDecimal($row, 'product_cost', $existing['product_cost'] ?? null)
                        : ($existing['product_cost'] !== null ? (float) $existing['product_cost'] : null),
                    'vendor_stock' => array_key_exists('vendor_stock', $row)
                        ? (int) ($row['vendor_stock'] ?? 0)
                        : (int) ($existing['vendor_stock'] ?? 0),
                    'status' => $this->status($row, (string) ($existing['status'] ?? 'active')),
                ];

                if ($this->products->supplierNoteColumnReady() && array_key_exists('supplier_note', $row)) {
                    $variantFields['supplier_note'] = $this->nullableString($row, 'supplier_note');
                }

                $this->variants->updateSupplierControlFields($variantId, $variantFields);
                $this->recordVariantHistoryIfChanged($productId, $variantId, $existing, $variantFields);
                $updatedVariants++;
            }
        }

        ActivityLog::record('product_workspace_saved', 'Supplier/ERP product fields saved (OpenCart data unchanged)', [
            'product_id' => $productId,
            'variants_updated' => $updatedVariants,
        ]);

        $message = $updatedVariants > 0
            ? 'Supplier/ERP fields saved (' . $updatedVariants . ' variant line(s) updated). OpenCart data was not changed.'
            : 'Supplier/ERP fields saved. OpenCart data was not changed.';

        return WriteResult::ok($message, $productId);
    }

    private function recordProductHistoryIfChanged(int $productId, array $product, array $newFields): void
    {
        if (!$this->costHistory->tableExists() || !$this->stockHistory->tableExists()) {
            return;
        }

        $oldCost = $product['product_cost'] !== null ? (float) $product['product_cost'] : null;
        $newCost = $newFields['product_cost'] ?? $oldCost;
        $oldStock = (int) ($product['vendor_stock'] ?? 0);
        $newStock = (int) ($newFields['vendor_stock'] ?? $oldStock);
        $supplierId = $product['supplier_id'] !== null ? (int) $product['supplier_id'] : null;

        $costChanged = $oldCost === null && $newCost !== null
            || ($oldCost !== null && $newCost !== null && abs((float) $newCost - (float) $oldCost) > 0.0001)
            || ($oldCost !== null && $newCost === null);

        $stockChanged = $newStock !== $oldStock;

        if ($costChanged) {
            $this->costHistory->insert([
                'product_id' => $productId,
                'product_variant_id' => null,
                'supplier_id' => $supplierId,
                'old_cost' => $oldCost,
                'new_cost' => $newCost,
                'note' => 'Workspace save',
            ]);
        }

        if ($stockChanged) {
            $this->stockHistory->insert([
                'product_id' => $productId,
                'product_variant_id' => null,
                'supplier_id' => $supplierId,
                'old_stock' => $oldStock,
                'new_stock' => $newStock,
                'change_type' => 'workspace_save',
                'note' => 'Workspace save',
            ]);
        }
    }

    private function recordVariantHistoryIfChanged(int $productId, int $variantId, array $existing, array $newFields): void
    {
        if (!$this->costHistory->tableExists() || !$this->stockHistory->tableExists()) {
            return;
        }

        $oldCost = $existing['product_cost'] !== null ? (float) $existing['product_cost'] : null;
        $newCost = $newFields['product_cost'] ?? $oldCost;
        $oldStock = (int) ($existing['vendor_stock'] ?? 0);
        $newStock = (int) ($newFields['vendor_stock'] ?? $oldStock);

        $costChanged = $oldCost === null && $newCost !== null
            || ($oldCost !== null && $newCost !== null && abs((float) $newCost - (float) $oldCost) > 0.0001)
            || ($oldCost !== null && $newCost === null);

        $stockChanged = $newStock !== $oldStock;

        if ($costChanged) {
            $this->costHistory->insert([
                'product_id' => $productId,
                'product_variant_id' => $variantId,
                'supplier_id' => null,
                'old_cost' => $oldCost,
                'new_cost' => $newCost,
                'note' => 'Workspace save (variant)',
            ]);
        }

        if ($stockChanged) {
            $this->stockHistory->insert([
                'product_id' => $productId,
                'product_variant_id' => $variantId,
                'supplier_id' => null,
                'old_stock' => $oldStock,
                'new_stock' => $newStock,
                'change_type' => 'workspace_save',
                'note' => 'Workspace save (variant)',
            ]);
        }
    }

    private function nullableInt(array $input, string $key, $fallback = null): ?int
    {
        if (!array_key_exists($key, $input)) {
            return $fallback === '' || $fallback === null ? null : (int) $fallback;
        }

        $val = $input[$key];

        return ($val === '' || $val === null) ? null : (int) $val;
    }

    private function nullableDecimal(array $input, string $key, $fallback = null): ?float
    {
        if (!array_key_exists($key, $input)) {
            return $fallback === '' || $fallback === null ? null : round((float) $fallback, 2);
        }

        $val = $input[$key];
        if ($val === '' || $val === null) {
            return null;
        }

        return round((float) $val, 2);
    }

    private function nullableString(array $input, string $key): ?string
    {
        if (!array_key_exists($key, $input)) {
            return null;
        }

        $value = trim((string) $input[$key]);

        return $value !== '' ? $value : null;
    }

    private function status(array $input, string $fallback): string
    {
        $status = trim((string) ($input['status'] ?? $fallback));

        return in_array($status, ['active', 'inactive'], true) ? $status : $fallback;
    }
}
