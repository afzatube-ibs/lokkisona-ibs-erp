<?php

namespace App\Services\Write;

use App\ActivityLog;
use App\Repositories\Write\ProductVariantWriteRepository;
use App\Repositories\Write\ProductWriteRepository;

class ProductVariantWriteService
{
    private ProductVariantWriteRepository $repository;
    private ProductWriteRepository $products;

    public function __construct(
        ?ProductVariantWriteRepository $repository = null,
        ?ProductWriteRepository $products = null
    ) {
        $this->repository = $repository ?? new ProductVariantWriteRepository();
        $this->products = $products ?? new ProductWriteRepository();
    }

    public function tableReady(): bool
    {
        return $this->repository->tableExists() && $this->products->tableExists();
    }

    public function create(array $input): WriteResult
    {
        if (!$this->repository->tableExists()) {
            return WriteResult::fail('Product variant table not available. Apply migration 0003 first.');
        }

        if (!$this->products->tableExists()) {
            return WriteResult::fail('Product table not available. Apply migration 0003 first.');
        }

        $productId = (int) ($input['product_id'] ?? 0);
        if ($productId <= 0) {
            return WriteResult::fail('Product is required.');
        }

        if ($this->products->find($productId) === null) {
            return WriteResult::fail('Selected product does not exist.');
        }

        $optionName = trim((string) ($input['option_name'] ?? ''));
        if ($optionName === '') {
            return WriteResult::fail('Option Name is required.');
        }

        $optionValue = trim((string) ($input['option_value'] ?? ''));
        if ($optionValue === '') {
            return WriteResult::fail('Option Value is required.');
        }

        $costResult = $this->nonNegativeDecimal($input, 'product_cost', 'Product Cost', true);
        if ($costResult instanceof WriteResult) {
            return $costResult;
        }

        $stockResult = $this->nonNegativeInt($input, 'vendor_stock', 'Vendor Stock', true);
        if ($stockResult instanceof WriteResult) {
            return $stockResult;
        }

        $lowWarningResult = $this->nonNegativeInt($input, 'low_warning_threshold', 'Low Warning', false);
        if ($lowWarningResult instanceof WriteResult) {
            return $lowWarningResult;
        }

        $id = $this->repository->create([
            'product_id' => $productId,
            'option_name' => $optionName,
            'option_value' => $optionValue,
            'supplier_model' => trim((string) ($input['supplier_model'] ?? '')) ?: null,
            'product_cost' => $costResult,
            'vendor_stock' => $stockResult,
            'status' => $this->status($input),
        ]);

        if ($lowWarningResult !== null) {
            $this->products->updateLowWarningThreshold($productId, $lowWarningResult);
        }

        ActivityLog::record('product_variant_created', 'Product variant created', [
            'product_variant_id' => $id,
            'product_id' => $productId,
        ]);

        return WriteResult::ok('Product variant/option saved.', $id);
    }

    public function applyEdit(int $id, array $input): WriteResult
    {
        if (!$this->tableReady()) {
            return WriteResult::fail('Product variant not found.');
        }

        $existing = $this->repository->find($id);
        if ($existing === null) {
            return WriteResult::fail('Product variant not found.');
        }

        $optionName = trim((string) ($input['option_name'] ?? ''));
        if ($optionName === '') {
            return WriteResult::fail('Option Name is required.');
        }

        $optionValue = trim((string) ($input['option_value'] ?? ''));
        if ($optionValue === '') {
            return WriteResult::fail('Option Value is required.');
        }

        $costResult = $this->nonNegativeDecimal($input, 'product_cost', 'Product Cost', false);
        if ($costResult instanceof WriteResult) {
            return $costResult;
        }

        $stockResult = $this->nonNegativeInt($input, 'vendor_stock', 'Vendor Stock', false);
        if ($stockResult instanceof WriteResult) {
            return $stockResult;
        }

        $existing = $this->repository->find($id);
        if ($existing === null) {
            return WriteResult::fail('Product variant not found.');
        }

        $this->repository->update($id, [
            'option_name' => $optionName,
            'option_value' => $optionValue,
            'supplier_model' => trim((string) ($input['supplier_model'] ?? '')) ?: null,
            'product_cost' => $costResult ?? ($existing['product_cost'] !== null ? (float) $existing['product_cost'] : null),
            'vendor_stock' => $stockResult ?? (int) ($existing['vendor_stock'] ?? 0),
            'status' => $this->status($input),
        ]);

        ActivityLog::record('product_variant_updated', 'Product variant updated', ['product_variant_id' => $id]);

        return WriteResult::ok('Product variant updated.', $id);
    }

    private function status(array $input): string
    {
        $status = trim((string) ($input['status'] ?? 'active'));

        return in_array($status, ['active', 'inactive'], true) ? $status : 'active';
    }

    /**
     * @return WriteResult|float|null
     */
    private function nonNegativeDecimal(array $input, string $key, string $label, bool $required)
    {
        $raw = $input[$key] ?? null;
        if ($raw === '' || $raw === null) {
            if ($required) {
                return WriteResult::fail($label . ' is required and must be 0 or greater.');
            }

            return null;
        }

        if (!is_numeric($raw)) {
            return WriteResult::fail($label . ' must be a valid number.');
        }

        $value = round((float) $raw, 2);
        if ($value < 0) {
            return WriteResult::fail($label . ' must be 0 or greater.');
        }

        return $value;
    }

    /**
     * @return WriteResult|int|null
     */
    private function nonNegativeInt(array $input, string $key, string $label, bool $required)
    {
        $raw = $input[$key] ?? null;
        if ($raw === '' || $raw === null) {
            if ($required) {
                return 0;
            }

            return null;
        }

        if (!is_numeric($raw)) {
            return WriteResult::fail($label . ' must be a valid whole number.');
        }

        $value = (int) $raw;
        if ($value < 0) {
            return WriteResult::fail($label . ' must be 0 or greater.');
        }

        return $value;
    }
}
