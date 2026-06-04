<?php

namespace App\Controllers;

use App\ActivityLog;
use App\Permission;

class SyncPreviewController extends Controller
{
    public function index()
    {
        $this->authorize('sync_preview.view');
        ActivityLog::record('sync_preview_access', 'Sync Preview and Import Safety planning foundation page viewed');

        $this->render('sync-preview.index', [
            'pageTitle' => 'Sync Preview',
            'breadcrumbs' => [
                ['label' => 'Operations', 'active' => false],
                ['label' => 'Sync Preview', 'active' => true],
            ],
            'accessMode' => Permission::accessMode(),
            'currentContext' => $this->currentContext(),
            'purpose' => $this->purpose(),
            'multiSourcePlan' => $this->multiSourcePlan(),
            'lokkisonaSourcePlan' => $this->lokkisonaSourcePlan(),
            'sonamoniSourcePlan' => $this->sonamoniSourcePlan(),
            'manualExternalRule' => $this->manualExternalRule(),
            'sharedStockRule' => $this->sharedStockRule(),
            'erpInvoiceRule' => $this->erpInvoiceRule(),
            'mappingFirstRule' => $this->mappingFirstRule(),
            'previewBeforeImportRule' => $this->previewBeforeImportRule(),
            'skipMissingRule' => $this->skipMissingRule(),
            'unmappedBlockingRule' => $this->unmappedBlockingRule(),
            'duplicateExistingRule' => $this->duplicateExistingRule(),
            'independentWorkflowRule' => $this->independentWorkflowRule(),
            'returnCandidateRule' => $this->returnCandidateRule(),
            'importConfirmationRule' => $this->importConfirmationRule(),
            'performanceSyncRules' => $this->performanceSyncRules(),
            'previewTotals' => $this->previewTotals(),
            'previewTableColumns' => $this->previewTableColumns(),
            'futurePreviewTablePlan' => $this->futurePreviewTablePlan(),
            'futureImportApprovalPlan' => $this->futureImportApprovalPlan(),
            'importSafetyBehavior' => $this->importSafetyBehavior(),
            'plannedPreviewFields' => $this->plannedPreviewFields(),
            'plannedPreviewItemFields' => $this->plannedPreviewItemFields(),
            'plannedImportApprovalFields' => $this->plannedImportApprovalFields(),
        ]);
    }

    private function currentContext()
    {
        return [
            'supplier' => 'Iqbal & Brothers',
            'sources' => 'Lokkisona.com, Sonamoni.com.bd, Manual/Offline',
            'summary' => 'Sync Preview and Import Safety planning starts with Lokkisona.com and Iqbal & Brothers, but stays ready for Sonamoni.com.bd, manual/offline orders, other suppliers, sales channels, payable/return workflows, and future multi-business expansion.',
        ];
    }

    private function purpose()
    {
        return [
            'Plan a safe preview-first sync/import process before any real order import.',
            'Sync must never blindly import or overwrite IBS workflow.',
            'Business Source is separate, but supplier product/cost/vendor stock can be shared across sources.',
            'Manual/external reference orders must remain available before and after channel sync is enabled.',
            'Every import later requires preview totals, confirmation, user, timestamp, and activity log.',
        ];
    }

    private function multiSourcePlan()
    {
        return [
            'title' => 'Multi-Source Sync Plan',
            'summary' => 'IBS-LK Business Manager manages supplier workflow across multiple business sources with shared ERP product/cost/stock.',
            'points' => [
                'Each business source keeps its own platform, courier mapping, and invoice style.',
                'Supplier cost and vendor stock remain central at ERP product/variant level.',
                'Stock deduction later combines demand from all business sources.',
                'Sync Preview runs per business source with source-aware mapping and preview totals.',
            ],
        ];
    }

    private function lokkisonaSourcePlan()
    {
        return [
            'title' => 'Lokkisona.com / OpenCart Source Plan',
            'summary' => 'Primary ecommerce source — OpenCart platform with Lokkisona-specific courier and invoice style.',
            'points' => [
                'Platform: OpenCart (connection planned later, not in this release).',
                'Invoice style: ERP Lokkisona-style invoice later.',
                'Courier account/status mapping: Lokkisona-specific.',
                'Status Mapping must be configured before any Lokkisona sync/import.',
            ],
        ];
    }

    private function sonamoniSourcePlan()
    {
        return [
            'title' => 'Sonamoni.com.bd / WooCommerce Future Source Plan',
            'summary' => 'Future WooCommerce source with separate courier mapping and manual/external fallback first.',
            'points' => [
                'Platform: WooCommerce (direct sync comes later, not in this release).',
                'Same/similar supplier products may map to the same ERP product/variant.',
                'Same supplier cost can be shared across Lokkisona and Sonamoni.',
                'Courier account/status mapping is separate from Lokkisona.',
                'Manual/external reference order entry must be supported before WooCommerce sync.',
            ],
        ];
    }

    private function manualExternalRule()
    {
        return [
            'title' => 'Manual / External Reference Order Fallback',
            'summary' => 'Sonamoni and other sources can first be handled as Manual / External Reference Orders.',
            'points' => [
                'Manual/external orders must show in the normal ERP order list later.',
                'Required future reference fields: business source, external order reference, external invoice reference, courier account, supplier, ERP product/variant, quantity, cost snapshot.',
                'Later WooCommerce sync can replace/reduce manual entry without changing supplier/payable workflow.',
                'Manual/offline order entry must remain available at all times.',
            ],
        ];
    }

    private function sharedStockRule()
    {
        return [
            'title' => 'Shared Supplier Stock Rule',
            'summary' => 'Vendor Stock belongs to internal ERP product/variant, not to each website/source.',
            'points' => [
                'If Lokkisona and Sonamoni sell the same supplier product/option, both source products must map to the same ERP product/variant.',
                'Stock deduction later must combine demand from all business sources.',
                'Example: Lokkisona Qty 2 + Sonamoni Qty 3 = Vendor Stock deducts total Qty 5.',
                'Courier account/status mapping can be separate per business source.',
                'Supplier cost and vendor stock remain central/shared at ERP product/variant level.',
            ],
        ];
    }

    private function erpInvoiceRule()
    {
        return [
            'title' => 'ERP Invoice Planning Rule',
            'summary' => 'ERP must generate/print its own source-aware invoice without depending on source admin login.',
            'points' => [
                'ERP must have its own invoice/print system later.',
                'ERP should not depend on being logged into Lokkisona/Sonamoni admin.',
                'Source invoices can be referenced, but ERP generates from imported/manual data.',
                'Future fields: source_invoice_reference, invoice_template_type, business_source_invoice_style, invoice_print_source = ERP, source_order_reference.',
                'Lokkisona order → ERP Lokkisona-style invoice; Sonamoni order → ERP Sonamoni-style invoice; Manual/offline → ERP manual invoice.',
            ],
        ];
    }

    private function mappingFirstRule()
    {
        return [
            'title' => 'Mapping-First Sync Rule',
            'summary' => 'Status Mapping must be configured and read before any sync preview or import.',
            'points' => [
                'Sync must read Settings/Status Mapping first.',
                'No orders should be imported without valid mapping.',
                'Unmapped source status must be blocked and shown in preview.',
                'See Status Mapping planning foundation for mapping types and rules.',
            ],
        ];
    }

    private function previewBeforeImportRule()
    {
        return [
            'title' => 'Preview-Before-Import Rule',
            'summary' => 'Sync Preview must run and show totals before any actual import action.',
            'points' => [
                'Test Sync / Preview must be visible.',
                'Full Sync must be hidden from normal UI.',
                'Preview must show totals before import.',
                'Import action later must require confirmation, user, timestamp, and activity log.',
                'Preview should be exportable later — export is not implemented in this release.',
            ],
        ];
    }

    private function skipMissingRule()
    {
        return [
            'title' => 'Missing / Status 0 Skip Rule',
            'summary' => 'Missing or OpenCart status 0 orders must be skipped during preview and import.',
            'points' => [
                'Missing/OpenCart status 0 must be skipped.',
                'Skipped orders are counted in preview totals with reason.',
                'Sync must use current/source order status only, not older order history.',
            ],
        ];
    }

    private function unmappedBlockingRule()
    {
        return [
            'title' => 'Unmapped Status Blocking Rule',
            'summary' => 'Unmapped source statuses are blocked in preview and must not import.',
            'points' => [
                'Unmapped source status must be blocked and shown in preview.',
                'No orders should be imported without valid mapping.',
                'Every blocked item should show block reason.',
                'Owner/admin resolves mapping before retrying preview/import.',
            ],
        ];
    }

    private function duplicateExistingRule()
    {
        return [
            'title' => 'Duplicate / Existing Order Rule',
            'summary' => 'Existing source orders and IBS workflow must not be overwritten by sync/import.',
            'points' => [
                'Existing/duplicate source orders should not import again.',
                'Existing IBS workflow must not be overwritten.',
                'Preview shows total duplicate/existing orders and Existing IBS Order Match per row.',
                'Sync Preview/import cannot overwrite existing IBS workflow.',
            ],
        ];
    }

    private function independentWorkflowRule()
    {
        return [
            'title' => 'Independent IBS Workflow Rule',
            'summary' => 'Source status is used only at first sync/import — IBS workflow stays independent afterward.',
            'points' => [
                'Source/origin order status is used only at first sync/import time.',
                'After order enters IBS, IBS workflow remains independent.',
                'Later source status changes must not overwrite IBS workflow automatically.',
                'Import must not create dispatch/payable/return records automatically without correct workflow event.',
            ],
        ];
    }

    private function returnCandidateRule()
    {
        return [
            'title' => 'Return Candidate Separation Rule',
            'summary' => 'Supplier Return and Lokkisona Return candidates must be separated during preview.',
            'points' => [
                'Return candidates go to Return Receive planning only when mapping allows.',
                'Supplier Return and Lokkisona Return must stay clearly separated in preview totals.',
                'Supplier Return may affect payable only after owner/admin review later.',
                'Lokkisona Return is list/control only and does not deduct supplier payable.',
            ],
        ];
    }

    private function importConfirmationRule()
    {
        return [
            'title' => 'Import Confirmation / Audit Rule',
            'summary' => 'Every import requires explicit confirmation and audit trail.',
            'points' => [
                'Import action later must require confirmation, user, timestamp, and activity log.',
                'Import-ready orders can later become IBS orders only after approved import.',
                'Every skipped/blocked item should show reason.',
                'sync_imports will record approval, totals, and confirmation note.',
            ],
        ];
    }

    private function performanceSyncRules()
    {
        return [
            'Max 50 orders per sync request.',
            'No background auto loops.',
            'No repeated fallback AJAX.',
            'One sync request at a time.',
            'No auto retry storm.',
            'Test Sync / Preview visible; Full Sync hidden from normal UI.',
        ];
    }

    private function previewTotals()
    {
        return [
            'total checked orders',
            'total matched/import-ready orders',
            'total skipped orders',
            'total unmapped orders',
            'total duplicate/existing orders',
            'total supplier-related orders',
            'total return candidates',
            'total Lokkisona Return candidates',
            'total Supplier Return candidates',
            'total blocked records',
            'request limit used',
        ];
    }

    private function previewTableColumns()
    {
        return [
            'Source Order ID',
            'Source Order Reference',
            'Business Source',
            'Platform Type',
            'Source/Origin Status',
            'Mapped IBS Target',
            'Mapped Return Type',
            'Supplier',
            'ERP Product/Variant Mapping Status',
            'Shared Vendor Stock Impact',
            'Customer Name',
            'Order Date',
            'Source Invoice Reference',
            'ERP Invoice Template Type',
            'Consignment ID',
            'Courier Account',
            'Courier Status',
            'Preview Result',
            'Block Reason',
            'Existing IBS Order Match',
            'Last Synced At',
        ];
    }

    private function futurePreviewTablePlan()
    {
        return [
            'Sync preview list will show one row per source order with preview result and block reason.',
            'Import-ready rows will be selectable for approved import only after confirmation.',
            'Blocked and duplicate rows remain visible with reason — never silently dropped.',
            'Shared vendor stock impact will be calculated across all sources for mapped ERP variants.',
            'Preview export is planned later — not implemented in this release.',
        ];
    }

    private function futureImportApprovalPlan()
    {
        return [
            'Owner/admin will approve import from a completed sync preview.',
            'Import approval records sync_preview_id, totals, confirmation note, and approver.',
            'Staff may view/manage later based on sync_import permissions.',
            'Supplier role does not manage global sync/import.',
            'No sync_imports or sync_logs are written in this release.',
        ];
    }

    private function importSafetyBehavior()
    {
        return [
            'Import-ready orders can later become IBS orders.',
            'Existing/duplicate source orders should not import again.',
            'Existing IBS workflow must not be overwritten.',
            'Return candidates should go to Return Receive planning only when mapping allows.',
            'Supplier Return and Lokkisona Return must stay clearly separated.',
            'Supplier Return may affect payable only after owner/admin review later.',
            'Lokkisona Return is list/control only and does not deduct supplier payable.',
            'Every skipped/blocked item should show reason.',
            'Preview should be exportable later, but export is not implemented yet.',
        ];
    }

    private function plannedPreviewFields()
    {
        return [
            'sync_preview_id',
            'business_source_id',
            'platform_type',
            'source_status_id',
            'mapped_target',
            'total_checked_orders',
            'total_import_ready_orders',
            'total_skipped_orders',
            'total_unmapped_orders',
            'total_duplicate_orders',
            'total_supplier_related_orders',
            'total_return_candidates',
            'total_lokkisona_return_candidates',
            'total_supplier_return_candidates',
            'total_blocked_records',
            'request_limit',
            'checked_by',
            'checked_at',
        ];
    }

    private function plannedPreviewItemFields()
    {
        return [
            'sync_preview_item_id',
            'sync_preview_id',
            'business_source_id',
            'platform_type',
            'source_order_id',
            'source_order_reference',
            'source_invoice_reference',
            'invoice_template_type',
            'source_status_id',
            'source_status_name',
            'mapped_ibs_status',
            'mapped_return_type',
            'supplier_id',
            'erp_product_id',
            'erp_variant_id',
            'product_mapping_status',
            'shared_vendor_stock_impact',
            'customer_name_snapshot',
            'order_date',
            'courier_account_id',
            'consignment_id',
            'courier_status',
            'preview_result',
            'block_reason',
            'existing_ibs_order_id',
            'last_synced_at',
        ];
    }

    private function plannedImportApprovalFields()
    {
        return [
            'sync_import_id',
            'sync_preview_id',
            'business_source_id',
            'platform_type',
            'import_type',
            'request_limit',
            'total_requested',
            'total_imported',
            'total_skipped',
            'total_blocked',
            'confirmation_note',
            'status',
            'created_by',
            'approved_by',
            'created_at',
            'approved_at',
        ];
    }
}
