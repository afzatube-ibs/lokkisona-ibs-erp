<?php

namespace App\Controllers;

use App\ActivityLog;
use App\Permission;

class SupplierPayablesController extends Controller
{
    public function index()
    {
        $this->authorize('supplier_payables.view');
        ActivityLog::record('supplier_payables_access', 'Supplier Payable and Settlement planning foundation page viewed');

        $this->render('supplier-payables.index', [
            'pageTitle' => 'Supplier Payables',
            'breadcrumbs' => [
                ['label' => 'Operations', 'active' => false],
                ['label' => 'Supplier Payables', 'active' => true],
            ],
            'accessMode' => Permission::accessMode(),
            'currentContext' => $this->currentContext(),
            'purpose' => $this->purpose(),
            'productCostPayable' => $this->productCostPayable(),
            'supplierInvoice' => $this->supplierInvoice(),
            'additionalPayable' => $this->additionalPayable(),
            'returnDeduction' => $this->returnDeduction(),
            'paymentMade' => $this->paymentMade(),
            'advanceReceived' => $this->advanceReceived(),
            'netPayable' => $this->netPayable(),
            'approvalRule' => $this->approvalRule(),
            'selfRequestRule' => $this->selfRequestRule(),
            'reportPlan' => $this->reportPlan(),
            'plannedLedgerFields' => $this->plannedLedgerFields(),
            'plannedInvoiceFields' => $this->plannedInvoiceFields(),
            'plannedPaymentFields' => $this->plannedPaymentFields(),
            'plannedDeductionFields' => $this->plannedDeductionFields(),
        ]);
    }

    private function currentContext()
    {
        return [
            'supplier' => 'Iqbal & Brothers',
            'source' => 'Lokkisona.com',
            'summary' => 'Supplier Payable & Settlement planning starts with Iqbal & Brothers supplier operations and the Lokkisona order workflow, but the payable model stays channel-neutral and ready for other suppliers, sales channels, manual/offline orders, return workflows, and future multi-business expansion.',
        ];
    }

    private function purpose()
    {
        return [
            'Define a safe supplier payable and settlement model before any payable writes or live order/dispatch links are enabled.',
            'Keep supplier payable based on product cost only, never selling price.',
            'Document how Product Cost Payable is created from dispatch cost snapshots.',
            'Plan manual invoices, additional payable, deductions, payments, and advances with owner/admin approval.',
            'Stay channel-neutral so manual, offline, ecommerce, and marketplace suppliers can share the same payable model.',
        ];
    }

    private function productCostPayable()
    {
        return [
            'title' => 'Product Cost Payable',
            'summary' => 'Dispatch report creates Product Cost Payable using the dispatch-time cost snapshot.',
            'points' => [
                'Product Cost Payable is created from a locked dispatch report.',
                'It must use the dispatch-time cost snapshot, not live changing product cost.',
                'Supplier payable is based on product cost only, not selling price.',
                'No automatic payable mutation without a clear event source such as a locked dispatch report.',
            ],
        ];
    }

    private function supplierInvoice()
    {
        return [
            'title' => 'Supplier Invoice',
            'summary' => 'Supplier invoices can be added later manually for reconciliation against payable.',
            'points' => [
                'Supplier Invoice can be added later manually by owner/admin.',
                'Invoices are reconciled against Product Cost Payable and additional payable.',
                'Invoice entries require confirmation, note, user, timestamp, and activity log later.',
            ],
        ];
    }

    private function additionalPayable()
    {
        return [
            'title' => 'Additional Payable',
            'summary' => 'Additional payable covers agreed charges beyond product cost.',
            'points' => [
                'Additional Payable can be added later manually.',
                'Used for agreed extra charges that increase net payable.',
                'Requires owner/admin review and full audit later.',
            ],
        ];
    }

    private function returnDeduction()
    {
        return [
            'title' => 'Return / Damage Deduction',
            'summary' => 'Returns and damages can reduce payable later.',
            'points' => [
                'Return/Damage Deduction can reduce payable later.',
                'Deductions link to a return and capture condition status and reason.',
                'Deductions reduce net payable and require owner/admin approval.',
            ],
        ];
    }

    private function paymentMade()
    {
        return [
            'title' => 'Payment Made to Supplier',
            'summary' => 'Payments made to the supplier reduce the payable balance.',
            'points' => [
                'Payment Made to Supplier reduces the payable balance.',
                'Payments capture method, reference, date, and note.',
                'Payment entries require owner/admin approval and audit later.',
            ],
        ];
    }

    private function advanceReceived()
    {
        return [
            'title' => 'Advance Received from Supplier',
            'summary' => 'Advances from the supplier are tracked separately and can reduce net payable.',
            'points' => [
                'Advance Received from Supplier should be tracked separately.',
                'Advances can reduce net payable through a clear adjustment.',
                'Advance handling requires owner/admin review and audit later.',
            ],
        ];
    }

    private function netPayable()
    {
        return [
            'title' => 'Net Payable to Supplier',
            'summary' => 'Net Payable to Supplier = payable additions - deductions - payments/advance adjustments.',
            'points' => [
                'Additions: Product Cost Payable + Additional Payable + Supplier Invoice charges.',
                'Deductions: Return/Damage Deduction.',
                'Adjustments: Payment Made to Supplier and Advance Received from Supplier.',
                'Net Payable is derived from ledger entries, never mutated automatically without an event source.',
            ],
        ];
    }

    private function approvalRule()
    {
        return [
            'Every payable/settlement action later must require confirmation, note, user, timestamp, and activity log.',
            'Supplier may later request or manually add entries, but owner/admin must review before approval.',
            'No automatic payable mutation without a clear event source.',
            'Ledger entries are append-only for audit; corrections are new entries, not silent edits.',
        ];
    }

    private function selfRequestRule()
    {
        return [
            'Supplier role should later see only its own payable/settlement area, not all suppliers.',
            'Supplier self-requests are draft until owner/admin approval.',
            'Approved entries become part of the supplier net payable calculation.',
        ];
    }

    private function reportPlan()
    {
        return [
            'Payable and settlement statements should later support print, export, and download.',
            'No real print, export, or download is implemented in this release.',
            'Statements will use approved ledger entries and snapshot values, not live changing cost.',
            'Reports stay channel-neutral and supplier-scoped.',
        ];
    }

    private function plannedLedgerFields()
    {
        return [
            'payable_ledger_id',
            'supplier_id',
            'business_source_id',
            'reference_type',
            'reference_id',
            'entry_type',
            'amount',
            'direction',
            'description',
            'status',
            'created_by',
            'approved_by',
            'created_at',
            'approved_at',
        ];
    }

    private function plannedInvoiceFields()
    {
        return [
            'supplier_invoice_id',
            'supplier_id',
            'invoice_reference',
            'invoice_date',
            'total_amount',
            'status',
            'note',
            'created_by',
            'approved_by',
            'created_at',
        ];
    }

    private function plannedPaymentFields()
    {
        return [
            'supplier_payment_id',
            'supplier_id',
            'payment_method',
            'amount',
            'payment_reference',
            'payment_date',
            'note',
            'created_by',
            'approved_by',
            'created_at',
        ];
    }

    private function plannedDeductionFields()
    {
        return [
            'deduction_id',
            'supplier_id',
            'return_id',
            'deduction_type',
            'deduction_reason',
            'amount',
            'condition_status',
            'approved_by',
            'created_at',
        ];
    }
}
