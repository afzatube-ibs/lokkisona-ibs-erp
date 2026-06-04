<?php

namespace App\Controllers;

use App\ActivityLog;
use App\Permission;

class ProductControlController extends Controller
{
    public function index()
    {
        $this->authorize('product_control.view');
        ActivityLog::record('product_control_access', 'Product Control foundation page viewed');

        $this->render('product-control.index', [
            'pageTitle' => 'Product Control',
            'breadcrumbs' => [
                ['label' => 'Operations', 'active' => false],
                ['label' => 'Product Control', 'active' => true],
            ],
            'accessMode' => Permission::accessMode(),
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
        ]);
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
