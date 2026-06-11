<?php

namespace App\Services\Write;

use App\ActivityLog;
use App\Auth;
use App\Domain\ProductControlAdjustment;
use App\Domain\ProductControlAdjustmentReason;
use App\Domain\ProductControlHistoryNote;
use App\Domain\ProductControlIbsCategory;
use App\Repositories\UserRepository;
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
    private UserRepository $users;

    public function __construct(
        ?ProductWriteRepository $products = null,
        ?ProductVariantWriteRepository $variants = null,
        ?ProductCostHistoryWriteRepository $costHistory = null,
        ?ProductStockHistoryWriteRepository $stockHistory = null,
        ?UserRepository $users = null
    ) {
        $this->products = $products ?? new ProductWriteRepository();
        $this->variants = $variants ?? new ProductVariantWriteRepository();
        $this->costHistory = $costHistory ?? new ProductCostHistoryWriteRepository();
        $this->stockHistory = $stockHistory ?? new ProductStockHistoryWriteRepository();
        $this->users = $users ?? new UserRepository();
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

        $variantRows = $input['variants'] ?? [];
        if (is_string($variantRows)) {
            $decoded = json_decode($variantRows, true);
            $variantRows = is_array($decoded) ? $decoded : [];
        }
        $hasVariants = is_array($variantRows) && $variantRows !== [];

        $productCostMeta = $this->metaFromInput($input, 'cost_meta');
        $productStockMeta = $this->metaFromInput($input, 'stock_meta');
        $metaError = ProductControlAdjustmentReason::validateMeta($productCostMeta)
            ?? ProductControlAdjustmentReason::validateMeta($productStockMeta);
        if ($metaError !== null) {
            return WriteResult::fail($metaError);
        }

        if ($hasVariants) {
            foreach ($variantRows as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $variantMetaError = ProductControlAdjustmentReason::validateMeta($this->metaFromRow($row, 'cost_meta'))
                    ?? ProductControlAdjustmentReason::validateMeta($this->metaFromRow($row, 'stock_meta'));
                if ($variantMetaError !== null) {
                    return WriteResult::fail($variantMetaError);
                }
            }
        }

        $productCost = $hasVariants
            ? ($product['product_cost'] !== null ? (float) $product['product_cost'] : null)
            : $this->resolveCostValue($product, $input, 'product_cost', 'cost_meta');
        $productStock = $hasVariants
            ? (int) ($product['vendor_stock'] ?? 0)
            : $this->resolveStockValue($product, $input, 'vendor_stock', 'stock_meta');

        if ($productStock < 0) {
            return WriteResult::fail('Vendor stock cannot be negative.');
        }

        $supplierFields = [
            'supplier_model' => array_key_exists('supplier_model', $input)
                ? $this->nullableString($input, 'supplier_model')
                : ($product['supplier_model'] ?? null),
            'product_cost' => $productCost,
            'vendor_stock' => $productStock,
            'low_warning_threshold' => array_key_exists('low_warning_threshold', $input)
                ? $this->nullableInt($input, 'low_warning_threshold', $product['low_warning_threshold'] ?? null)
                : ($product['low_warning_threshold'] !== null ? (int) $product['low_warning_threshold'] : null),
            'status' => $this->status($input, (string) ($product['status'] ?? 'active')),
        ];

        $categoryWarning = null;

        if ($this->products->supplierProductCategoryColumnReady()) {
            if (array_key_exists('supplier_product_category', $input)) {
                $supplierId = (int) ($product['supplier_id'] ?? 0);
                $existingCategories = $this->products->distinctSupplierProductCategories(
                    $supplierId > 0 ? $supplierId : null
                );
                $resolved = ProductControlIbsCategory::resolve(
                    $this->nullableString($input, 'supplier_product_category'),
                    $existingCategories
                );
                $supplierFields['supplier_product_category'] = $resolved['value'];
                $categoryWarning = $resolved['warning'];
            } else {
                $supplierFields['supplier_product_category'] = $product['supplier_product_category'] ?? null;
            }
        }

        if ($this->products->supplierNoteColumnReady() && array_key_exists('supplier_note', $input)) {
            $supplierFields['supplier_note'] = $this->nullableString($input, 'supplier_note');
        }

        $this->products->updateSupplierControlFields($productId, $supplierFields);

        if (array_key_exists('supplier_id', $input) && trim((string) $input['supplier_id']) !== '') {
            $supplierId = (int) $input['supplier_id'];
            $this->products->updateSupplierAssignment($productId, $supplierId > 0 ? $supplierId : null);
        }

        $this->recordProductHistoryIfChanged(
            $productId,
            $product,
            $supplierFields,
            $productCostMeta,
            $productStockMeta
        );

        $updatedVariants = 0;
        if ($hasVariants && $this->variants->tableExists()) {
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

                $newCost = $this->resolveCostValue($existing, $row, 'product_cost', 'cost_meta');
                $newStock = $this->resolveStockValue($existing, $row, 'vendor_stock', 'stock_meta');
                if ($newStock < 0) {
                    return WriteResult::fail('Vendor stock cannot be negative for variant #' . $variantId . '.');
                }

                $variantFields = [
                    'supplier_model' => array_key_exists('supplier_model', $row)
                        ? $this->nullableString($row, 'supplier_model')
                        : ($existing['supplier_model'] ?? null),
                    'product_cost' => $newCost,
                    'vendor_stock' => $newStock,
                    'status' => $this->status($row, (string) ($existing['status'] ?? 'active')),
                ];

                if ($this->products->supplierNoteColumnReady() && array_key_exists('supplier_note', $row)) {
                    $variantFields['supplier_note'] = $this->nullableString($row, 'supplier_note');
                }

                $this->variants->updateSupplierControlFields($variantId, $variantFields);
                $this->recordVariantHistoryIfChanged(
                    $productId,
                    $variantId,
                    $existing,
                    $variantFields,
                    $this->metaFromRow($row, 'cost_meta'),
                    $this->metaFromRow($row, 'stock_meta')
                );
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
        if ($categoryWarning !== null && $categoryWarning !== '') {
            $message .= ' ' . $categoryWarning;
        }

        return WriteResult::ok($message, $productId);
    }

    /**
     * @param array<string, mixed> $existing
     * @param array<string, mixed> $input
     */
    private function resolveCostValue(array $existing, array $input, string $valueKey, string $metaKey): ?float
    {
        $meta = $this->metaFromRow($input, $metaKey);
        $old = $existing['product_cost'] !== null && $existing['product_cost'] !== ''
            ? (float) $existing['product_cost']
            : null;

        if ($meta !== null) {
            $type = (string) ($meta['type'] ?? 'direct');
            $amount = (float) ($meta['amount'] ?? 0);

            return ProductControlAdjustment::applyCost($old, $type, $amount);
        }

        if (!array_key_exists($valueKey, $input)) {
            return $old;
        }

        return $this->nullableDecimal($input, $valueKey, $old);
    }

    /**
     * @param array<string, mixed> $existing
     * @param array<string, mixed> $input
     */
    private function resolveStockValue(array $existing, array $input, string $valueKey, string $metaKey): int
    {
        $meta = $this->metaFromRow($input, $metaKey);
        $old = (int) ($existing['vendor_stock'] ?? 0);

        if ($meta !== null) {
            $type = (string) ($meta['type'] ?? 'direct');
            $amount = (int) ($meta['amount'] ?? 0);

            return ProductControlAdjustment::applyStock($old, $type, $amount);
        }

        if (!array_key_exists($valueKey, $input)) {
            return $old;
        }

        return max(0, (int) ($input[$valueKey] ?? 0));
    }

    /**
     * @param array<string, mixed> $input
     * @return array{type: string, amount: float|int, note: string}|null
     */
    private function metaFromInput(array $input, string $key): ?array
    {
        return $this->metaFromRow($input, $key);
    }

    /**
     * @param array<string, mixed> $row
     * @return array{type: string, amount: float|int, note: string}|null
     */
    private function metaFromRow(array $row, string $key): ?array
    {
        if (!isset($row[$key])) {
            return null;
        }

        $meta = $row[$key];
        if (is_string($meta)) {
            $decoded = json_decode($meta, true);
            $meta = is_array($decoded) ? $decoded : null;
        }

        if (!is_array($meta) || empty($meta['type'])) {
            return null;
        }

        return [
            'type' => (string) $meta['type'],
            'amount' => $meta['amount'] ?? 0,
            'note' => trim((string) ($meta['note'] ?? '')),
        ];
    }

    /**
     * @param array<string, mixed> $product
     * @param array<string, mixed> $newFields
     * @param array{type: string, amount: float|int, note: string}|null $costMeta
     * @param array{type: string, amount: float|int, note: string}|null $stockMeta
     */
    private function recordProductHistoryIfChanged(
        int $productId,
        array $product,
        array $newFields,
        ?array $costMeta,
        ?array $stockMeta
    ): void {
        if (!$this->costHistory->tableExists() || !$this->stockHistory->tableExists()) {
            return;
        }

        $oldCost = $product['product_cost'] !== null && $product['product_cost'] !== ''
            ? (float) $product['product_cost']
            : null;
        $newCost = $newFields['product_cost'] ?? $oldCost;
        $oldStock = (int) ($product['vendor_stock'] ?? 0);
        $newStock = (int) ($newFields['vendor_stock'] ?? $oldStock);
        $supplierId = $product['supplier_id'] !== null ? (int) $product['supplier_id'] : null;
        $changedBy = $this->resolveChangedById();

        $this->insertCostHistoryIfChanged(
            $productId,
            null,
            $supplierId,
            $oldCost,
            $newCost,
            $costMeta,
            $changedBy
        );
        $this->insertStockHistoryIfChanged(
            $productId,
            null,
            $supplierId,
            $oldStock,
            $newStock,
            $stockMeta,
            $changedBy
        );
    }

    /**
     * @param array<string, mixed> $existing
     * @param array<string, mixed> $newFields
     * @param array{type: string, amount: float|int, note: string}|null $costMeta
     * @param array{type: string, amount: float|int, note: string}|null $stockMeta
     */
    private function recordVariantHistoryIfChanged(
        int $productId,
        int $variantId,
        array $existing,
        array $newFields,
        ?array $costMeta,
        ?array $stockMeta
    ): void {
        if (!$this->costHistory->tableExists() || !$this->stockHistory->tableExists()) {
            return;
        }

        $oldCost = $existing['product_cost'] !== null && $existing['product_cost'] !== ''
            ? (float) $existing['product_cost']
            : null;
        $newCost = $newFields['product_cost'] ?? $oldCost;
        $oldStock = (int) ($existing['vendor_stock'] ?? 0);
        $newStock = (int) ($newFields['vendor_stock'] ?? $oldStock);
        $changedBy = $this->resolveChangedById();

        $this->insertCostHistoryIfChanged($productId, $variantId, null, $oldCost, $newCost, $costMeta, $changedBy);
        $this->insertStockHistoryIfChanged($productId, $variantId, null, $oldStock, $newStock, $stockMeta, $changedBy);
    }

    private function insertCostHistoryIfChanged(
        int $productId,
        ?int $variantId,
        ?int $supplierId,
        ?float $oldCost,
        ?float $newCost,
        ?array $meta,
        ?int $changedBy
    ): void {
        $costChanged = $oldCost === null && $newCost !== null
            || ($oldCost !== null && $newCost !== null && abs((float) $newCost - (float) $oldCost) > 0.0001)
            || ($oldCost !== null && $newCost === null);

        if (!$costChanged) {
            return;
        }

        $type = $meta['type'] ?? 'direct';
        $delta = (string) ProductControlAdjustment::costDelta($oldCost, $newCost);
        $userNote = $meta['note'] ?? '';

        $this->costHistory->insert([
            'product_id' => $productId,
            'product_variant_id' => $variantId,
            'supplier_id' => $supplierId,
            'old_cost' => $oldCost,
            'new_cost' => $newCost,
            'changed_by' => $changedBy,
            'note' => ProductControlHistoryNote::format('supplier_cost', (string) $type, $delta, (string) $userNote),
        ]);
    }

    private function insertStockHistoryIfChanged(
        int $productId,
        ?int $variantId,
        ?int $supplierId,
        int $oldStock,
        int $newStock,
        ?array $meta,
        ?int $changedBy
    ): void {
        if ($newStock === $oldStock) {
            return;
        }

        $type = $meta['type'] ?? 'direct';
        $delta = (string) ProductControlAdjustment::stockDelta($oldStock, $newStock);
        $userNote = $meta['note'] ?? '';

        $this->stockHistory->insert([
            'product_id' => $productId,
            'product_variant_id' => $variantId,
            'supplier_id' => $supplierId,
            'old_stock' => $oldStock,
            'new_stock' => $newStock,
            'change_type' => (string) $type,
            'changed_by' => $changedBy,
            'note' => ProductControlHistoryNote::format('vendor_stock', (string) $type, $delta, (string) $userNote),
        ]);
    }

    private function resolveChangedById(): ?int
    {
        $username = Auth::user();
        if ($username === null || $username === '') {
            return null;
        }

        if (!$this->users->tableExists()) {
            return null;
        }

        $user = $this->users->findByUsername((string) $username);
        if ($user === null) {
            return null;
        }

        $userId = (int) ($user['user_id'] ?? 0);

        return $userId > 0 ? $userId : null;
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
