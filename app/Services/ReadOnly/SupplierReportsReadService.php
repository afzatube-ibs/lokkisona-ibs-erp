<?php

namespace App\Services\ReadOnly;

use App\ActivityLog;
use App\Database\Connection;
use App\Database\QueryGuard;
use App\Domain\PayableLedgerType;
use App\Repositories\DispatchReportRepository;
use App\Repositories\ReturnReceiveRepository;
use App\Services\ReadOnly\PayableLedgerReadService;
use PDO;

class SupplierReportsReadService
{
    private PDO $pdo;
    private string $prefix;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Connection::pdo();
        $this->prefix = (string) config('database.prefix', 'ibs_');
    }

    public function definitions(): array
    {
        return [
            'supplier_ledger' => ['title' => 'Supplier Ledger Report', 'group' => 'Financial'],
            'dispatch_payable' => ['title' => 'Dispatch Payable Report', 'group' => 'Dispatch'],
            'product_dispatch' => ['title' => 'Product-wise Dispatch Report', 'group' => 'Dispatch'],
            'hub_return' => ['title' => 'Hub Return / Courier Return Report', 'group' => 'Returns'],
            'customer_return' => ['title' => 'Customer Return Report', 'group' => 'Returns'],
            'vendor_return' => ['title' => 'Vendor Return Report', 'group' => 'Returns'],
            'owner_return' => ['title' => 'Owner / Lokkisona Return Report', 'group' => 'Returns'],
            'return_deduction' => ['title' => 'Return Deduction Report', 'group' => 'Financial'],
            'monthly_payable' => ['title' => 'Monthly Payable Report', 'group' => 'Financial'],
            'supplier_statement' => ['title' => 'Supplier Statement', 'group' => 'Financial'],
            'settlement_summary' => ['title' => 'Settlement Report', 'group' => 'Financial'],
            'payment_history' => ['title' => 'Payment History', 'group' => 'Financial'],
            'manual_entries' => ['title' => 'Manual Account Entry Report', 'group' => 'Financial'],
            'supplier_invoice' => ['title' => 'Supplier Invoice Report', 'group' => 'Financial'],
            'activity_log' => ['title' => 'Activity Log', 'group' => 'Audit'],
        ];
    }

    public function run(string $key, int $supplierId = 0, string $month = ''): array
    {
        if (!isset($this->definitions()[$key])) {
            return $this->emptyReport('Unknown report', 'Report key not found.');
        }

        return match ($key) {
            'supplier_ledger' => $this->supplierLedgerReport($supplierId),
            'dispatch_payable' => $this->dispatchPayableReport($supplierId),
            'product_dispatch' => $this->productDispatchReport($supplierId),
            'hub_return' => $this->returnReport('hub_courier_return', 'Hub Return / Courier Return Report', $supplierId),
            'customer_return' => $this->returnReport('customer_return_to_supplier', 'Customer Return Report', $supplierId),
            'vendor_return' => $this->vendorReturnReport($supplierId),
            'owner_return' => $this->returnReport('lokkisona_warehouse_return', 'Owner / Lokkisona Return Report', $supplierId),
            'return_deduction' => $this->ledgerTypeReport(PayableLedgerType::RETURN_DEDUCTION, 'Return Deduction Report', $supplierId),
            'monthly_payable' => $this->monthlyPayableReport($supplierId, $month),
            'supplier_statement' => $this->supplierStatement($supplierId),
            'settlement_summary' => $this->settlementSummary($supplierId),
            'payment_history' => $this->ledgerTypeReport(PayableLedgerType::PAYMENT_MADE, 'Payment History', $supplierId),
            'manual_entries' => $this->manualEntriesReport($supplierId),
            'supplier_invoice' => $this->ledgerTypeReport(PayableLedgerType::SUPPLIER_INVOICE, 'Supplier Invoice Report', $supplierId),
            'activity_log' => $this->activityLogReport(),
            default => $this->emptyReport('Report', 'Not available.'),
        };
    }

    private function supplierLedgerReport(int $supplierId): array
    {
        if (!$this->tableExists('payable_ledgers')) {
            return $this->emptyReport('Supplier Ledger Report', 'Payable ledger table not applied.');
        }

        $service = new PayableLedgerReadService();
        $rows = $supplierId > 0 ? $service->forSupplier($supplierId, 500) : $service->all(500);

        return [
            'title' => 'Supplier Ledger Report',
            'columns' => ['Date', 'Reference', 'Type', 'Debit', 'Credit', 'Balance', 'Status'],
            'rows' => array_map(static fn (array $r): array => [
                (string) ($r['created_at'] ?? ''),
                (string) ($r['ledger_reference'] ?? ''),
                (string) ($r['type_label'] ?? ''),
                (float) ($r['debit_amount'] ?? 0) > 0 ? number_format((float) $r['debit_amount'], 2) : '—',
                (float) ($r['credit_amount'] ?? 0) > 0 ? number_format((float) $r['credit_amount'], 2) : '—',
                ($r['balance_after'] ?? null) !== null ? number_format((float) $r['balance_after'], 2) : '—',
                (string) ($r['status'] ?? ''),
            ], $rows),
            'summary' => 'Entries: ' . count($rows),
            'empty_message' => 'No ledger entries found.',
        ];
    }

    private function dispatchPayableReport(int $supplierId): array
    {
        if (!$this->tableExists('dispatch_reports')) {
            return $this->emptyReport('Dispatch Payable Report', 'Dispatch tables not applied.');
        }

        $repo = new DispatchReportRepository();
        $reports = $repo->latest(50);
        if ($supplierId > 0) {
            $reports = array_values(array_filter($reports, static fn (array $r): bool => (int) ($r['supplier_id'] ?? 0) === $supplierId));
        }

        $rows = [];
        foreach ($reports as $report) {
            $ref = (string) ($report['dispatch_reference'] ?? '');
            $payable = $this->payableForDispatch($ref);
            $rows[] = [
                (string) ($report['dispatch_date'] ?? $report['created_at'] ?? ''),
                $ref,
                (string) ($report['total_orders'] ?? 0),
                number_format((float) ($report['total_product_cost'] ?? 0), 2),
                (string) ($report['status'] ?? ''),
                $payable !== null ? (string) ($payable['status'] ?? 'no draft') : 'no payable',
            ];
        }

        return [
            'title' => 'Dispatch Payable Report',
            'columns' => ['Date', 'Dispatch Ref', 'Orders', 'Cost Snapshot', 'Report Status', 'Payable Status'],
            'rows' => $rows,
            'summary' => 'Dispatch reports: ' . count($rows) . ' — cost snapshots are immutable.',
            'empty_message' => 'No dispatch reports found.',
        ];
    }

    private function productDispatchReport(int $supplierId): array
    {
        if (!$this->tableExists('dispatch_report_items') || !$this->tableExists('orders')) {
            return $this->emptyReport('Product-wise Dispatch Report', 'Dispatch or order tables not applied.');
        }

        $sql = 'SELECT r.dispatch_reference, o.order_reference, i.product_cost_snapshot, i.item_count, i.created_at '
            . 'FROM `' . $this->escape($this->prefix . 'dispatch_report_items') . '` i '
            . 'INNER JOIN `' . $this->escape($this->prefix . 'dispatch_reports') . '` r ON r.dispatch_report_id = i.dispatch_report_id '
            . 'LEFT JOIN `' . $this->escape($this->prefix . 'orders') . '` o ON o.order_id = i.order_id '
            . ($supplierId > 0 ? 'WHERE r.supplier_id = :supplier_id ' : '')
            . 'ORDER BY i.dispatch_report_item_id DESC LIMIT 200';
        QueryGuard::assertReadOnly($sql);
        $statement = $this->pdo->prepare($sql);
        if ($supplierId > 0) {
            $statement->execute(['supplier_id' => $supplierId]);
        } else {
            $statement->execute();
        }
        $data = $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $rows = array_map(static fn (array $r): array => [
            (string) ($r['dispatch_reference'] ?? ''),
            (string) ($r['order_reference'] ?? ''),
            (string) ($r['item_count'] ?? 0),
            number_format((float) ($r['product_cost_snapshot'] ?? 0), 2),
            (string) ($r['created_at'] ?? ''),
        ], $data);

        return [
            'title' => 'Product-wise Dispatch Report',
            'columns' => ['Dispatch Ref', 'Order No', 'Qty', 'Line Cost Snapshot', 'Created'],
            'rows' => $rows,
            'summary' => 'Order-level dispatch lines: ' . count($rows),
            'empty_message' => 'No dispatch line items found.',
        ];
    }

    private function returnReport(string $type, string $title, int $supplierId): array
    {
        if (!$this->tableExists('return_receives')) {
            return $this->emptyReport($title, 'Return receive table not applied.');
        }

        $repo = new ReturnReceiveRepository();
        $all = $repo->all(100, 0);
        $filtered = array_values(array_filter($all, static function (array $r) use ($type, $supplierId): bool {
            if (($r['return_type'] ?? '') !== $type) {
                return false;
            }
            if ($supplierId > 0 && (int) ($r['supplier_id'] ?? 0) !== $supplierId) {
                return false;
            }

            return true;
        }));

        $rows = array_map(static fn (array $r): array => [
            (string) ($r['return_reference'] ?? ''),
            (string) ($r['return_type'] ?? ''),
            (string) ($r['total_items'] ?? 0),
            number_format((float) ($r['total_cost_snapshot'] ?? 0), 2),
            (string) ($r['status'] ?? ''),
            (string) ($r['received_at'] ?? $r['created_at'] ?? ''),
        ], $filtered);

        return [
            'title' => $title,
            'columns' => ['Return Ref', 'Type', 'Items', 'Cost Snapshot', 'Status', 'Received'],
            'rows' => $rows,
            'summary' => 'Returns: ' . count($rows),
            'empty_message' => 'No returns found for this report.',
        ];
    }

    private function vendorReturnReport(int $supplierId): array
    {
        $hub = $this->returnReport('hub_courier_return', 'Hub', $supplierId);
        $customer = $this->returnReport('customer_return_to_supplier', 'Customer', $supplierId);

        return [
            'title' => 'Vendor Return Report',
            'columns' => ['Return Ref', 'Type', 'Items', 'Cost Snapshot', 'Status', 'Received'],
            'rows' => array_merge($hub['rows'], $customer['rows']),
            'summary' => 'Supplier-side returns (Hub + Customer to Supplier): ' . (count($hub['rows']) + count($customer['rows'])),
            'empty_message' => 'No vendor returns found.',
        ];
    }

    private function ledgerTypeReport(string $ledgerType, string $title, int $supplierId): array
    {
        if (!$this->tableExists('payable_ledgers')) {
            return $this->emptyReport($title, 'Payable ledger table not applied.');
        }

        $sql = 'SELECT * FROM `' . $this->escape($this->prefix . 'payable_ledgers') . '` WHERE ledger_type = :type '
            . ($supplierId > 0 ? 'AND supplier_id = :supplier_id ' : '')
            . 'ORDER BY payable_ledger_id DESC LIMIT 200';
        QueryGuard::assertReadOnly($sql);
        $statement = $this->pdo->prepare($sql);
        $params = ['type' => $ledgerType];
        if ($supplierId > 0) {
            $params['supplier_id'] = $supplierId;
        }
        $statement->execute($params);
        $data = $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $rows = array_map(static fn (array $r): array => [
            (string) ($r['created_at'] ?? ''),
            (string) ($r['ledger_reference'] ?? ''),
            (string) ($r['source_reference'] ?? ''),
            number_format((float) ($r['debit_amount'] ?? 0), 2),
            number_format((float) ($r['credit_amount'] ?? 0), 2),
            (string) ($r['status'] ?? ''),
        ], $data);

        return [
            'title' => $title,
            'columns' => ['Date', 'Reference', 'Source', 'Debit', 'Credit', 'Status'],
            'rows' => $rows,
            'summary' => PayableLedgerType::label($ledgerType) . ' entries: ' . count($rows),
            'empty_message' => 'No entries found.',
        ];
    }

    private function monthlyPayableReport(int $supplierId, string $month): array
    {
        if (!$this->tableExists('payable_ledgers')) {
            return $this->emptyReport('Monthly Payable Report', 'Payable ledger table not applied.');
        }

        $month = $month !== '' ? $month : date('Y-m');
        $sql = 'SELECT * FROM `' . $this->escape($this->prefix . 'payable_ledgers') . '` '
            . 'WHERE status = :status AND DATE_FORMAT(created_at, \'%Y-%m\') = :month '
            . ($supplierId > 0 ? 'AND supplier_id = :supplier_id ' : '')
            . 'ORDER BY payable_ledger_id ASC';
        QueryGuard::assertReadOnly($sql);
        $statement = $this->pdo->prepare($sql);
        $params = ['status' => 'posted', 'month' => $month];
        if ($supplierId > 0) {
            $params['supplier_id'] = $supplierId;
        }
        $statement->execute($params);
        $data = $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $totalDebit = 0.0;
        $totalCredit = 0.0;
        foreach ($data as $row) {
            $totalDebit += (float) ($row['debit_amount'] ?? 0);
            $totalCredit += (float) ($row['credit_amount'] ?? 0);
        }

        $rows = array_map(static fn (array $r): array => [
            (string) ($r['created_at'] ?? ''),
            PayableLedgerType::label((string) ($r['ledger_type'] ?? '')),
            (string) ($r['ledger_reference'] ?? ''),
            number_format((float) ($r['debit_amount'] ?? 0), 2),
            number_format((float) ($r['credit_amount'] ?? 0), 2),
            ($r['balance_after'] ?? null) !== null ? number_format((float) $r['balance_after'], 2) : '—',
        ], $data);

        return [
            'title' => 'Monthly Payable Report — ' . $month,
            'columns' => ['Date', 'Type', 'Reference', 'Debit', 'Credit', 'Balance'],
            'rows' => $rows,
            'summary' => 'Posted entries in ' . $month . ': ' . count($rows)
                . ' | Debit: ' . number_format($totalDebit, 2)
                . ' | Credit: ' . number_format($totalCredit, 2),
            'empty_message' => 'No posted entries for this month.',
        ];
    }

    private function supplierStatement(int $supplierId): array
    {
        $ledger = $this->supplierLedgerReport($supplierId);
        $ledger['title'] = 'Supplier Statement';
        $balance = $supplierId > 0
            ? (new PayableLedgerReadService())->currentBalanceForSupplier($supplierId)
            : (float) ($ledger['rows'][0][5] ?? 0);

        $ledger['summary'] = 'Net Payable Balance: ' . number_format($balance, 2) . ' BDT | ' . $ledger['summary'];

        return $ledger;
    }

    private function settlementSummary(int $supplierId): array
    {
        if (!$this->tableExists('payable_ledgers')) {
            return $this->emptyReport('Settlement Report', 'Payable ledger table not applied.');
        }

        $service = new PayableLedgerReadService();
        $rows = $supplierId > 0 ? $service->forSupplier($supplierId, 500) : $service->all(500);

        $opening = 0.0;
        $productCost = 0.0;
        $invoices = 0.0;
        $additional = 0.0;
        $deductions = 0.0;
        $payments = 0.0;
        $advances = 0.0;
        $debitAdj = 0.0;
        $creditAdj = 0.0;

        foreach ($rows as $row) {
            if (($row['status'] ?? '') !== 'posted') {
                continue;
            }
            $type = (string) ($row['ledger_type'] ?? '');
            $debit = (float) ($row['debit_amount'] ?? 0);
            $credit = (float) ($row['credit_amount'] ?? 0);
            match ($type) {
                PayableLedgerType::OPENING_BALANCE => $opening += $debit - $credit,
                PayableLedgerType::PRODUCT_COST_PAYABLE => $productCost += $debit,
                PayableLedgerType::SUPPLIER_INVOICE => $invoices += $debit,
                PayableLedgerType::ADDITIONAL_PAYABLE => $additional += $debit,
                PayableLedgerType::RETURN_DEDUCTION => $deductions += $credit,
                PayableLedgerType::PAYMENT_MADE => $payments += $credit,
                PayableLedgerType::ADVANCE_RECEIVED => $advances += $credit,
                PayableLedgerType::DEBIT_ADJUSTMENT => $debitAdj += $debit,
                PayableLedgerType::CREDIT_ADJUSTMENT => $creditAdj += $credit,
                default => null,
            };
        }

        $closing = $supplierId > 0
            ? $service->currentBalanceForSupplier($supplierId)
            : round($opening + $productCost + $invoices + $additional + $debitAdj - $deductions - $payments - $advances - $creditAdj, 2);

        return [
            'title' => 'Settlement Report Summary',
            'columns' => ['Line Item', 'Amount (BDT)'],
            'rows' => [
                ['Opening Balance', number_format($opening, 2)],
                ['Product Cost Payable', number_format($productCost, 2)],
                ['Supplier Invoice', number_format($invoices, 2)],
                ['Additional Payable', number_format($additional, 2)],
                ['Debit Adjustment', number_format($debitAdj, 2)],
                ['Return Deduction', number_format($deductions, 2)],
                ['Payment Made', number_format($payments, 2)],
                ['Advance Received', number_format($advances, 2)],
                ['Credit Adjustment', number_format($creditAdj, 2)],
                ['Closing Balance / Net Payable', number_format($closing, 2)],
            ],
            'summary' => 'Settlement summary from posted ledger entries. Full settlement workflow (Draft → Paid) is planned for a later release.',
            'empty_message' => 'No posted entries for settlement summary.',
        ];
    }

    private function manualEntriesReport(int $supplierId): array
    {
        if (!$this->tableExists('payable_ledgers')) {
            return $this->emptyReport('Manual Account Entry Report', 'Payable ledger table not applied.');
        }

        $types = [
            PayableLedgerType::SUPPLIER_INVOICE,
            PayableLedgerType::ADDITIONAL_PAYABLE,
            PayableLedgerType::DEBIT_ADJUSTMENT,
            PayableLedgerType::CREDIT_ADJUSTMENT,
            PayableLedgerType::PAYMENT_MADE,
            PayableLedgerType::ADVANCE_RECEIVED,
        ];

        $placeholders = implode(',', array_fill(0, count($types), '?'));
        $sql = 'SELECT * FROM `' . $this->escape($this->prefix . 'payable_ledgers') . '` '
            . 'WHERE ledger_type IN (' . $placeholders . ') '
            . ($supplierId > 0 ? 'AND supplier_id = ? ' : '')
            . 'ORDER BY payable_ledger_id DESC LIMIT 200';
        QueryGuard::assertReadOnly($sql);
        $statement = $this->pdo->prepare($sql);
        $params = $types;
        if ($supplierId > 0) {
            $params[] = $supplierId;
        }
        $statement->execute($params);
        $data = $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $rows = array_map(static fn (array $r): array => [
            (string) ($r['created_at'] ?? ''),
            PayableLedgerType::label((string) ($r['ledger_type'] ?? '')),
            (string) ($r['ledger_reference'] ?? ''),
            number_format((float) ($r['debit_amount'] ?? 0), 2),
            number_format((float) ($r['credit_amount'] ?? 0), 2),
            (string) ($r['status'] ?? ''),
        ], $data);

        return [
            'title' => 'Manual Account Entry Report',
            'columns' => ['Date', 'Type', 'Reference', 'Debit', 'Credit', 'Status'],
            'rows' => $rows,
            'summary' => 'Manual / adjustment entries: ' . count($rows),
            'empty_message' => 'No manual entries found.',
        ];
    }

    private function activityLogReport(): array
    {
        $entries = ActivityLog::recent(100);
        $rows = [];
        foreach ($entries as $entry) {
            $rows[] = [
                (string) ($entry['timestamp'] ?? ''),
                (string) ($entry['event'] ?? ''),
                (string) ($entry['message'] ?? ''),
                (string) ($entry['user'] ?? ''),
            ];
        }

        return [
            'title' => 'Activity Log',
            'columns' => ['Timestamp', 'Event', 'Message', 'User'],
            'rows' => $rows,
            'summary' => 'Recent activity events: ' . count($rows),
            'empty_message' => 'No activity log entries.',
        ];
    }

    private function tableExists(string $logicalTable): bool
    {
        try {
            $database = config('database.database', '');
            $table = $this->prefix . $logicalTable;
            $sql = 'SELECT COUNT(*) AS table_count FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = :table';
            QueryGuard::assertReadOnly($sql);
            $statement = $this->pdo->prepare($sql);
            $statement->execute(['schema' => $database, 'table' => $table]);
            $row = $statement->fetch(PDO::FETCH_ASSOC);

            return ((int) ($row['table_count'] ?? 0)) > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function payableForDispatch(string $dispatchReference): ?array
    {
        if ($dispatchReference === '' || !$this->tableExists('payable_ledgers')) {
            return null;
        }

        $sql = 'SELECT status, ledger_reference, debit_amount FROM `' . $this->escape($this->prefix . 'payable_ledgers') . '` '
            . 'WHERE source_reference = :ref AND ledger_type = :type LIMIT 1';
        QueryGuard::assertReadOnly($sql);
        $statement = $this->pdo->prepare($sql);
        $statement->execute([
            'ref' => $dispatchReference,
            'type' => PayableLedgerType::PRODUCT_COST_PAYABLE,
        ]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }

    private function escape(string $identifier): string
    {
        return str_replace('`', '``', $identifier);
    }

    private function emptyReport(string $title, string $message): array
    {
        return [
            'title' => $title,
            'columns' => [],
            'rows' => [],
            'summary' => $message,
            'empty_message' => $message,
        ];
    }
}
