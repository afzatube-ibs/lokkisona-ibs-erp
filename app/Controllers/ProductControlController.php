<?php

namespace App\Controllers;

use App\ActivityLog;
use App\Database;
use App\Database\TableName;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductCostHistory;
use App\Models\ProductStockHistory;
use App\Permission;
use App\Csrf;
use App\Services\ReadOnly\ProductReadService;
use App\Services\ReadOnly\ProductVariantReadService;
use App\Services\ReadOnly\ProductCostHistoryReadService;
use App\Services\ReadOnly\ProductStockHistoryReadService;
use App\ReadFoundation\WriteGate;
use App\Services\Write\ProductCostStockWriteService;
use App\Services\Write\ProductVariantWriteService;
use App\Services\Write\ProductWriteService;

class ProductControlController extends Controller
{
    public function index()
    {
        $this->authorize('product_control.view');
        ActivityLog::record('product_control_access', 'Product Control read foundation page viewed');

        $productReadInventory = $this->buildProductReadInventory();
        $productVariantReadInventory = $this->buildProductVariantReadInventory();

        $this->render('product-control.index', [
            'pageTitle' => 'Product Control',
            'breadcrumbs' => [
                ['label' => 'Operations', 'active' => false],
                ['label' => 'Product Control', 'active' => true],
            ],
            'accessMode' => Permission::accessMode(),
            'productReadInventory' => $productReadInventory,
            'productVariantReadInventory' => $productVariantReadInventory,
            'productSelectOptions' => $this->productSelectOptionsFromInventory($productReadInventory),
            'variantDisplay' => $this->buildVariantDisplayFromInventories($productReadInventory, $productVariantReadInventory),
            'currentSupplier' => $this->currentSupplier(),
            'purpose' => $this->purpose(),
            'futureSyncedStructure' => $this->futureSyncedStructure(),
            'editableFields' => $this->editableFields(),
            'readOnlyFields' => $this->readOnlyFields(),
            'businessRules' => $this->businessRules(),
            'historyRules' => $this->historyRules(),
            'lowStockRules' => $this->lowStockRules(),
            'optionImageRules' => $this->optionImageRules(),
            'costSnapshotRule' => $this->costSnapshotRule(),
            'sharedStockRule' => $this->sharedStockRule(),
            'plannedProductFields' => $this->plannedProductFields(),
            'plannedVariantFields' => $this->plannedVariantFields(),
            'flashSuccess' => $this->pullFlash('success'),
            'flashError' => $this->pullFlash('error'),
            'csrfField' => Csrf::field(),
            'writeGateProductCreate' => WriteGate::productCreateForm(),
            'writeGateProductCreateReady' => WriteGate::productCreateForm()['ready'],
            'writeGateVariantForm' => WriteGate::productVariantForm(),
            'writeGateVariantFormReady' => WriteGate::productVariantForm()['ready'],
            'writeGateCostStock' => WriteGate::productCostStockForm(),
            'writeGateCostStockReady' => WriteGate::productCostStockForm()['ready'],
        ]);
    }

    public function createProduct()
    {
        $this->authorize('product_control.manage');
        $this->requirePost();
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Invalid security token.');
            redirect('/product-control');
        }
        $this->redirectWithWriteResult('/product-control', (new ProductWriteService())->create($_POST));
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
        $this->redirectWithWriteResult('/product-control', (new ProductWriteService())->applyEdit($id, $_POST));
    }

    public function createVariant()
    {
        $this->authorize('product_control.manage');
        $this->requirePost();
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Invalid security token.');
            redirect('/product-control');
        }
        $this->redirectWithWriteResult('/product-control', (new ProductVariantWriteService())->create($_POST));
    }

    public function updateCostStock()
    {
        $this->authorize('product_control.manage');
        $this->requirePost();
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Invalid security token.');
            redirect('/product-control');
        }
        $service = new ProductCostStockWriteService();
        if (!empty($_POST['product_variant_id'])) {
            $result = $service->updateVariantCostStock((int) $_POST['product_variant_id'], $_POST);
        } else {
            $result = $service->updateProductCostStock((int) ($_POST['product_id'] ?? 0), $_POST);
        }
        $this->redirectWithWriteResult('/product-control', $result);
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
            'status_message' => 'Cost/stock history tables are not fully ready yet.',
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

            $labelParts = [];
            $optionName = trim((string) ($variant['option_name'] ?? ''));
            $optionValue = trim((string) ($variant['option_value'] ?? ''));
            $supplierModel = trim((string) ($variant['supplier_model'] ?? ''));

            if ($optionName !== '' || $optionValue !== '') {
                $labelParts[] = trim($optionName . ': ' . $optionValue, ': ');
            }

            if ($supplierModel !== '') {
                $labelParts[] = $supplierModel;
            }

            $variantLabels[$variantId] = $labelParts ? implode(' / ', $labelParts) : 'Variant #' . $variantId;
        }

        if (in_array($costHistoryInventory['status'] ?? '', ['ok', 'empty'], true)) {
            foreach ($costHistoryInventory['rows'] ?? [] as $row) {
                $productId = (int) ($row['product_id'] ?? 0);
                $variantId = (int) ($row['product_variant_id'] ?? 0);

                $display['rows'][] = [
                    'type' => 'Cost',
                    'history_id' => $row['product_cost_history_id'] ?? '',
                    'sort_id' => (int) ($row['product_cost_history_id'] ?? 0),
                    'product_id' => $productId,
                    'product_name' => $productNames[$productId] ?? ('Product #' . $productId),
                    'product_variant_id' => $variantId,
                    'variant_label' => $variantId > 0 ? ($variantLabels[$variantId] ?? ('Variant #' . $variantId)) : 'Product level',
                    'old_value' => $row['old_cost'] ?? '',
                    'new_value' => $row['new_cost'] ?? '',
                    'change_type' => 'cost_update',
                    'note' => $row['note'] ?? '',
                    'created_at' => $row['created_at'] ?? '',
                ];
            }
        }

        if (in_array($stockHistoryInventory['status'] ?? '', ['ok', 'empty'], true)) {
            foreach ($stockHistoryInventory['rows'] ?? [] as $row) {
                $productId = (int) ($row['product_id'] ?? 0);
                $variantId = (int) ($row['product_variant_id'] ?? 0);

                $display['rows'][] = [
                    'type' => 'Stock',
                    'history_id' => $row['product_stock_history_id'] ?? '',
                    'sort_id' => (int) ($row['product_stock_history_id'] ?? 0),
                    'product_id' => $productId,
                    'product_name' => $productNames[$productId] ?? ('Product #' . $productId),
                    'product_variant_id' => $variantId,
                    'variant_label' => $variantId > 0 ? ($variantLabels[$variantId] ?? ('Variant #' . $variantId)) : 'Product level',
                    'old_value' => $row['old_stock'] ?? '',
                    'new_value' => $row['new_stock'] ?? '',
                    'change_type' => $row['change_type'] ?? 'stock_update',
                    'note' => $row['note'] ?? '',
                    'created_at' => $row['created_at'] ?? '',
                ];
            }
        }

        usort($display['rows'], function (array $a, array $b): int {
            $timeCompare = strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? ''));
            if ($timeCompare !== 0) {
                return $timeCompare;
            }

            return ((int) ($b['sort_id'] ?? 0)) <=> ((int) ($a['sort_id'] ?? 0));
        });

        $display['rows'] = array_slice($display['rows'], 0, 20);

        if (!empty($display['rows'])) {
            $display['status_message'] = 'Showing latest cost/stock history changes with saved notes.';
        } elseif ($display['cost_table_exists'] || $display['stock_table_exists']) {
            $display['status_message'] = 'History tables are ready. No cost/stock history rows yet.';
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
        foreach ($productReadInventory['rows'] ?? [] as $product) {
            $pid = (int) ($product['product_id'] ?? 0);
            if ($pid > 0) {
                $productNames[$pid] = trim((string) ($product['product_name'] ?? ''));
            }
        }

        foreach ($productVariantReadInventory['rows'] ?? [] as $variant) {
            $productId = (int) ($variant['product_id'] ?? 0);
            $productName = $productNames[$productId] ?? '';
            $display['rows'][] = [
                'product_variant_id' => $variant['product_variant_id'] ?? '',
                'product_id' => $productId,
                'product_name' => $productName !== '' ? $productName : '(product #' . $productId . ')',
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
            'product cost history'
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
            'product stock history'
        );
    }
    private function buildEntityReadInventory(
        string $modelClass,
        string $serviceClass,
        string $modelShortName,
        string $readServiceName,
        string $readRepositoryName,
        string $recordLabel
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
                $defaults['status_message'] = 'Table `' . $defaults['prefixed_table'] . '` not available â€” migration `0003_business_sources_suppliers_products.sql` not applied yet. Apply manually with the `ibs_` table prefix from config/database.php.';

                return $defaults;
            }

            $rowCount = $service->count();
            $defaults['row_count'] = $rowCount;
            $defaults['rows'] = $service->all(50, 0);

            if ($rowCount === 0) {
                $defaults['status'] = 'empty';
                $defaults['status_message'] = 'Table ready. No ' . $recordLabel . ' records yet (read-only; no writes in this release).';

                return $defaults;
            }

            $defaults['status'] = 'ok';
            $defaults['status_message'] = 'Showing up to 50 ' . $recordLabel . ' records (SELECT only).';

            return $defaults;
        } catch (\Throwable $e) {
            return $defaults;
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
            'Plan supplier-side product, cost, and stock control without OpenCart sync or database writes.',
            'Separate platform/source product data from supplier-editable model, cost, and stock fields.',
            'Prepare cost and stock history so dispatch and payable can use cost snapshots instead of live changing values.',
            'Stay channel-neutral so manual, offline, ecommerce, and marketplace products can share the same control model.',
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
            'Supplier Model',
            'Product Cost (with history)',
            'Vendor Stock (with history)',
            'Low Warning Threshold (warning only)',
        ];
    }

    private function readOnlyFields()
    {
        return [
            'OpenCart / Source Model (read-only when synced later)',
            'OpenCart / Source Stock (read-only when synced later)',
            'Improved Option Model (read-only when synced later)',
            'Improved Option Stock (read-only when synced later)',
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
                'rule' => 'Warning only â€” does not auto-block orders, dispatch, or payable workflows.',
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
                'Stock deduction later combines demand from all business sources â€” see Sync Preview planning foundation.',
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
