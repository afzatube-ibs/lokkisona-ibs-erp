<?php

namespace App\Controllers;

use App\ActivityLog;
use App\Permission;

class InvoicePrintingController extends Controller
{
    public function index()
    {
        $this->authorize('invoice_printing.view');
        ActivityLog::record('invoice_printing_access', 'ERP Invoice and Packing Print planning foundation page viewed');

        $this->render('invoice-printing.index', [
            'pageTitle' => 'Invoice Printing',
            'breadcrumbs' => [
                ['label' => 'Operations', 'active' => false],
                ['label' => 'Invoice Printing', 'active' => true],
            ],
            'accessMode' => Permission::accessMode(),
            'currentContext' => $this->currentContext(),
            'purpose' => $this->purpose(),
            'templateRules' => $this->templateRules(),
            'invoiceLayoutSections' => $this->invoiceLayoutSections(),
            'courierReferenceFields' => $this->courierReferenceFields(),
            'printRules' => $this->printRules(),
            'documentTypes' => $this->documentTypes(),
            'plannedInvoiceFields' => $this->plannedInvoiceFields(),
            'plannedInvoiceItemFields' => $this->plannedInvoiceItemFields(),
            'plannedPackingPrintFields' => $this->plannedPackingPrintFields(),
            'plannedPrintLogFields' => $this->plannedPrintLogFields(),
            'plannedInvoiceTemplateFields' => $this->plannedInvoiceTemplateFields(),
        ]);
    }

    private function currentContext()
    {
        return [
            'primarySource' => 'Lokkisona.com',
            'primarySupplier' => 'Iqbal & Brothers',
            'summary' => 'ERP Invoice and Packing Print planning starts with Lokkisona order workflow and Iqbal & Brothers supplier operations, but print templates remain source-aware and ready for Sonamoni.com.bd, manual/offline orders, other suppliers, returns, dispatch, payable, and future multi-business expansion.',
        ];
    }

    private function purpose()
    {
        return [
            'ERP must have its own invoice print system.',
            'Source invoice reference can be stored, but ERP print must be independent.',
            'ERP print must not depend on source admin login.',
            'Current invoice extension is read-only business reference only; no old extension code is copied.',
            'Real Lokkisona invoice sample is visual/layout reference only.',
            'PIT Order Manager is read-only reference for courier tracking and consignment fields only.',
        ];
    }

    private function templateRules()
    {
        return [
            [
                'source' => 'Lokkisona order',
                'template' => 'ERP Lokkisona-style invoice later',
                'note' => 'Uses Lokkisona layout/branding reference while printing from ERP order snapshot.',
            ],
            [
                'source' => 'Sonamoni order',
                'template' => 'ERP Sonamoni-style invoice later',
                'note' => 'Separate source-aware style for WooCommerce/manual-external Sonamoni orders.',
            ],
            [
                'source' => 'Manual/offline order',
                'template' => 'ERP manual invoice later',
                'note' => 'Manual template for external reference orders and offline entry.',
            ],
        ];
    }

    private function invoiceLayoutSections()
    {
        return [
            [
                'section' => 'Header',
                'fields' => 'Lokkisona logo, store name/details, invoice title, order ID / ERP invoice number, invoice number/status, order date',
            ],
            [
                'section' => 'Customer/Order block',
                'fields' => 'Customer details, delivery address, order & shipping details, shipping method, payment method, payment status, order date',
            ],
            [
                'section' => 'Payment summary',
                'fields' => 'Due/Paid mark, sub-total, shipping/city charge, grand total',
            ],
            [
                'section' => 'Product table',
                'fields' => 'Item number, product image, product name, options/variation text, model/SKU, quantity, unit price, total',
            ],
            [
                'section' => 'Courier/tracking block',
                'fields' => 'Consignment ID / tracking number, courier name/account, tracking QR/code reference, tracking URL reference if available',
            ],
            [
                'section' => 'Footer',
                'fields' => 'Store/source name, thank-you message, support/contact number, return/replacement note',
            ],
        ];
    }

    private function courierReferenceFields()
    {
        return [
            'courier_account_id',
            'courier_name',
            'consignment_id',
            'tracking_number',
            'tracking_url',
            'courier_status',
            'courier_qr_reference',
        ];
    }

    private function printRules()
    {
        return [
            'Customer invoice must not show supplier cost.',
            'Supplier model/cost can be used in internal packing/dispatch documents only.',
            'ERP invoice must print from ERP order snapshot.',
            'Print/download actions should be logged later.',
            'Reprint rules are planned later.',
            'Packing and dispatch documents may show internal supplier model/cost snapshots when appropriate.',
        ];
    }

    private function documentTypes()
    {
        return [
            'Customer Invoice',
            'Packing Invoice / Packing Slip',
            'Dispatch Batch Report',
            'Supplier Product Summary',
            'Return Receive Batch Print',
            'Supplier Payable Settlement Summary',
        ];
    }

    private function plannedInvoiceFields()
    {
        return [
            'invoice_id',
            'ibs_order_id',
            'business_source_id',
            'source_order_reference',
            'source_invoice_reference',
            'erp_invoice_number',
            'invoice_status',
            'invoice_template_id',
            'invoice_template_type',
            'business_source_invoice_style',
            'invoice_print_source',
            'customer_name_snapshot',
            'delivery_address_snapshot',
            'shipping_method_snapshot',
            'payment_method_snapshot',
            'payment_status_snapshot',
            'sub_total_snapshot',
            'shipping_charge_snapshot',
            'grand_total_snapshot',
            'created_by',
            'created_at',
            'updated_at',
        ];
    }

    private function plannedInvoiceItemFields()
    {
        return [
            'invoice_item_id',
            'invoice_id',
            'order_item_id',
            'product_id',
            'variant_id',
            'product_image_reference',
            'product_name_snapshot',
            'option_text_snapshot',
            'source_model_snapshot',
            'quantity',
            'unit_price_snapshot',
            'line_total_snapshot',
            'sort_order',
            'created_at',
        ];
    }

    private function plannedPackingPrintFields()
    {
        return [
            'packing_print_id',
            'ibs_order_id',
            'dispatch_report_id',
            'supplier_id',
            'business_source_id',
            'print_type',
            'supplier_model_snapshot',
            'cost_snapshot_visible_internal',
            'total_items',
            'total_quantity',
            'printed_by',
            'printed_at',
            'status',
        ];
    }

    private function plannedPrintLogFields()
    {
        return [
            'print_log_id',
            'document_type',
            'document_id',
            'action',
            'print_status',
            'printed_by',
            'printed_at',
            'downloaded_at',
            'reprint_reason',
            'ip_address',
            'user_agent',
            'created_at',
        ];
    }

    private function plannedInvoiceTemplateFields()
    {
        return [
            'invoice_template_id',
            'business_source_id',
            'template_name',
            'template_type',
            'invoice_style',
            'logo_reference',
            'store_name_snapshot',
            'support_contact_snapshot',
            'footer_note',
            'is_active',
            'created_by',
            'updated_by',
            'created_at',
            'updated_at',
        ];
    }
}
