<?php

namespace App\Repositories\Write;

use App\Models\PayableLedger;
use PDO;

class PayableLedgerWriteRepository extends BaseWriteRepository
{
    public function modelClass(): string
    {
        return PayableLedger::class;
    }

    public function find(int $id): ?array
    {
        return $this->findById($id);
    }

    public function createOpeningEntry(int $supplierId, string $reference, float $debit, float $credit): int
    {
        return $this->createEntry([
            'supplier_id' => $supplierId,
            'ledger_reference' => $reference,
            'ledger_type' => 'opening_balance',
            'source_reference' => $reference,
            'debit_amount' => $debit,
            'credit_amount' => $credit,
            'balance_after' => round($debit - $credit, 2),
            'status' => 'posted',
            'created_by' => null,
        ]);
    }

    public function createEntry(array $data): int
    {
        $sql = 'INSERT INTO `' . $this->escapeIdentifier($this->table()) . '` '
            . '(supplier_id, ledger_reference, ledger_type, source_reference, debit_amount, credit_amount, balance_after, status, created_by, created_at) '
            . 'VALUES (:supplier_id, :ledger_reference, :ledger_type, :source_reference, :debit_amount, :credit_amount, :balance_after, :status, :created_by, NOW())';
        $statement = $this->pdo->prepare($sql);
        $statement->execute([
            'supplier_id' => (int) $data['supplier_id'],
            'ledger_reference' => (string) $data['ledger_reference'],
            'ledger_type' => (string) $data['ledger_type'],
            'source_reference' => $data['source_reference'] ?? null,
            'debit_amount' => round((float) ($data['debit_amount'] ?? 0), 2),
            'credit_amount' => round((float) ($data['credit_amount'] ?? 0), 2),
            'balance_after' => array_key_exists('balance_after', $data) && $data['balance_after'] !== null
                ? round((float) $data['balance_after'], 2)
                : null,
            'status' => (string) ($data['status'] ?? 'draft'),
            'created_by' => $data['created_by'] ?? null,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function updateStatus(int $id, string $status, ?float $balanceAfter = null): bool
    {
        if ($balanceAfter !== null) {
            $sql = 'UPDATE `' . $this->escapeIdentifier($this->table()) . '` '
                . 'SET status = :status, balance_after = :balance_after WHERE payable_ledger_id = :id';
            $statement = $this->pdo->prepare($sql);

            return $statement->execute([
                'status' => $status,
                'balance_after' => round($balanceAfter, 2),
                'id' => $id,
            ]);
        }

        $sql = 'UPDATE `' . $this->escapeIdentifier($this->table()) . '` '
            . 'SET status = :status WHERE payable_ledger_id = :id';
        $statement = $this->pdo->prepare($sql);

        return $statement->execute(['status' => $status, 'id' => $id]);
    }

    public function findByLedgerReference(string $reference): ?array
    {
        if (!$this->tableExists() || $reference === '') {
            return null;
        }

        $sql = 'SELECT * FROM `' . $this->escapeIdentifier($this->table()) . '` '
            . 'WHERE ledger_reference = :reference LIMIT 1';
        $statement = $this->pdo->prepare($sql);
        $statement->execute(['reference' => $reference]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }

    public function findBySourceAndType(string $sourceReference, string $ledgerType): ?array
    {
        if (!$this->tableExists() || $sourceReference === '') {
            return null;
        }

        $sql = 'SELECT * FROM `' . $this->escapeIdentifier($this->table()) . '` '
            . 'WHERE source_reference = :source_reference AND ledger_type = :ledger_type LIMIT 1';
        $statement = $this->pdo->prepare($sql);
        $statement->execute([
            'source_reference' => $sourceReference,
            'ledger_type' => $ledgerType,
        ]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }

    public function getPostedBalanceForSupplier(int $supplierId): float
    {
        if (!$this->tableExists() || $supplierId <= 0) {
            return 0.0;
        }

        $sql = 'SELECT balance_after FROM `' . $this->escapeIdentifier($this->table()) . '` '
            . 'WHERE supplier_id = :supplier_id AND status = :status '
            . 'ORDER BY payable_ledger_id DESC LIMIT 1';
        $statement = $this->pdo->prepare($sql);
        $statement->execute(['supplier_id' => $supplierId, 'status' => 'posted']);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row === false ? 0.0 : round((float) ($row['balance_after'] ?? 0), 2);
    }

    public function listForSupplier(int $supplierId, int $limit = 200): array
    {
        if (!$this->tableExists()) {
            return [];
        }

        $sql = 'SELECT * FROM `' . $this->escapeIdentifier($this->table()) . '` '
            . 'WHERE supplier_id = :supplier_id ORDER BY payable_ledger_id DESC LIMIT ' . max(1, min(500, $limit));
        $statement = $this->pdo->prepare($sql);
        $statement->execute(['supplier_id' => $supplierId]);

        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function listAll(int $limit = 200): array
    {
        if (!$this->tableExists()) {
            return [];
        }

        $sql = 'SELECT * FROM `' . $this->escapeIdentifier($this->table()) . '` '
            . 'ORDER BY payable_ledger_id DESC LIMIT ' . max(1, min(500, $limit));
        $statement = $this->pdo->query($sql);

        return $statement ? ($statement->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
    }

    public function countByStatus(string $status): int
    {
        if (!$this->tableExists()) {
            return 0;
        }

        $sql = 'SELECT COUNT(*) AS row_count FROM `' . $this->escapeIdentifier($this->table()) . '` WHERE status = :status';
        $statement = $this->pdo->prepare($sql);
        $statement->execute(['status' => $status]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return (int) ($row['row_count'] ?? 0);
    }

    public function countBySupplierAndStatus(int $supplierId, string $status): int
    {
        if (!$this->tableExists() || $supplierId <= 0) {
            return 0;
        }

        $sql = 'SELECT COUNT(*) AS row_count FROM `' . $this->escapeIdentifier($this->table()) . '` '
            . 'WHERE supplier_id = :supplier_id AND status = :status';
        $statement = $this->pdo->prepare($sql);
        $statement->execute(['supplier_id' => $supplierId, 'status' => $status]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return (int) ($row['row_count'] ?? 0);
    }
}
