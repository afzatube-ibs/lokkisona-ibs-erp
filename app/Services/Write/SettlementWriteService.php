<?php

namespace App\Services\Write;

use App\ActivityLog;
use App\Database\Connection;
use App\Domain\PayableLedgerType;
use App\Domain\SettlementWorkflowStatus;
use App\ReadFoundation\WriteGate;
use App\Repositories\Write\SettlementWriteRepository;

class SettlementWriteService
{
    private SettlementWriteRepository $settlements;

    public function __construct(?SettlementWriteRepository $settlements = null)
    {
        $this->settlements = $settlements ?? new SettlementWriteRepository();
    }

    public function prepare(array $input): WriteResult
    {
        if (!WriteGate::settlements()['ready']) {
            return WriteResult::fail(WriteGate::WARNING_MESSAGE);
        }

        $supplierId = (int) ($input['supplier_id'] ?? 0);
        if ($supplierId <= 0) {
            return WriteResult::fail('Supplier is required.');
        }

        $periodType = trim((string) ($input['period_type'] ?? 'monthly'));
        if (!isset(SettlementWorkflowStatus::periodTypes()[$periodType])) {
            return WriteResult::fail('Invalid settlement period type.');
        }

        [$periodStart, $periodEnd] = $this->resolvePeriodDates($periodType, $input);
        if ($periodStart === null || $periodEnd === null) {
            return WriteResult::fail('Period start and end dates are required for custom periods.');
        }

        $totals = $this->aggregateLedgerTotals($supplierId, $periodStart, $periodEnd);
        $ref = 'SET-' . date('Ymd') . '-' . $supplierId . '-' . random_int(100, 999);

        $id = $this->settlements->create([
            'supplier_id' => $supplierId,
            'settlement_reference' => $ref,
            'period_type' => $periodType,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'opening_balance' => $totals['opening_balance'],
            'dispatch_payable' => $totals['dispatch_payable'],
            'invoice_total' => $totals['invoice_total'],
            'deductions' => $totals['deductions'],
            'payments' => $totals['payments'],
            'advances' => $totals['advances'],
            'adjustments' => $totals['adjustments'],
            'closing_balance' => $totals['closing_balance'],
            'workflow_status' => SettlementWorkflowStatus::PREPARED,
            'prepared_by' => null,
            'notes' => trim((string) ($input['notes'] ?? '')) ?: null,
        ]);

        ActivityLog::record('settlement_prepared', 'Settlement period prepared', [
            'settlement_id' => $id,
            'supplier_id' => $supplierId,
            'period_type' => $periodType,
        ]);

        return WriteResult::ok('Settlement prepared for period ' . $periodStart . ' to ' . $periodEnd . '.', $id);
    }

    public function approve(int $id): WriteResult
    {
        return $this->advanceWorkflow($id, SettlementWorkflowStatus::PREPARED, SettlementWorkflowStatus::APPROVED, 'approved_by', 'approved_at');
    }

    public function markPaid(int $id): WriteResult
    {
        return $this->advanceWorkflow($id, SettlementWorkflowStatus::APPROVED, SettlementWorkflowStatus::PAID, null, 'paid_at');
    }

    public function close(int $id): WriteResult
    {
        return $this->advanceWorkflow($id, SettlementWorkflowStatus::PAID, SettlementWorkflowStatus::CLOSED, null, 'closed_at');
    }

    private function advanceWorkflow(int $id, string $expected, string $next, ?string $actorField, ?string $timeField): WriteResult
    {
        if (!WriteGate::settlements()['ready']) {
            return WriteResult::fail(WriteGate::WARNING_MESSAGE);
        }

        if ($id <= 0) {
            return WriteResult::fail('Settlement ID is required.');
        }

        $row = $this->settlements->find($id);
        if ($row === null) {
            return WriteResult::fail('Settlement not found.');
        }

        if ((string) ($row['workflow_status'] ?? '') !== $expected) {
            return WriteResult::fail('Settlement must be in ' . $expected . ' status before this action.');
        }

        $extra = ['workflow_status' => $next];
        if ($actorField !== null) {
            $extra[$actorField] = null;
        }
        if ($timeField !== null) {
            $extra[$timeField] = date('Y-m-d H:i:s');
        }

        if (!$this->settlements->updateWorkflow($id, $next, $extra)) {
            return WriteResult::fail('Could not update settlement workflow.');
        }

        ActivityLog::record('settlement_workflow', 'Settlement workflow advanced to ' . $next, [
            'settlement_id' => $id,
            'from' => $expected,
            'to' => $next,
        ]);

        return WriteResult::ok('Settlement moved to ' . (SettlementWorkflowStatus::labels()[$next] ?? $next) . '.');
    }

    private function resolvePeriodDates(string $periodType, array $input): array
    {
        $today = new \DateTimeImmutable('today');
        if ($periodType === 'custom') {
            $start = trim((string) ($input['period_start'] ?? ''));
            $end = trim((string) ($input['period_end'] ?? ''));

            return [$start !== '' ? $start : null, $end !== '' ? $end : null];
        }

        if ($periodType === 'daily') {
            $d = $today->format('Y-m-d');

            return [$d, $d];
        }

        if ($periodType === 'weekly') {
            $start = $today->modify('monday this week');
            $end = $today->modify('sunday this week');

            return [$start->format('Y-m-d'), $end->format('Y-m-d')];
        }

        if ($periodType === 'first_half') {
            return [$today->format('Y-m-01'), $today->format('Y-m-15')];
        }

        if ($periodType === 'second_half') {
            return [$today->format('Y-m-16'), $today->format('Y-m-t')];
        }

        return [$today->format('Y-m-01'), $today->format('Y-m-t')];
    }

    private function aggregateLedgerTotals(int $supplierId, string $periodStart, string $periodEnd): array
    {
        $prefix = (string) config('database.prefix', 'ibs_');
        $table = $prefix . 'payable_ledgers';
        $pdo = Connection::pdo();

        $sql = 'SELECT ledger_type, SUM(debit_amount) AS debit_sum, SUM(credit_amount) AS credit_sum '
            . 'FROM `' . str_replace('`', '``', $table) . '` '
            . 'WHERE supplier_id = :supplier_id AND status = :status '
            . 'AND DATE(created_at) BETWEEN :start AND :end GROUP BY ledger_type';
        $statement = $pdo->prepare($sql);
        $statement->execute([
            'supplier_id' => $supplierId,
            'status' => 'posted',
            'start' => $periodStart,
            'end' => $periodEnd,
        ]);
        $rows = $statement->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        $dispatch = 0.0;
        $invoice = 0.0;
        $deductions = 0.0;
        $payments = 0.0;
        $advances = 0.0;
        $adjustments = 0.0;
        $opening = 0.0;

        foreach ($rows as $row) {
            $type = (string) ($row['ledger_type'] ?? '');
            $debit = (float) ($row['debit_sum'] ?? 0);
            $credit = (float) ($row['credit_sum'] ?? 0);
            $amount = $debit > 0 ? $debit : $credit;

            match ($type) {
                PayableLedgerType::OPENING_BALANCE => $opening += $amount,
                PayableLedgerType::PRODUCT_COST_PAYABLE => $dispatch += $amount,
                PayableLedgerType::SUPPLIER_INVOICE, PayableLedgerType::ADDITIONAL_PAYABLE => $invoice += $amount,
                PayableLedgerType::RETURN_DEDUCTION => $deductions += $amount,
                PayableLedgerType::PAYMENT_MADE => $payments += $amount,
                PayableLedgerType::ADVANCE_RECEIVED => $advances += $amount,
                PayableLedgerType::DEBIT_ADJUSTMENT, PayableLedgerType::CREDIT_ADJUSTMENT => $adjustments += $amount,
                default => null,
            };
        }

        $closing = round($opening + $dispatch + $invoice - $deductions - $payments - $advances + $adjustments, 2);

        return [
            'opening_balance' => round($opening, 2),
            'dispatch_payable' => round($dispatch, 2),
            'invoice_total' => round($invoice, 2),
            'deductions' => round($deductions, 2),
            'payments' => round($payments, 2),
            'advances' => round($advances, 2),
            'adjustments' => round($adjustments, 2),
            'closing_balance' => $closing,
        ];
    }
}
