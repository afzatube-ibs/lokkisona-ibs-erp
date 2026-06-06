<?php

namespace App\Controllers;

use App\ActivityLog;
use App\Auth;
use App\Csrf;
use App\Permission;
use App\SupplierContext;
use App\ReadFoundation\WriteGate;
use App\Services\Write\SupplierQuickInvoiceWriteService;

class SupplierToolsController extends Controller
{
    public function index()
    {
        if (!Permission::can('supplier_tools.view') && !Permission::can('supplier_quick_invoice.manage')) {
            $this->authorize('supplier_tools.view');
        }
        ActivityLog::record('supplier_tools_access', SupplierContext::isSupplier() ? 'Offline Invoices page viewed' : 'Supplier Tools page viewed');

        $service = new SupplierQuickInvoiceWriteService();
        $isSupplierView = SupplierContext::isSupplier();
        $isOwnerView = in_array(Auth::role(), ['owner', 'admin'], true);
        $supplierId = $isSupplierView ? SupplierContext::supplierId() : 0;
        $quickInvoiceLog = $isSupplierView
            ? $service->recentForSupplier($supplierId, 30)
            : ($isOwnerView ? $service->recent(20) : []);

        $this->render('supplier-tools.index', [
            'pageTitle' => $isSupplierView ? 'Offline Invoices' : 'Supplier Tools',
            'breadcrumbs' => [
                ['label' => 'Operations', 'active' => false],
                ['label' => $isSupplierView ? 'Offline Invoices' : 'Supplier Tools', 'active' => true],
            ],
            'accessMode' => Permission::accessMode(),
            'quickInvoiceLog' => $quickInvoiceLog,
            'showInvoiceLog' => $quickInvoiceLog !== [] || $isSupplierView || $isOwnerView,
            'showOwnerLog' => $isOwnerView,
            'isSupplierView' => $isSupplierView,
            'writeGate' => WriteGate::supplierQuickInvoice(),
            'writeGateReady' => WriteGate::supplierQuickInvoice()['ready'],
            'flashSuccess' => $this->pullFlash('success'),
            'flashError' => $this->pullFlash('error'),
            'csrfField' => Csrf::field(),
            'currentContext' => $this->currentContext(),
            'purpose' => $this->purpose(),
            'independentSafetyRule' => $this->independentSafetyRule(),
            'quickInvoicePlan' => $this->quickInvoicePlan(),
            'oneTimeAccessRule' => $this->oneTimeAccessRule(),
            'adminAuditRule' => $this->adminAuditRule(),
            'calculatorPlan' => $this->calculatorPlan(),
            'noAccountingImpactRule' => $this->noAccountingImpactRule(),
            'futurePermissionPlan' => $this->futurePermissionPlan(),
            'futureAuditPlan' => $this->futureAuditPlan(),
            'plannedQuickInvoiceFields' => $this->plannedQuickInvoiceFields(),
            'plannedQuickInvoiceItemFields' => $this->plannedQuickInvoiceItemFields(),
            'plannedQuickInvoiceAuditFields' => $this->plannedQuickInvoiceAuditFields(),
        ]);
    }

    public function quickInvoice()
    {
        $this->authorize('supplier_quick_invoice.manage');
        $this->requirePost();
        $returnPath = SupplierContext::isSupplier() ? '/supplier-tools' : '/dashboard';
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Invalid security token.');
            redirect($returnPath);
        }

        $result = (new SupplierQuickInvoiceWriteService())->create($_POST);
        if ($result->success && $result->id) {
            redirect('/supplier-tools/quick-invoice/print/' . $result->id);
        }

        $this->flash($result->success ? 'success' : 'error', $result->message);
        redirect($returnPath);
    }

    public function printQuickInvoice($id)
    {
        $this->authorize('supplier_quick_invoice.manage');
        $invoiceId = (int) $id;
        $service = new SupplierQuickInvoiceWriteService();
        $data = $service->findForPrint($invoiceId);

        if ($data === null) {
            http_response_code(403);
            $this->render('errors.403', [
                'pageTitle' => 'Access Denied',
                'breadcrumbs' => [
                    ['label' => 'Operations', 'active' => false],
                    ['label' => 'Access Denied', 'active' => true],
                ],
                'permission' => 'supplier_quick_invoice one-time access',
            ]);
            exit;
        }

        view('supplier-tools.print', [
            'invoice' => $data['invoice'],
            'items' => $data['items'],
            'csrfField' => Csrf::field(),
        ]);
        exit;
    }

    public function logDownload()
    {
        $this->authorize('supplier_quick_invoice.manage');
        $this->requirePost();
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Invalid security token.');
            redirect('/dashboard');
        }

        $id = (int) ($_POST['supplier_quick_invoice_id'] ?? 0);
        $result = (new SupplierQuickInvoiceWriteService())->recordDownload($id);
        $this->flash($result->success ? 'success' : 'error', $result->message);
        redirect('/dashboard');
    }

    private function currentContext()
    {
        return [
            'primarySupplier' => 'Iqbal & Brothers',
            'summary' => 'Supplier engagement tools — calculator and quick invoice generator available from the topbar and supplier dashboard. Independent of official ERP financial workflow.',
        ];
    }

    private function purpose()
    {
        return [
            'Lightweight supplier engagement tools without connecting them to official ERP workflows.',
            'Calculator opens from header icon — standalone, no system impact.',
            'Quick invoice supports multiple products, discount, advance, and professional print layout.',
            'Keep Supplier Tools separate from official ERP invoices, payables, settlements, product cost, stock, courier, orders, dispatch, accounting, returns, and sync/import.',
        ];
    }

    private function independentSafetyRule()
    {
        return [
            'title' => 'Independent Tool Safety Rule',
            'summary' => 'Supplier Tools are fully independent engagement tools only.',
            'points' => [
                'No connection to official ERP invoice printing.',
                'No connection to supplier payable or settlement.',
                'No connection to product cost or vendor stock.',
                'No connection to courier charge, orders, dispatch, accounting, return receive, or sync/import.',
                'Owner/admin may review generated quick invoices; no automatic conversion.',
            ],
        ];
    }

    private function quickInvoicePlan()
    {
        return [
            'title' => 'Supplier Quick Invoice Generator',
            'summary' => 'Multi-line invoice with subtotal, discount, advance, balance due, and print layout.',
            'points' => [
                'Independent tool only — no ERP order or payable impact.',
                'Supplier one-time access: generate → print → access closed.',
                'Owner/admin sees history on this page when migrations 0007 + 0010 are applied.',
                'Requires table gate Ready before modal form is enabled.',
            ],
        ];
    }

    private function oneTimeAccessRule()
    {
        return [
            'title' => 'One-Time Supplier Invoice Access Rule',
            'summary' => 'Supplier-created quick invoices are one-time output, not an editable history area.',
            'points' => [
                'Supplier can create and print/download after creation.',
                'Supplier cannot reopen after download — supplier_access_closed_at is set.',
                'Owner/admin backend can still see audit/history/details.',
            ],
        ];
    }

    private function adminAuditRule()
    {
        return [
            'title' => 'Admin Audit / History Rule',
            'summary' => 'Owner/admin keeps visibility into generated quick invoices without making them official ERP documents.',
            'points' => [
                'Every generated invoice has an audit record.',
                'Owner/admin backend can see history and details on /supplier-tools.',
                'Review/conversion is owner/admin controlled and never automatic.',
            ],
        ];
    }

    private function calculatorPlan()
    {
        return [
            'title' => 'Calculator',
            'summary' => 'Full keypad calculator in header modal — no database writes.',
            'points' => [
                'Available from topbar icon on all authenticated pages.',
                'No payable, settlement, product cost, or courier calculations saved.',
                'No ERP accounting impact.',
            ],
        ];
    }

    private function noAccountingImpactRule()
    {
        return [
            'title' => 'No ERP Accounting Impact Rule',
            'summary' => 'Supplier Tools must never mutate official ERP financial state.',
            'points' => [
                'Quick invoices do not affect payable, settlement, stock, orders, dispatch, courier, returns, sync/import, or accounting.',
                'Calculator output is temporary and not saved to ERP accounting.',
                'Official ERP invoice and payable workflows remain the source of truth.',
            ],
        ];
    }

    private function futurePermissionPlan()
    {
        return [
            'supplier_calculator.view — header calculator icon.',
            'supplier_quick_invoice.manage — header invoice icon and generator modal.',
            'Supplier role has both permissions for dashboard engagement.',
        ];
    }

    private function futureAuditPlan()
    {
        return [
            'Quick invoice generation, download, and access close are audited in supplier_quick_invoice_audits.',
            'Activity log records high-level supplier_quick_invoice events.',
            'Calculator use does not require database writes.',
        ];
    }

    private function plannedQuickInvoiceFields()
    {
        return [
            'supplier_quick_invoice_id',
            'supplier_id',
            'quick_invoice_reference',
            'supplier_name',
            'customer_name',
            'customer_phone',
            'customer_address',
            'subtotal',
            'discount_amount',
            'advance_amount',
            'balance_due',
            'invoice_total',
            'notes',
            'output_status',
            'supplier_access_closed_at',
        ];
    }

    private function plannedQuickInvoiceItemFields()
    {
        return [
            'supplier_quick_invoice_item_id',
            'supplier_quick_invoice_id',
            'item_name',
            'quantity',
            'unit_price',
            'line_total',
        ];
    }

    private function plannedQuickInvoiceAuditFields()
    {
        return [
            'supplier_quick_invoice_audit_id',
            'supplier_quick_invoice_id',
            'action',
            'user_id',
            'message',
            'context_json',
        ];
    }
}

