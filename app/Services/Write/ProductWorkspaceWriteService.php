<?php

namespace App\Services\Write;

use App\ActivityLog;
use App\Repositories\Write\ProductVariantWriteRepository;
use App\Repositories\Write\ProductWriteRepository;

class ProductWorkspaceWriteService
{
    private ProductWriteRepository $products;
    private ProductVariantWriteRepository $variants;

    public function __construct(
        ?ProductWriteRepository $products = null,
        ?ProductVariantWriteRepository $variants = null
    ) {
        $this->products = $products ?? new ProductWriteRepository();
        $this->variants = $variants ?? new ProductVariantWriteRepository();
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

        $this->products->updateSupplierControlFields($productId, [
            'supplier_model' => array_key_exists('supplier_model', $input)
                ? $this->nullableString($input, 'supplier_model')
                : ($product['supplier_model'] ?? null),
            'supplier_product_category' => array_key_exists('supplier_product_category', $input)
                ? $this->nullableString($input, 'supplier_product_category')
                : ($product['supplier_product_category'] ?? null),
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
        ]);

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

                $this->variants->updateSupplierControlFields($variantId, [
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
                ]);
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
