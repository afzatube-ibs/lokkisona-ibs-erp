<?php

namespace App\Controllers;

use App\ActivityLog;
use App\Csrf;
use App\Database;
use App\Database\TableName;
use App\Models\Product;
use App\Models\ProductCostHistory;
use App\Models\ProductStockHistory;
use App\Models\ProductVariant;
use App\Permission;
use App\ReadFoundation\WriteGate;
use App\Services\Read\OpenCartReadClient;
use App\Services\ReadOnly\ProductControlCatalogReadService;
use App\Services\ReadOnly\ProductControlHistoryReadService;
use App\Services\ReadOnly\ProductControlListReadService;
use App\Services\ReadOnly\ProductCostHistoryReadService;
use App\Services\ReadOnly\ProductReadService;
use App\Services\ReadOnly\ProductStockHistoryReadService;
use App\Services\ReadOnly\ProductVariantReadService;
use App\Services\ReadOnly\SupplierProductFilter;
use App\Services\ReadOnly\SupplierReadService;
use App\Services\Write\ProductCostStockWriteService;
use App\Services\Write\ProductWorkspaceWriteService;
use App\Services\Write\ProductWriteService;
use App\Services\Write\SyncPreviewWriteService;
use App\SupplierContext;
use App\Support\RequestTimer;
use App\Repositories\ProductVariantRepository;

class ProductControlController extends Controller
{
    public function index()
    {
        $this->authorize('product_control.view');
        ActivityLog::record('product_control_access', 'Product Control read foundation page viewed');

        $timer = new RequestTimer();
        $isSupplierView = SupplierContext::isSupplier();
        $supplierId = $isSupplierView ? SupplierContext::supplierId() : 0;
        $listService = new ProductControlListReadService();
        $tableReady = $listService->tableExists();

        $canManageSync = Permission::can('sync_preview.manage');
        $productWriteGate = WriteGate::productSyncImport();
        $warehousePullAvailable = (new OpenCartReadClient())->warehouseProductPullAvailable();
        $canRefreshProducts = $canManageSync
            && !empty($productWriteGate['ready'])
            && $warehousePullAvailable
            && !$isSupplierView;

        $catalogFilters = [
            'q' => $_GET['q'] ?? '',
            'product_id' => $_GET['product_id'] ?? '',
            'product_name' => $_GET['product_name'] ?? '',
            'model' => $_GET['model'] ?? '',
            'supplier_model' => $_GET['supplier_model'] ?? '',
            'category' => $_GET['category'] ?? '',
            'type' => $_GET['type'] ?? 'all',
            'sort' => $_GET['sort'] ?? 'product_id_asc',
            'chip' => $_GET['chip'] ?? 'all',
            'per_page' => $_GET['per_page'] ?? ProductControlListReadService::PER_PAGE,
        ];
        $catalogPage = max(1, (int) ($_GET['page'] ?? 1));

        $productCatalog = $listService->listPage($catalogFilters, $catalogPage, $supplierId, $isSupplierView, $timer);
        $summaryKpis = $productCatalog['summary_kpis'] ?? [];
        $freshness = $listService->snapshotFreshness($supplierId);
        $categoryOptions = $listService->listCategoryOptions($supplierId);
        $timer->lap('total');
        $timer->log('product-control index');

        $productReadInventory = [
            'table_exists' => $tableReady,
            'status' => $tableReady ? 'ok' : 'table_missing',
            'status_message' => $tableReady
                ? 'Local ERP product snapshot ready.'
                : 'Table `' . TableName::forModel(Product::class) . '` not available — migration `0003_business_sources_suppliers_products.sql` not applied yet.',
        ];

        $this->render('product-control.index', [
            'pageTitle' => 'Product Control',
            'breadcrumbs' => [
                ['label' => 'Operations', 'active' => false],
                ['label' => 'Product Control', 'active' => true],
            ],
            'accessMode' => Permission::accessMode(),
            'productReadInventory' => $productReadInventory,
            'productCatalog' => $productCatalog,
            'summaryKpis' => $summaryKpis,
            'lastCatalogSyncAt' => $freshness['last_synced_at'] ?? '',
            'snapshotIsStale' => !empty($freshness['is_stale']),
            'sourceSyncLabel' => $this->productSyncSourceLabelConfigOnly(),
            'catalogFilters' => $productCatalog['filters'] ?? [],
            'categoryOptions' => $categoryOptions,
            'catalogPagination' => $productCatalog['pagination'] ?? [],
            'defaultBusinessSourceId' => (int) config('opencart.business_source_id', 1),
            'canManage' => Permission::can('product_control.manage'),
            'canViewHealth' => Permission::can('health.view'),
            'canViewLogs' => Permission::can('activity_log.view'),
            'flashSuccess' => $this->pullFlash('success'),
            'flashError' => $this->pullFlash('error'),
            'csrfField' => Csrf::field(),
            'writeGateProductEditReady' => WriteGate::productCreateForm()['ready'],
            'isSupplierView' => $isSupplierView,
            'boundSupplierId' => $isSupplierView ? SupplierContext::supplierId() : 0,
            'supplierSelectOptions' => $this->supplierSelectOptions(),
            'writeGateSupplierNote' => WriteGate::supplierProductNoteColumn(),
            'writeGateSupplierNoteReady' => WriteGate::supplierProductNoteColumn()['ready'],
            'canRefreshProducts' => $canRefreshProducts,
            'warehouseProductPullAvailable' => $warehousePullAvailable,
            'productWriteGateReady' => $productWriteGate['ready'],
            'tableReady' => $tableReady,
            'timingDiagnostics' => $timer->isEnabled() ? $timer->laps() : null,
        ]);
    }

    public function workspaceJson()
    {
        $this->authorize('product_control.view');
        $timer = new RequestTimer();
        $productId = (int) ($_GET['id'] ?? 0);
        if ($productId <= 0 || !$this->assertProductOwnedBySupplier($productId)) {
            $this->jsonResponse(['ok' => false, 'message' => 'Product not found.'], 404);
        }

        $product = (new ProductReadService())->findById($productId);
        if ($product === null || !$this->isSyncedCatalogProduct($product)) {
            $this->jsonResponse(['ok' => false, 'message' => 'Product not in supplier catalog snapshot.'], 404);
        }

        $isSupplierView = SupplierContext::isSupplier();
        $variants = (new ProductVariantRepository())->findByProductId($productId);
        $views = (new ProductControlCatalogReadService())->buildProductViews($product, $variants, $isSupplierView);
        $timer->lap('workspace');
        $timer->log('product-control workspace id=' . $productId);

        $this->jsonResponse([
            'ok' => true,
            'workspace' => $views['workspace'] ?? [],
            'isSupplierView' => $isSupplierView,
            'timing_ms' => $timer->isEnabled() ? $timer->laps() : null,
        ]);
    }

    public function historyJson()
    {
        $this->authorize('product_control.view');
        $timer = new RequestTimer();
        $productId = (int) ($_GET['id'] ?? 0);
        $limit = max(1, min(100, (int) ($_GET['limit'] ?? 50)));
        if ($productId <= 0 || !$this->assertProductOwnedBySupplier($productId)) {
            $this->jsonResponse(['ok' => false, 'message' => 'Product not found.'], 404);
        }

        $rows = (new ProductControlHistoryReadService())->forProduct($productId, $limit);
        $timer->lap('history');
        $timer->log('product-control history id=' . $productId);

        $this->jsonResponse([
            'ok' => true,
            'rows' => $rows,
            'timing_ms' => $timer->isEnabled() ? $timer->laps() : null,
        ]);
    }

    public function refreshProducts()
    {
        $this->authorize('sync_preview.manage');
        $this->requirePost();
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Invalid security token.');
            redirect('/product-control');
        }

        if (SupplierContext::isSupplier()) {
            $this->flash('error', 'Supplier accounts cannot refresh the product catalog.');
            redirect('/product-control');
        }

        $result = (new SyncPreviewWriteService())->refreshWarehouseProductsFromApi($_POST);
        if ($result->success) {
            ProductControlListReadService::invalidateCache();
        }
        $this->redirectWithWriteResult('/product-control', $result);
    }

    public function saveWorkspace()
    {
        $this->authorize('product_control.manage');
        $this->requirePost();
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Invalid security token.');
            redirect('/product-control');
        }

        $productId = (int) ($_POST['product_id'] ?? 0);
        if (!$this->assertProductOwnedBySupplier($productId)) {
            $this->flash('error', 'Product not found for your supplier account.');
            redirect('/product-control');
        }

        $input = $this->applySupplierProductScope($_POST);
        if (isset($input['variants']) && is_string($input['variants'])) {
            $decoded = json_decode($input['variants'], true);
            $input['variants'] = is_array($decoded) ? $decoded : [];
        } elseif (!isset($input['variants']) || !is_array($input['variants'])) {
            $input['variants'] = [];
        }

        $result = (new ProductWorkspaceWriteService())->save($productId, $input);
        if ($result->success) {
            ProductControlListReadService::invalidateCache();
        }
        $this->redirectWithWriteResult('/product-control', $result);
    }

    public function createProduct()
    {
        $this->authorize('product_control.manage');
        $this->requirePost();
        $this->flash('error', 'Manual product create is disabled. Catalog rows are synced from the live site (Pull warehouse products on Sync Preview).');
        redirect('/product-control');
    }

    public function editProduct()
    {
        $this->authorize('product_control.manage');
        $this->requirePost();
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Invalid security token.');
            redirect('/product-control');
        }
        $id = (int) ($_POST['product_id'] ?? 0);
        if (!$this->assertProductOwnedBySupplier($id)) {
            $this->flash('error', 'Product not found for your supplier account.');
            redirect('/product-control');
        }
        $input = $this->applySupplierProductScope($_POST);
        $this->redirectWithWriteResult('/product-control', (new ProductWriteService())->applyEdit($id, $input));
    }

    public function createVariant()
    {
        $this->authorize('product_control.manage');
        $this->requirePost();
        $this->flash('error', 'Manual variant create is disabled. Option lines are synced from the live site with the parent product.');
        redirect('/product-control');
    }

    public function updateCostStock()
    {
        $this->authorize('product_control.manage');
        $this->requirePost();
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Invalid security token.');
            redirect('/product-control');
        }
        $productId = (int) ($_POST['product_id'] ?? 0);
        if (!empty($_POST['product_variant_id'])) {
            $variantId = (int) $_POST['product_variant_id'];
            $productId = $this->productIdForVariant($variantId) ?: $productId;
        }
        if ($productId > 0 && !$this->assertProductOwnedBySupplier($productId)) {
            $this->flash('error', 'Product not found for your supplier account.');
            redirect('/product-control');
        }
        $service = new ProductCostStockWriteService();
        if (!empty($_POST['product_variant_id'])) {
            $result = $service->updateVariantCostStock((int) $_POST['product_variant_id'], $_POST);
        } else {
            $result = $service->updateProductCostStock($productId, $_POST);
        }
        if (!empty($_POST['product_variant_id'])) {
            $result = $service->updateVariantCostStock((int) $_POST['product_variant_id'], $_POST);
        } else {
            $result = $service->updateProductCostStock($productId, $_POST);
        }
        if ($result->success) {
            ProductControlListReadService::invalidateCache();
        }
        $this->redirectWithWriteResult('/product-control', $result);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function jsonResponse(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function productSyncSourceLabelConfigOnly(): string
    {
        $mode = strtolower((string) config('opencart.source_mode', 'demo'));

        return match ($mode) {
            'staging' => 'Staging OpenCart read-only',
            'live' => 'Live OpenCart read-only',
            default => 'Demo OpenCart read-only',
        };
    }

    /**
     * @param array<string, mixed> $product
     */
    private function isSyncedCatalogProduct(array $product): bool
    {
        return (new SupplierProductFilter())->isSupplierSyncedProduct($product);
    }

    private function latestCatalogSyncTime(array $rows): string
    {
        $latest = '';
        foreach ($rows as $row) {
            $synced = trim((string) ($row['last_synced_at'] ?? ''));
            if ($synced === '') {
                continue;
            }
            if ($latest === '' || strcmp($synced, $latest) > 0) {
                $latest = $synced;
            }
        }

        return $latest;
    }

    private function productSyncSourceLabel(?array $syncStatus): string
    {
        $mode = strtolower((string) ($syncStatus['source_mode'] ?? config('opencart.source_mode', 'demo')));
        return match ($mode) {
            'staging' => 'Staging OpenCart read-only',
            'live' => 'Live OpenCart read-only',
            default => 'Demo OpenCart read-only',
        };
    }

    private function historyRowsByProduct(array $rows): array
    {
        $grouped = [];
        foreach ($rows as $row) {
            $productId = (int) ($row['product_id'] ?? 0);
            if ($productId <= 0) {
                continue;
            }
            if (!isset($grouped[$productId])) {
                $grouped[$productId] = [];
            }
            if (count($grouped[$productId]) >= 15) {
                continue;
            }
            $grouped[$productId][] = $row;
        }

        return $grouped;
    }

    private function productSelectOptionsFromInventory(array $productReadInventory): array
    {
        if (!in_array($productReadInventory['status'] ?? '', ['ok', 'empty'], true)) {
            return [];
        }

        if (empty($productReadInventory['rows'])) {
            return [];
        }

        $options = [];
        foreach ($productReadInventory['rows'] as $row) {
            $id = (int) ($row['product_id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $name = trim((string) ($row['product_name'] ?? ''));
            $label = $name !== '' ? $name . ' (#' . $id . ')' : 'Product #' . $id;
            $options[] = ['id' => $id, 'label' => $label];
        }

        return $options;
    }


    private function buildCostStockHistoryDisplay(array $productReadInventory, array $productVariantReadInventory, array $costHistoryInventory, array $stockHistoryInventory): array
    {
        $display = [
            'status_message' => 'Cost/stock audit history is not fully ready yet.',
            'cost_status_message' => $costHistoryInventory['status_message'] ?? 'Cost history unavailable.',
            'stock_status_message' => $stockHistoryInventory['status_message'] ?? 'Stock history unavailable.',
            'cost_table_exists' => (bool) ($costHistoryInventory['table_exists'] ?? false),
            'stock_table_exists' => (bool) ($stockHistoryInventory['table_exists'] ?? false),
            'rows' => [],
        ];

        $productNames = [];
        foreach ($productReadInventory['rows'] ?? [] as $product) {
            $productId = (int) ($product['product_id'] ?? 0);
            if ($productId > 0) {
                $productNames[$productId] = trim((string) ($product['product_name'] ?? ''));
            }
        }

        $variantLabels = [];
        foreach ($productVariantReadInventory['rows'] ?? [] as $variant) {
            $variantId = (int) ($variant['product_variant_id'] ?? 0);
            if ($variantId <= 0) {
                continue;
            }

            $optionName = trim((string) ($variant['option_name'] ?? ''));
            $optionValue = trim((string) ($variant['option_value'] ?? ''));
            $supplierModel = trim((string) ($variant['supplier_model'] ?? ''));

            $label = trim($optionName . ': ' . $optionValue, ': ');
            if ($supplierModel !== '') {
                $label = $label !== '' ? $label . ' / ' . $supplierModel : $supplierModel;
            }

            $variantLabels[$variantId] = $label !== '' ? $label : 'Variant #' . $variantId;
        }

        $groups = [];

        foreach ($costHistoryInventory['rows'] ?? [] as $row) {
            $productId = (int) ($row['product_id'] ?? 0);
            $variantId = (int) ($row['product_variant_id'] ?? 0);
            $createdAt = (string) ($row['created_at'] ?? '');
            $note = (string) ($row['note'] ?? '');
            $key = $createdAt . '|' . $productId . '|' . $variantId . '|' . md5($note);

            if (!isset($groups[$key])) {
                $groups[$key] = $this->emptyHistoryGroup($productId, $variantId, $createdAt, $note, $productNames, $variantLabels);
            }

            $groups[$key]['old_cost'] = $row['old_cost'] ?? '';
            $groups[$key]['new_cost'] = $row['new_cost'] ?? '';
            $groups[$key]['source'] = trim($groups[$key]['source'] . ' Cost');
            $groups[$key]['sort_id'] = max((int) $groups[$key]['sort_id'], (int) ($row['product_cost_history_id'] ?? 0));
        }

        foreach ($stockHistoryInventory['rows'] ?? [] as $row) {
            $productId = (int) ($row['product_id'] ?? 0);
            $variantId = (int) ($row['product_variant_id'] ?? 0);
            $createdAt = (string) ($row['created_at'] ?? '');
            $note = (string) ($row['note'] ?? '');
            $key = $createdAt . '|' . $productId . '|' . $variantId . '|' . md5($note);

            if (!isset($groups[$key])) {
                $groups[$key] = $this->emptyHistoryGroup($productId, $variantId, $createdAt, $note, $productNames, $variantLabels);
            }

            $groups[$key]['old_stock'] = $row['old_stock'] ?? '';
            $groups[$key]['new_stock'] = $row['new_stock'] ?? '';
            $groups[$key]['change_type'] = $row['change_type'] ?? '';
            $groups[$key]['source'] = trim($groups[$key]['source'] . ' Stock');
            $groups[$key]['sort_id'] = max((int) $groups[$key]['sort_id'], (int) ($row['product_stock_history_id'] ?? 0));
        }

        $rows = array_values($groups);
        usort($rows, function (array $a, array $b): int {
            $timeCompare = strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? ''));
            if ($timeCompare !== 0) {
                return $timeCompare;
            }

            return ((int) ($b['sort_id'] ?? 0)) <=> ((int) ($a['sort_id'] ?? 0));
        });

        $display['rows'] = array_slice($rows, 0, 20);

        if (!empty($display['rows'])) {
            $display['status_message'] = 'Audit confirmation ready: latest cost/stock history notes are visible below.';
        } elseif ($display['cost_table_exists'] || $display['stock_table_exists']) {
            $display['status_message'] = 'History tables are ready, but no audit history row is available yet. Save cost/stock with a note to create one.';
        }

        return $display;
    }

    private function emptyHistoryGroup(int $productId, int $variantId, string $createdAt, string $note, array $productNames, array $variantLabels): array
    {
        return [
            'product_id' => $productId,
            'product_name' => $productNames[$productId] ?? ('Product #' . $productId),
            'product_variant_id' => $variantId,
            'variant_label' => $variantId > 0 ? ($variantLabels[$variantId] ?? ('Variant #' . $variantId)) : 'Product level',
            'old_cost' => '',
            'new_cost' => '',
            'old_stock' => '',
            'new_stock' => '',
            'change_type' => '',
            'note' => $note,
            'created_at' => $createdAt,
            'source' => '',
            'sort_id' => 0,
        ];
    }
    private function buildProductDisplayFromInventory(array $productReadInventory): array
    {
        $display = [
            'status' => $productReadInventory['status'] ?? 'error',
            'status_message' => $productReadInventory['status_message'] ?? 'Product inventory unavailable.',
            'row_count' => (int) ($productReadInventory['row_count'] ?? 0),
            'table_exists' => (bool) ($productReadInventory['table_exists'] ?? false),
            'rows' => [],
        ];

        if (!in_array($display['status'], ['ok', 'empty'], true)) {
            return $display;
        }

        foreach ($productReadInventory['rows'] ?? [] as $product) {
            $display['rows'][] = [
                'product_id' => (int) ($product['product_id'] ?? 0),
                'product_name' => trim((string) ($product['product_name'] ?? '')),
                'supplier_model' => (string) ($product['supplier_model'] ?? ''),
                'supplier_product_category' => (string) ($product['supplier_product_category'] ?? ''),
                'product_cost' => $product['product_cost'] ?? '',
                'vendor_stock' => (int) ($product['vendor_stock'] ?? 0),
                'source_product_id' => (string) ($product['source_product_id'] ?? ''),
                'source_model' => (string) ($product['source_model'] ?? ''),
                'source_stock' => $product['source_stock'] ?? '',
                'last_synced_at' => (string) ($product['last_synced_at'] ?? ''),
                'low_warning_threshold' => $product['low_warning_threshold'] ?? '',
                'status' => (string) ($product['status'] ?? 'active'),
                'business_source_id' => $product['business_source_id'] ?? '',
                'supplier_id' => $product['supplier_id'] ?? '',
            ];
        }

        return $display;
    }

    private function buildVariantDisplayFromInventories(array $productReadInventory, array $productVariantReadInventory): array
    {
        $display = [
            'status' => $productVariantReadInventory['status'] ?? 'error',
            'status_message' => $productVariantReadInventory['status_message'] ?? 'Variant inventory unavailable.',
            'row_count' => (int) ($productVariantReadInventory['row_count'] ?? 0),
            'table_exists' => (bool) ($productVariantReadInventory['table_exists'] ?? false),
            'rows' => [],
        ];

        if (!in_array($display['status'], ['ok', 'empty'], true)) {
            return $display;
        }

        $productNames = [];
        $productCategories = [];
        foreach ($productReadInventory['rows'] ?? [] as $product) {
            $pid = (int) ($product['product_id'] ?? 0);
            if ($pid > 0) {
                $productNames[$pid] = trim((string) ($product['product_name'] ?? ''));
                $productCategories[$pid] = trim((string) ($product['supplier_product_category'] ?? ''));
            }
        }

        foreach ($productVariantReadInventory['rows'] ?? [] as $variant) {
            $productId = (int) ($variant['product_id'] ?? 0);
            $productName = $productNames[$productId] ?? '';
            $category = $productCategories[$productId] ?? '';
            $display['rows'][] = [
                'product_variant_id' => $variant['product_variant_id'] ?? '',
                'product_id' => $productId,
                'product_name' => $productName !== '' ? $productName : '(product #' . $productId . ')',
                'supplier_product_category' => $category !== '' ? $category : '—',
                'option_name' => $variant['option_name'] ?? '',
                'option_value' => $variant['option_value'] ?? '',
                'supplier_model' => $variant['supplier_model'] ?? '',
                'product_cost' => $variant['product_cost'] ?? '',
                'vendor_stock' => $variant['vendor_stock'] ?? 0,
                'status' => $variant['status'] ?? '',
            ];
        }

        return $display;
    }

    private function buildProductReadInventory()
    {
        return $this->buildEntityReadInventory(
            Product::class,
            ProductReadService::class,
            'Product',
            'ProductReadService',
            'ProductRepository',
            'product'
        );
    }

    private function buildProductVariantReadInventory()
    {
        return $this->buildEntityReadInventory(
            ProductVariant::class,
            ProductVariantReadService::class,
            'ProductVariant',
            'ProductVariantReadService',
            'ProductVariantRepository',
            'product variant'
        );
    }


    private function buildProductCostHistoryReadInventory()
    {
        return $this->buildEntityReadInventory(
            ProductCostHistory::class,
            ProductCostHistoryReadService::class,
            'ProductCostHistory',
            'ProductCostHistoryReadService',
            'ProductCostHistoryRepository',
            'product cost history',
            true
        );
    }

    private function buildProductStockHistoryReadInventory()
    {
        return $this->buildEntityReadInventory(
            ProductStockHistory::class,
            ProductStockHistoryReadService::class,
            'ProductStockHistory',
            'ProductStockHistoryReadService',
            'ProductStockHistoryRepository',
            'product stock history',
            true
        );
    }
    private function buildEntityReadInventory(
        string $modelClass,
        string $serviceClass,
        string $modelShortName,
        string $readServiceName,
        string $readRepositoryName,
        string $recordLabel,
        bool $useLatestRows = false
    ) {
        $databaseStatus = Database::check();
        $defaults = [
            'database_connected' => (bool) ($databaseStatus['connected'] ?? false),
            'service_ready' => false,
            'logical_table' => $modelClass::table(),
            'prefixed_table' => TableName::forModel($modelClass),
            'model_class' => $modelShortName,
            'primary_key' => $modelClass::primaryKey(),
            'columns' => $modelClass::columns(),
            'read_service' => $readServiceName,
            'read_repository' => $readRepositoryName,
            'table_exists' => false,
            'row_count' => 0,
            'rows' => [],
            'status' => 'error',
            'status_message' => 'Read inventory could not be loaded safely.',
        ];

        try {
            $service = new $serviceClass();
            $defaults['service_ready'] = true;

            if (!$defaults['database_connected']) {
                $defaults['status'] = 'not_connected';
                $defaults['status_message'] = 'Database not connected. Read inventory unavailable.';

                return $defaults;
            }

            $tableExists = $service->tableExists();
            $defaults['table_exists'] = $tableExists;

            if (!$tableExists) {
                $defaults['status'] = 'table_missing';
                $defaults['status_message'] = 'Table `' . $defaults['prefixed_table'] . '` not available — migration `0003_business_sources_suppliers_products.sql` not applied yet. Apply manually with the `ibs_` table prefix from config/database.php.';

                return $defaults;
            }

            $rowCount = $service->count();
            $defaults['row_count'] = $rowCount;
            $rowLimit = $recordLabel === 'product' ? 200 : ($recordLabel === 'product variant' ? 500 : 50);
            $defaults['rows'] = $useLatestRows ? $service->latest($rowLimit) : $service->all($rowLimit, 0);

            if ($rowCount === 0) {
                $defaults['status'] = 'empty';
                $defaults['status_message'] = 'Table ready. No ' . $recordLabel . ' records yet (read-only; no writes in this release).';

                return $defaults;
            }

            $defaults['status'] = 'ok';
            $defaults['status_message'] = $useLatestRows
                ? 'Showing up to 50 latest ' . $recordLabel . ' records ordered by created_at DESC (SELECT only).'
                : 'Showing up to 50 ' . $recordLabel . ' records (SELECT only).';

            return $defaults;
        } catch (\Throwable $e) {
            return $defaults;
        }
    }

    private function applySupplierProductScope(array $input): array
    {
        if (!SupplierContext::isSupplier()) {
            return $input;
        }

        $input['supplier_id'] = SupplierContext::supplierId();

        return $input;
    }

    private function assertProductOwnedBySupplier(int $productId): bool
    {
        if (!SupplierContext::isSupplier() || $productId <= 0) {
            return true;
        }

        try {
            $product = (new \App\Repositories\ProductRepository())->findById($productId);
            if ($product === null) {
                return false;
            }

            return (int) ($product['supplier_id'] ?? 0) === SupplierContext::supplierId();
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function productIdForVariant(int $variantId): int
    {
        if ($variantId <= 0) {
            return 0;
        }

        try {
            $variant = (new \App\Repositories\ProductVariantRepository())->findById($variantId);

            return (int) ($variant['product_id'] ?? 0);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function filterRowsBySupplierId(array $inventory, int $supplierId): array
    {
        $rows = $inventory['rows'] ?? [];
        $filtered = array_values(array_filter($rows, static function (array $row) use ($supplierId): bool {
            return (int) ($row['supplier_id'] ?? 0) === $supplierId;
        }));
        $inventory['rows'] = $filtered;
        $inventory['row_count'] = count($filtered);
        $inventory['status'] = $filtered === [] ? 'empty' : ($inventory['status'] ?? 'ok');

        return $inventory;
    }

    private function filterVariantRowsForSupplierProducts(array $variantInventory, array $productInventory): array
    {
        $productIds = [];
        foreach ($productInventory['rows'] ?? [] as $product) {
            $pid = (int) ($product['product_id'] ?? 0);
            if ($pid > 0) {
                $productIds[$pid] = true;
            }
        }

        $rows = $variantInventory['rows'] ?? [];
        $filtered = array_values(array_filter($rows, static function (array $row) use ($productIds): bool {
            return isset($productIds[(int) ($row['product_id'] ?? 0)]);
        }));
        $variantInventory['rows'] = $filtered;
        $variantInventory['row_count'] = count($filtered);
        $variantInventory['status'] = $filtered === [] ? 'empty' : ($variantInventory['status'] ?? 'ok');

        return $variantInventory;
    }

    private function supplierSelectOptions(): array
    {
        try {
            $service = new SupplierReadService();
            if (!$service->tableExists()) {
                return [];
            }

            $options = [];
            foreach ($service->all(100, 0) as $row) {
                $id = (int) ($row['supplier_id'] ?? 0);
                if ($id <= 0) {
                    continue;
                }
                $options[] = [
                    'supplier_id' => $id,
                    'label' => trim((string) ($row['supplier_name'] ?? 'Supplier #' . $id)),
                ];
            }

            return $options;
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function currentSupplier()
    {
        return [
            'name' => 'Iqbal & Brothers',
            'role' => 'Current primary supplier context',
            'summary' => 'Product Control planning starts with Iqbal & Brothers supplier operations and Lokkisona order workflow, but stays ready for other suppliers, sales channels, manual/offline products, and multi-business expansion.',
        ];
    }

    private function purpose()
    {
        return [
            'Catalog rows are synced from the live site only — no manual product or variant create on this page.',
            'Owner runs Pull warehouse products on Sync Preview (From Warehouse = Yes / Dispatch Location rule).',
            'Order import uses synced source_product_id to resolve cost on imported line items.',
            'Only OpenCart products marked From Warehouse = Yes belong in the ERP supplier catalog.',
            'Separate platform read-only fields (source model/stock) from supplier-editable model, sale/cost, and vendor stock.',
            'Cost and stock history support dispatch and payable cost snapshots instead of live changing values.',
        ];
    }

    private function futureSyncedStructure()
    {
        return [
            [
                'title' => 'Source product identity',
                'description' => 'Each product will keep a source product id, source/channel label, and last synced timestamp when ecommerce sync is enabled later.',
            ],
            [
                'title' => 'Platform read-only layer',
                'description' => 'OpenCart or other source model and stock values will sync in as read-only fields. Supplier operations must not overwrite synced platform data.',
            ],
            [
                'title' => 'Supplier control layer',
                'description' => 'Supplier model, product cost, vendor stock, and low warning threshold remain supplier-editable with audit history.',
            ],
            [
                'title' => 'Variant / option layer',
                'description' => 'Product variants and improved options will carry source option ids, read-only improved option model/stock, and supplier-editable cost/stock per variant.',
            ],
            [
                'title' => 'Multi-supplier readiness',
                'description' => 'The same product structure supports other suppliers, other channels, and manual/offline products without hard-coding Lokkisona or Iqbal & Brothers.',
            ],
        ];
    }

    private function editableFields()
    {
        return [
            'Supplier Model (vendor model)',
            'Supplier category (ERP reporting only)',
            'Product Cost / Sale amount (supplier ERP field)',
            'Vendor Stock (supplier ERP field)',
            'Low Warning Threshold (warning only)',
            'ERP status (active / inactive)',
            'Supplier note (ERP only, migration 0012)',
            'Variant vendor model, cost/sale, vendor stock, and note per option line',
        ];
    }

    private function readOnlyFields()
    {
        return [
            'OpenCart product name (synced from live site)',
            'OpenCart / Source Model',
            'OpenCart / Source Stock (owner/platform stock)',
            'OpenCart source product ID',
            'Option name / option value (platform structure)',
            'Last synced at',
            'Product and option images from live site',
        ];
    }

    private function businessRules()
    {
        return [
            [
                'field' => 'OpenCart / Improved Option Model',
                'rule' => 'Read-only when synced later. Supplier staff must not edit platform model values.',
            ],
            [
                'field' => 'OpenCart / Improved Option Stock',
                'rule' => 'Read-only when synced later. Platform stock remains the source-of-truth from sync.',
            ],
            [
                'field' => 'Supplier Model',
                'rule' => 'Editable by supplier operations. Used for supplier-side identification and fulfillment planning.',
            ],
            [
                'field' => 'Product Cost',
                'rule' => 'Editable with history. Each change is recorded for audit and payable planning.',
            ],
            [
                'field' => 'Vendor Stock',
                'rule' => 'Editable with history. Each change is recorded for stock planning and low-stock warnings.',
            ],
            [
                'field' => 'Low Warning',
                'rule' => 'Warning only — does not auto-block orders, dispatch, or payable workflows.',
            ],
        ];
    }

    private function historyRules()
    {
        return [
            'Product cost changes will append to product_cost_histories with who changed it, previous value, new value, and timestamp.',
            'Vendor stock changes will append to product_stock_histories with who changed it, previous value, new value, and timestamp.',
            'History records are append-only for audit. Dispatch and payable must not rely on live changing cost after a snapshot is taken.',
        ];
    }

    private function lowStockRules()
    {
        return [
            'Low warning threshold is a planning alert only.',
            'Low stock warnings do not auto-block order acceptance, dispatch, or payable processing.',
            'Warnings help supplier staff review vendor stock before fulfillment and replenishment.',
        ];
    }

    private function optionImageRules()
    {
        return [
            'Option images should later follow POIP / PIT Order Manager image reference logic.',
            'Store image references, not duplicated image files, unless a manual upload workflow is explicitly added.',
            'Variant option rows will keep an option image reference field plus a POIP/PIT reference note for order-manager compatibility.',
        ];
    }

    private function costSnapshotRule()
    {
        return [
            'title' => 'Future payable / dispatch cost snapshot',
            'summary' => 'Dispatch and payable workflows must use a cost snapshot captured at dispatch or payable time, not the live product cost that may change later.',
            'points' => [
                'Live product cost remains editable for planning and future orders.',
                'Dispatch report items and payable calculations must store the cost value used at that moment.',
                'Historical cost changes must remain visible through product_cost_histories without rewriting past dispatch or payable records.',
            ],
        ];
    }

    private function sharedStockRule()
    {
        return [
            'title' => 'Shared ERP Product / Vendor Stock Rule',
            'summary' => 'Lokkisona and Sonamoni source products can map to the same ERP product/variant with shared supplier cost and vendor stock.',
            'points' => [
                'Vendor Stock belongs to internal ERP product/variant, not to each website/source.',
                'Lokkisona.com (OpenCart) and Sonamoni.com.bd (WooCommerce) source products may map to the same ERP product/variant.',
                'Same supplier cost can be shared across mapped source products.',
                'Stock deduction later combines demand from all business sources — see Sync Preview planning foundation.',
            ],
        ];
    }

    private function plannedProductFields()
    {
        return [
            'product_id / source_product_id',
            'product name',
            'image',
            'source / channel',
            'supplier',
            'OC / source model (read-only)',
            'OC / source stock (read-only)',
            'supplier model',
            'product cost',
            'vendor stock',
            'low warning threshold',
            'status',
            'last synced at',
            'updated at',
        ];
    }

    private function plannedVariantFields()
    {
        return [
            'option / variant name',
            'option value',
            'source option id',
            'source option value id',
            'improved option model (read-only)',
            'improved option stock (read-only)',
            'supplier model',
            'product cost',
            'vendor stock',
            'option image reference',
            'POIP / PIT image reference note',
        ];
    }
}
