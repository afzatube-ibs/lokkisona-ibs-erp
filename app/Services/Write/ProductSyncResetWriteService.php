<?php

namespace App\Services\Write;

use App\ActivityLog;
use App\Auth;
use App\Database\Connection;
use App\ReadFoundation\WriteGate;
use App\Repositories\Write\ProductCostHistoryWriteRepository;
use App\Repositories\Write\ProductStockHistoryWriteRepository;
use App\Repositories\Write\ProductVariantWriteRepository;
use App\Repositories\Write\ProductWriteRepository;

/**
 * ERP-only purge of OpenCart/demo product sync data (v1.8.5). No OpenCart API calls.
 */
class ProductSyncResetWriteService
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

    public function reset(array $input = []): WriteResult
    {
        if (!$this->isOwnerOrAdmin()) {
            return WriteResult::fail('Product sync reset is available to owner/admin only.');
        }

        if (!WriteGate::productSyncImport()['ready']) {
            return WriteResult::fail(WriteGate::WARNING_MESSAGE);
        }

        $confirmed = in_array((string) ($input['reset_confirmation'] ?? ''), ['1', 'on', 'yes'], true);
        if (!$confirmed) {
            return WriteResult::fail('Confirmation checkbox is required.');
        }

        $this->clearProductSyncSession();

        if (!$this->products->tableExists()) {
            ActivityLog::record('product_sync_reset', 'Product sync ERP data reset (session only — product table missing)');

            return WriteResult::ok('Product sync data reset from ERP. Load real supplier product preview again.');
        }

        $productIds = $this->products->listSyncedProductIds();
        if ($productIds === []) {
            ActivityLog::record('product_sync_reset', 'Product sync ERP data reset (no synced products in database)');

            return WriteResult::ok('Product sync data reset from ERP. Load real supplier product preview again.');
        }

        try {
            $pdo = Connection::pdo();
            $pdo->beginTransaction();

            $stats = [
                'stock_history' => $this->stockHistory->deleteForProductIds($productIds),
                'cost_history' => $this->costHistory->deleteForProductIds($productIds),
                'variants' => $this->variants->deleteForProductIds($productIds),
                'products' => $this->products->deleteByIds($productIds),
            ];

            $pdo->commit();
        } catch (\Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }

            return WriteResult::fail('Product sync reset failed: ' . $e->getMessage());
        }

        ActivityLog::record('product_sync_reset', 'Product sync ERP data reset', $stats);

        return WriteResult::ok('Product sync data reset from ERP. Load real supplier product preview again.');
    }

    public function clearProductSyncSession(): void
    {
        unset($_SESSION['ibs_product_sync_preview']);
    }

    private function isOwnerOrAdmin(): bool
    {
        return in_array(Auth::role(), ['owner', 'admin'], true);
    }
}
