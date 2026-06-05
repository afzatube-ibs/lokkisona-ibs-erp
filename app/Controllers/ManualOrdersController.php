<?php

namespace App\Controllers;

use App\ActivityLog;
use App\Csrf;
use App\Database;
use App\Database\TableName;
use App\Models\ManualOrder;
use App\Models\ManualOrderItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Permission;
use App\ReadFoundation\WriteGate;
use App\Services\ReadOnly\ManualOrderItemReadService;
use App\Services\ReadOnly\ManualOrderReadService;
use App\Services\ReadOnly\OrderItemReadService;
use App\Services\ReadOnly\OrderReadService;
use App\Services\ReadOnly\ProductReadService;
use App\Services\ReadOnly\ProductVariantReadService;
use App\Services\Write\ManualOrderWriteService;

class ManualOrdersController extends Controller
{
    public function index()
    {
        $this->authorize('manual_orders.view');
        ActivityLog::record('manual_orders_access', 'Manual and External Order planning foundation page viewed');

        $this->render('manual-orders.index', [
            'pageTitle' => 'Manual Orders',
            'breadcrumbs' => [
                ['label' => 'Operations', 'active' => false],
                ['label' => 'Manual Orders', 'active' => true],
            ],
            'accessMode' => Permission::accessMode(),
            'manualOrderReadInventory' => $this->buildManualOrderReadInventory(),
            'manualOrderItemReadInventory' => $this->buildManualOrderItemReadInventory(),
            'orderReadInventory' => $this->buildOrderReadInventory(),
            'orderItemReadInventory' => $this->buildOrderItemReadInventory(),
            'productReadInventory' => $this->buildProductReadInventory(),
            'productVariantReadInventory' => $this->buildProductVariantReadInventory(),
            'currentContext' => $this->currentContext(),
            'purpose' => $this->purpose(),
            'sonamoniReferencePlan' => $this->sonamoniReferencePlan(),
            'offlineOrderPlan' => $this->offlineOrderPlan(),
            'businessSourceRule' => $this->businessSourceRule(),
            'externalReferenceRule' => $this->externalReferenceRule(),
            'productMappingRule' => $this->productMappingRule(),
            'sharedStockRule' => $this->sharedStockRule(),
            'costSnapshotRule' => $this->costSnapshotRule(),
            'workflowEntryRule' => $this->workflowEntryRule(),
            'invoicePlanningRule' => $this->invoicePlanningRule(),
            'confirmationAuditRule' => $this->confirmationAuditRule(),
            'duplicateReferenceRule' => $this->duplicateReferenceRule(),
            'woocommerceUpgradeRule' => $this->woocommerceUpgradeRule(),
            'plannedManualOrderFields' => $this->plannedManualOrderFields(),
            'plannedManualOrderItemFields' => $this->plannedManualOrderItemFields(),
            'plannedManualOrderAuditFields' => $this->plannedManualOrderAuditFields(),
            'flashSuccess' => $this->pullFlash('success'),
            'flashError' => $this->pullFlash('error'),
            'csrfField' => Csrf::field(),
            'writeGate' => WriteGate::manualOrders(),
            'writeGateReady' => WriteGate::manualOrders()['ready'],
        ]);
    }

    public function create()
    {
        $this->authorize('manual_orders.manage');
        $this->requirePost();
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Invalid security token.');
            redirect('/manual-orders');
        }
        $this->redirectWithWriteResult('/manual-orders', (new ManualOrderWriteService())->create($_POST));
    }

    private function buildManualOrderReadInventory()
    {
        return $this->buildEntityReadInventory(
            ManualOrder::class,
            ManualOrderReadService::class,
            'ManualOrder',
            'ManualOrderReadService',
            'ManualOrderRepository',
            'manual order',
            '0005_orders_manual_orders_workflow.sql',
            true
        );
    }

    private function buildManualOrderItemReadInventory()
    {
        return $this->buildEntityReadInventory(
            ManualOrderItem::class,
            ManualOrderItemReadService::class,
            'ManualOrderItem',
            'ManualOrderItemReadService',
            'ManualOrderItemRepository',
            'manual order item',
            '0005_orders_manual_orders_workflow.sql',
            true
        );
    }

    private function buildOrderReadInventory()
    {
        return $this->buildEntityReadInventory(
            Order::class,
            OrderReadService::class,
            'Order',
            'OrderReadService',
            'OrderRepository',
            'ERP order',
            '0005_orders_manual_orders_workflow.sql',
            true
        );
    }

    private function buildOrderItemReadInventory()
    {
        return $this->buildEntityReadInventory(
            OrderItem::class,
            OrderItemReadService::class,
            'OrderItem',
            'OrderItemReadService',
            'OrderItemRepository',
            'ERP order item',
            '0005_orders_manual_orders_workflow.sql',
            true
        );
    }

    private function buildProductReadInventory()
    {
        return $this->buildEntityReadInventory(
            Product::class,
            ProductReadService::class,
            'Product',
            'ProductReadService',
            'ProductRepository',
            'product',
            '0003_business_sources_suppliers_products.sql'
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
            'product variant',
            '0003_business_sources_suppliers_products.sql'
        );
    }

    private function buildEntityReadInventory(
        string $modelClass,
        string $serviceClass,
        string $modelShortName,
        string $readServiceName,
        string $readRepositoryName,
        string $recordLabel,
        string $migrationFile,
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
                $defaults['status_message'] = 'Table `' . $defaults['prefixed_table'] . '` not available - migration `' . $migrationFile . '` not applied yet. Apply manually with the `ibs_` table prefix from config/database.php.';

                return $defaults;
            }

            $rowCount = $service->count();
            $defaults['row_count'] = $rowCount;
            $defaults['rows'] = $useLatestRows ? $service->latest(50) : $service->all(50, 0);

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

    private function currentContext()
    {
        return [
            'primarySource' => 'Lokkisona.com',
            'futureSource' => 'Sonamoni.com.bd',
            'primarySupplier' => 'Iqbal & Brothers',
            'summary' => 'Manual / External Order planning starts with Lokkisona.com and Iqbal & Brothers, while preparing Sonamoni.com.bd WooCommerce reference entry, offline/manual sales, and future channels to enter the same supplier workflow without live sync.',
        ];
    }

    private function purpose()
    {
        return [
            'Support Sonamoni.com.bd orders as Manual / External Reference Orders before direct WooCommerce sync.',
            'Support offline/manual sales through direct ERP entry.',
            'Let manual/external orders behave like normal IBS orders after entry while clearly showing their source/reference.',
            'Prepare shared supplier workflow, dispatch, returns, payable, stock planning, and ERP invoice templates without creating real orders.',
        ];
    }

    private function sonamoniReferencePlan()
    {
        return [
            'title' => 'Sonamoni Manual Reference Order Plan',
            'summary' => 'Sonamoni.com.bd currently runs on WooCommerce, but direct WooCommerce sync comes later.',
            'points' => [
                'Business Source: Sonamoni.com.bd.',
                'External Order Reference: WooCommerce order ID.',
                'External Invoice Reference: Sonamoni invoice/reference if available.',
                'Courier Account: Sonamoni courier account.',
                'Supplier: Iqbal & Brothers or mapped supplier.',
                'ERP Product/Variant: mapped same supplier item.',
                'Cost Snapshot: supplier cost at entry time.',
            ],
        ];
    }

    private function offlineOrderPlan()
    {
        return [
            'title' => 'Offline Order Plan',
            'summary' => 'Manual / Offline Order is direct ERP entry for non-synced orders.',
            'points' => [
                'Business Source: Manual / Offline.',
                'External Reference: manual invoice/reference.',
                'Courier Account: selected manually if needed.',
                'Supplier: selected/mapped supplier.',
                'ERP Product/Variant: selected internal product.',
                'Cost Snapshot: supplier cost at entry time.',
            ],
        ];
    }

    private function businessSourceRule()
    {
        return [
            'title' => 'Business Source Selection Rule',
            'summary' => 'Every manual/external order must declare its business source before confirmation.',
            'points' => [
                'Lokkisona.com = OpenCart source.',
                'Sonamoni.com.bd = WooCommerce future source / manual external reference first.',
                'Manual / Offline Order = direct ERP entry.',
                'Future source/channel = later extension.',
                'Courier account/status mapping can remain separate per business source.',
            ],
        ];
    }

    private function externalReferenceRule()
    {
        return [
            'title' => 'External Reference Rule',
            'summary' => 'Manual/external orders must preserve their source reference for review and duplicate checks.',
            'points' => [
                'Store external_order_reference and external_invoice_reference when available.',
                'Manual entry must not auto-modify the source website.',
                'External references remain visible in the normal ERP order list later.',
                'Duplicate external reference should be blocked later.',
            ],
        ];
    }

    private function productMappingRule()
    {
        return [
            'title' => 'Product / Variant Mapping Rule',
            'summary' => 'Manual order items must map to internal ERP product/variant before they can enter workflow.',
            'points' => [
                'Manual order must not bypass product mapping.',
                'Manual/external order items must map to internal ERP product/variant for shared supplier stock and cost.',
                'Product mapping status is planned per item.',
                'Unmapped products should block confirmation until owner/admin resolves mapping.',
            ],
        ];
    }

    private function sharedStockRule()
    {
        return [
            'title' => 'Shared Vendor Stock Rule',
            'summary' => 'Vendor Stock belongs to internal ERP product/variant, not to each website/source.',
            'points' => [
                'If Lokkisona and Sonamoni sell the same supplier product/option, both source products must map to the same ERP product/variant.',
                'Manual/external orders must also deduct from the same shared vendor stock later.',
                'Example: Lokkisona Qty 2 + Sonamoni Qty 3 + Manual Qty 1 = Vendor Stock deducts total Qty 6.',
                'Supplier cost and vendor stock remain central/shared at ERP product/variant level.',
                'No stock is deducted in this release.',
            ],
        ];
    }

    private function costSnapshotRule()
    {
        return [
            'title' => 'Cost Snapshot Rule',
            'summary' => 'Manual order entry must capture supplier cost snapshot for future dispatch/payable accuracy.',
            'points' => [
                'Manual order must capture cost snapshot at entry time.',
                'Cost snapshot is informational until the correct workflow event.',
                'Manual order must not create payable until a correct workflow event, such as dispatch report.',
                'Live supplier cost can change later without rewriting confirmed order snapshots.',
            ],
        ];
    }

    private function workflowEntryRule()
    {
        return [
            'title' => 'Workflow Entry Rule',
            'summary' => 'Manual / External Orders enter IBS workflow only after confirmation.',
            'points' => [
                'Manual order must enter IBS workflow as New Order or configured starting status.',
                'Manual/external orders enter IBS workflow after confirmation and must not bypass workflow rules.',
                'After entry, they behave like normal IBS orders while keeping source/reference visible.',
                'Manual entry must log user, timestamp, note, and activity.',
            ],
        ];
    }

    private function invoicePlanningRule()
    {
        return [
            'title' => 'Invoice Planning Rule',
            'summary' => 'Manual/external orders should support ERP invoice printing later.',
            'points' => [
                'ERP has its own invoice/print system later.',
                'Sonamoni manual reference order should use Sonamoni-style ERP invoice later.',
                'Offline order should use ERP manual invoice template later.',
                'ERP invoice must not depend on source admin login.',
                'No invoice is generated in this release.',
            ],
        ];
    }

    private function confirmationAuditRule()
    {
        return [
            'title' => 'Confirmation / Audit Rule',
            'summary' => 'Manual order creation later must require confirmation before submit.',
            'points' => [
                'Confirmation should show business source, supplier, total products/items, total quantity, total cost snapshot, customer name/phone, external reference, and courier account.',
                'Manual order must log user, timestamp, note, and activity.',
                'Confirmed data becomes order snapshot planning data later.',
                'No order records are written in this release.',
            ],
        ];
    }

    private function duplicateReferenceRule()
    {
        return [
            'title' => 'Duplicate Reference Safety Rule',
            'summary' => 'Duplicate external references should be blocked later to prevent duplicate order entry.',
            'points' => [
                'Duplicate checks are per business source and external reference.',
                'Existing ERP order/reference match should be shown before confirmation.',
                'Blocked duplicate entries require owner/admin review.',
                'No duplicate checking is implemented in this foundation release.',
            ],
        ];
    }

    private function woocommerceUpgradeRule()
    {
        return [
            'title' => 'Future Direct WooCommerce Sync Upgrade Rule',
            'summary' => 'Manual Sonamoni reference entry is the fallback before direct WooCommerce sync.',
            'points' => [
                'Direct WooCommerce sync can later reduce manual entry.',
                'The supplier/payable workflow should not change when WooCommerce sync is added.',
                'Existing manual/external reference orders remain source-aware ERP orders.',
                'No WooCommerce connection or sync is added in this release.',
            ],
        ];
    }

    private function plannedManualOrderFields()
    {
        return [
            'manual_order_id',
            'business_source_id',
            'platform_type',
            'external_order_reference',
            'external_invoice_reference',
            'erp_order_reference',
            'customer_name',
            'customer_phone',
            'customer_address',
            'supplier_id',
            'courier_account_id',
            'order_status',
            'workflow_status',
            'total_items',
            'total_quantity',
            'total_cost_snapshot',
            'total_selling_amount',
            'invoice_template_type',
            'created_by',
            'confirmed_by',
            'created_at',
            'confirmed_at',
            'status',
        ];
    }

    private function plannedManualOrderItemFields()
    {
        return [
            'manual_order_item_id',
            'manual_order_id',
            'erp_product_id',
            'erp_variant_id',
            'product_name_snapshot',
            'option_name_snapshot',
            'supplier_model_snapshot',
            'source_model_snapshot',
            'quantity',
            'cost_snapshot',
            'selling_price_snapshot',
            'item_total',
            'product_mapping_status',
            'stock_impact_status',
            'created_at',
        ];
    }

    private function plannedManualOrderAuditFields()
    {
        return [
            'manual_order_audit_id',
            'manual_order_id',
            'action',
            'actor_user_id',
            'actor_role',
            'note',
            'ip_address',
            'user_agent',
            'created_at',
        ];
    }
}
