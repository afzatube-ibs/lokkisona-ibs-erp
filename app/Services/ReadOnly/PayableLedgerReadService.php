<?php

namespace App\Services\ReadOnly;

use App\Domain\PayableLedgerType;
use App\SupplierContext;
use App\Repositories\PayableLedgerRepository;
use App\Repositories\Write\PayableLedgerWriteRepository;

class PayableLedgerReadService
{
    private PayableLedgerRepository $repository;
    private PayableLedgerWriteRepository $writeRepository;

    public function __construct(
        ?PayableLedgerRepository $repository = null,
        ?PayableLedgerWriteRepository $writeRepository = null
    ) {
        $this->repository = $repository ?? new PayableLedgerRepository();
        $this->writeRepository = $writeRepository ?? new PayableLedgerWriteRepository();
    }

    public function tableExists(): bool
    {
        return $this->repository->tableExists();
    }

    public function findById(int $id): ?array
    {
        return $this->enrichRow($this->repository->findById($id));
    }

    public function all(int $limit = 100, int $offset = 0): array
    {
        return $this->enrichRows($this->repository->all($limit, $offset));
    }

    public function forSupplier(int $supplierId, int $limit = 200): array
    {
        return $this->enrichRows($this->writeRepository->listForSupplier($supplierId, $limit));
    }

    public function count(): int
    {
        return $this->repository->count();
    }

    public function countDrafts(): int
    {
        return $this->writeRepository->countByStatus('draft');
    }

    public function currentBalanceForSupplier(int $supplierId): float
    {
        return $this->writeRepository->getPostedBalanceForSupplier($supplierId);
    }

    public function summaryForSupplier(int $supplierId): array
    {
        if ($supplierId <= 0) {
            return [
                'draft_count' => 0,
                'posted_count' => 0,
                'net_payable' => 0.0,
                'total_debit_posted' => 0.0,
                'total_credit_posted' => 0.0,
            ];
        }

        $rows = $this->forSupplier($supplierId, 500);
        $draftCount = 0;
        $postedCount = 0;
        $totalDebit = 0.0;
        $totalCredit = 0.0;

        foreach ($rows as $row) {
            if (($row['status'] ?? '') === 'draft') {
                $draftCount++;
            }
            if (($row['status'] ?? '') === 'posted') {
                $postedCount++;
                $totalDebit += (float) ($row['debit_amount'] ?? 0);
                $totalCredit += (float) ($row['credit_amount'] ?? 0);
            }
        }

        $netPayable = $this->currentBalanceForSupplier($supplierId);

        return [
            'draft_count' => $draftCount,
            'posted_count' => $postedCount,
            'net_payable' => $netPayable,
            'total_debit_posted' => round($totalDebit, 2),
            'total_credit_posted' => round($totalCredit, 2),
        ];
    }

    public function summary(): array
    {
        $rows = $this->all(500, 0);
        $draftCount = 0;
        $postedCount = 0;
        $totalDebit = 0.0;
        $totalCredit = 0.0;

        foreach ($rows as $row) {
            if (($row['status'] ?? '') === 'draft') {
                $draftCount++;
            }
            if (($row['status'] ?? '') === 'posted') {
                $postedCount++;
                $totalDebit += (float) ($row['debit_amount'] ?? 0);
                $totalCredit += (float) ($row['credit_amount'] ?? 0);
            }
        }

        $latestBalance = 0.0;
        foreach ($rows as $row) {
            if (($row['status'] ?? '') === 'posted' && ($row['balance_after'] ?? null) !== null) {
                $latestBalance = (float) $row['balance_after'];
                break;
            }
        }

        return [
            'draft_count' => $draftCount,
            'posted_count' => $postedCount,
            'net_payable' => $latestBalance,
            'total_debit_posted' => round($totalDebit, 2),
            'total_credit_posted' => round($totalCredit, 2),
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    private function enrichRows(array $rows): array
    {
        return array_map(fn (array $row): array => $this->enrichRow($row) ?? $row, $rows);
    }

    private function enrichRow(?array $row): ?array
    {
        if ($row === null) {
            return null;
        }

        $type = (string) ($row['ledger_type'] ?? '');
        $source = (string) ($row['source_reference'] ?? '');
        $supplierView = SupplierContext::isSupplier();
        $row['type_label'] = PayableLedgerType::labelForRole($type, $supplierView);
        $row['description'] = PayableLedgerType::descriptionForRole($type, $supplierView, $source !== '' ? $source : null);

        return $row;
    }
}
