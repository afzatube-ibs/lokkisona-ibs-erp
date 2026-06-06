<?php

namespace App\Repositories\Write;

use App\Models\Settlement;

class SettlementWriteRepository extends BaseWriteRepository
{
    public function modelClass(): string
    {
        return Settlement::class;
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO `' . $this->escapeIdentifier($this->table()) . '` '
            . '(supplier_id, settlement_reference, period_type, period_start, period_end, opening_balance, dispatch_payable, invoice_total, '
            . 'deductions, payments, advances, adjustments, closing_balance, workflow_status, prepared_by, prepared_at, notes, created_at) '
            . 'VALUES (:supplier_id, :settlement_reference, :period_type, :period_start, :period_end, :opening_balance, :dispatch_payable, :invoice_total, '
            . ':deductions, :payments, :advances, :adjustments, :closing_balance, :workflow_status, :prepared_by, NOW(), :notes, NOW())';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($data);

        return (int) $this->pdo->lastInsertId();
    }

    public function updateWorkflow(int $id, string $status, array $extra = []): bool
    {
        if (!$this->tableExists()) {
            return false;
        }

        $sets = ['workflow_status = :workflow_status', 'updated_at = NOW()'];
        $params = ['workflow_status' => $status, 'id' => $id];

        foreach (['approved_by', 'approved_at', 'paid_at', 'closed_at', 'prepared_by', 'prepared_at'] as $field) {
            if (array_key_exists($field, $extra)) {
                $sets[] = $field . ' = :' . $field;
                $params[$field] = $extra[$field];
            }
        }

        $sql = 'UPDATE `' . $this->escapeIdentifier($this->table()) . '` SET ' . implode(', ', $sets) . ' WHERE settlement_id = :id LIMIT 1';
        $statement = $this->pdo->prepare($sql);

        return $statement->execute($params);
    }

    public function listRecent(int $limit = 50, int $supplierId = 0): array
    {
        if (!$this->tableExists()) {
            return [];
        }

        $limit = max(1, min($limit, 100));
        $sql = 'SELECT * FROM `' . $this->escapeIdentifier($this->table()) . '`';
        $params = [];
        if ($supplierId > 0) {
            $sql .= ' WHERE supplier_id = :supplier_id';
            $params['supplier_id'] = $supplierId;
        }
        $sql .= ' ORDER BY settlement_id DESC LIMIT ' . $limit;
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    public function find(int $id): ?array
    {
        return $this->findById($id);
    }
}
