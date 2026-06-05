<?php

namespace App\Repositories\Write;

use App\Models\BusinessSource;

class BusinessSourceWriteRepository extends BaseWriteRepository
{
    public function modelClass(): string
    {
        return BusinessSource::class;
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO `' . $this->escapeIdentifier($this->table()) . '` '
            . '(business_id, source_name, source_type, website_domain, order_source_label, default_supplier_id, default_workflow, status, created_at) '
            . 'VALUES (:business_id, :source_name, :source_type, :website_domain, :order_source_label, :default_supplier_id, :default_workflow, :status, NOW())';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($data);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $sql = 'UPDATE `' . $this->escapeIdentifier($this->table()) . '` SET '
            . 'business_id = :business_id, source_name = :source_name, source_type = :source_type, '
            . 'website_domain = :website_domain, order_source_label = :order_source_label, '
            . 'default_supplier_id = :default_supplier_id, default_workflow = :default_workflow, status = :status, updated_at = NOW() '
            . 'WHERE business_source_id = :id';
        $data['id'] = $id;
        $statement = $this->pdo->prepare($sql);

        return $statement->execute($data);
    }

    public function find(int $id): ?array
    {
        return $this->findById($id);
    }
}
