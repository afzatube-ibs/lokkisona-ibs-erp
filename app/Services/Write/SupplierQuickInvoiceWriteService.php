<?php

namespace App\Services\Write;

use App\ActivityLog;
use App\Auth;
use App\SupplierContext;
use App\ReadFoundation\WriteGate;
use App\Repositories\Write\SupplierQuickInvoiceAuditWriteRepository;
use App\Repositories\Write\SupplierQuickInvoiceItemWriteRepository;
use App\Repositories\Write\SupplierQuickInvoiceWriteRepository;

class SupplierQuickInvoiceWriteService
{
    private SupplierQuickInvoiceWriteRepository $invoices;
    private SupplierQuickInvoiceItemWriteRepository $items;
    private SupplierQuickInvoiceAuditWriteRepository $audits;

    public function __construct(
        ?SupplierQuickInvoiceWriteRepository $invoices = null,
        ?SupplierQuickInvoiceItemWriteRepository $items = null,
        ?SupplierQuickInvoiceAuditWriteRepository $audits = null
    ) {
        $this->invoices = $invoices ?? new SupplierQuickInvoiceWriteRepository();
        $this->items = $items ?? new SupplierQuickInvoiceItemWriteRepository();
        $this->audits = $audits ?? new SupplierQuickInvoiceAuditWriteRepository();
    }

    public function create(array $input): WriteResult
    {
        if (!WriteGate::supplierQuickInvoice()['ready']) {
            return WriteResult::fail(WriteGate::WARNING_MESSAGE);
        }

        if (!$this->invoices->hasExtendedColumns()) {
            return WriteResult::fail('Migration 0010_supplier_quick_invoice_totals.sql must be applied after 0007 before generating professional quick invoices.');
        }

        $customerName = trim((string) ($input['customer_name'] ?? ''));
        if ($customerName === '') {
            return WriteResult::fail('Customer name is required.');
        }

        $lineItems = $this->parseLineItems($input['items'] ?? []);
        if ($lineItems === []) {
            return WriteResult::fail('At least one product line is required.');
        }

        $subtotal = 0.0;
        foreach ($lineItems as $line) {
            $subtotal += $line['line_total'];
        }
        $subtotal = round($subtotal, 2);

        $discount = round(max(0, (float) ($input['discount_amount'] ?? 0)), 2);
        if ($discount > $subtotal) {
            return WriteResult::fail('Discount cannot exceed subtotal.');
        }

        $afterDiscount = round($subtotal - $discount, 2);
        $advance = round(max(0, (float) ($input['advance_amount'] ?? 0)), 2);
        if ($advance > $afterDiscount) {
            return WriteResult::fail('Advance cannot exceed amount after discount.');
        }

        $balanceDue = round($afterDiscount - $advance, 2);
        $reference = 'SQI-' . date('YmdHis') . '-' . random_int(100, 999);
        $invoiceDate = trim((string) ($input['invoice_date'] ?? ''));
        if ($invoiceDate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $invoiceDate)) {
            return WriteResult::fail('Valid invoice date is required.');
        }
        $generatedAt = $invoiceDate . ' 00:00:00';
        $supplierId = SupplierContext::isSupplier() ? SupplierContext::supplierId() : null;

        $invoiceId = $this->invoices->create([
            'supplier_id' => $supplierId,
            'quick_invoice_reference' => $reference,
            'supplier_name' => 'Iqbal & Brothers',
            'customer_name' => $customerName,
            'customer_phone' => trim((string) ($input['customer_phone'] ?? '')) ?: null,
            'customer_address' => trim((string) ($input['customer_address'] ?? '')) ?: null,
            'invoice_total' => $afterDiscount,
            'subtotal' => $subtotal,
            'discount_amount' => $discount,
            'advance_amount' => $advance,
            'balance_due' => $balanceDue,
            'notes' => trim((string) ($input['notes'] ?? '')) ?: null,
            'output_status' => 'generated',
            'created_by' => null,
            'generated_at' => $generatedAt,
        ]);

        foreach ($lineItems as $line) {
            $this->items->create([
                'supplier_quick_invoice_id' => $invoiceId,
                'item_name' => $line['name'],
                'quantity' => $line['quantity'],
                'unit_price' => $line['unit_price'],
                'line_total' => $line['line_total'],
            ]);
        }

        $this->audits->append($invoiceId, 'generated', 'Quick invoice generated', [
            'reference' => $reference,
            'created_by' => Auth::user(),
            'role' => Auth::role(),
        ]);

        $this->grantSupplierPrintAccess($invoiceId);

        ActivityLog::record('supplier_quick_invoice_created', 'Supplier quick invoice generated (independent of ERP payable)', [
            'supplier_quick_invoice_id' => $invoiceId,
            'reference' => $reference,
            'balance_due' => $balanceDue,
        ]);

        return WriteResult::ok('Quick invoice ' . $reference . ' generated. Opening print view.', $invoiceId);
    }

    public function recordDownload(int $invoiceId): WriteResult
    {
        if ($invoiceId <= 0) {
            return WriteResult::fail('Invoice ID is required.');
        }

        $invoice = $this->invoices->find($invoiceId);
        if ($invoice === null) {
            return WriteResult::fail('Invoice not found.');
        }

        if (!$this->canAccessInvoice($invoiceId, $invoice)) {
            return WriteResult::fail('Supplier one-time access expired for this invoice.');
        }

        $this->invoices->markDownloaded($invoiceId);
        $this->audits->append($invoiceId, 'downloaded', 'Invoice printed or downloaded', [
            'user' => Auth::user(),
            'role' => Auth::role(),
        ]);

        $this->revokeSupplierPrintAccess($invoiceId);

        ActivityLog::record('supplier_quick_invoice_downloaded', 'Supplier quick invoice download logged', [
            'supplier_quick_invoice_id' => $invoiceId,
        ]);

        return WriteResult::ok('Download recorded.');
    }

    public function findForPrint(int $invoiceId): ?array
    {
        $invoice = $this->invoices->find($invoiceId);
        if ($invoice === null) {
            return null;
        }

        if (!$this->canAccessInvoice($invoiceId, $invoice)) {
            return null;
        }

        return [
            'invoice' => $invoice,
            'items' => $this->items->forInvoice($invoiceId),
        ];
    }

    public function recent(int $limit = 20): array
    {
        if (!$this->invoices->tableExists()) {
            return [];
        }

        return $this->invoices->listRecent($limit);
    }

    public function recentForSupplier(int $supplierId, int $limit = 20): array
    {
        if (!$this->invoices->tableExists() || $supplierId <= 0) {
            return [];
        }

        return $this->invoices->listRecentForSupplier($supplierId, $limit);
    }

    private function parseLineItems($raw): array
    {
        if (!is_array($raw)) {
            return [];
        }

        $lines = [];
        foreach ($raw as $row) {
            if (!is_array($row)) {
                continue;
            }
            $name = trim((string) ($row['name'] ?? ''));
            $qty = (int) ($row['qty'] ?? 0);
            $unitPrice = round((float) ($row['unit_price'] ?? 0), 2);
            if ($name === '' || $qty <= 0) {
                continue;
            }
            $lines[] = [
                'name' => $name,
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'line_total' => round($qty * $unitPrice, 2),
            ];
        }

        return $lines;
    }

    public function canAccessInvoice(int $invoiceId, ?array $invoice = null): bool
    {
        $role = Auth::role();
        if (in_array($role, ['owner', 'admin'], true)) {
            return true;
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $allowed = $_SESSION['supplier_quick_invoice_access'][$invoiceId] ?? null;
        if ($allowed === true) {
            return true;
        }

        $invoice = $invoice ?? $this->invoices->find($invoiceId);
        if ($invoice === null) {
            return false;
        }

        return empty($invoice['supplier_access_closed_at']) && ($invoice['output_status'] ?? '') === 'generated';
    }

    private function grantSupplierPrintAccess(int $invoiceId): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        if (!isset($_SESSION['supplier_quick_invoice_access'])) {
            $_SESSION['supplier_quick_invoice_access'] = [];
        }
        $_SESSION['supplier_quick_invoice_access'][$invoiceId] = true;
    }

    private function revokeSupplierPrintAccess(int $invoiceId): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        unset($_SESSION['supplier_quick_invoice_access'][$invoiceId]);
    }
}
