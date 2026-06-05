<?php

namespace App\Services\Write;

use App\ActivityLog;
use App\Repositories\Write\SupplierWriteRepository;

class SupplierWriteService
{
    private SupplierWriteRepository $repository;

    public function __construct(?SupplierWriteRepository $repository = null)
    {
        $this->repository = $repository ?? new SupplierWriteRepository();
    }

    public function tableReady(): bool
    {
        return $this->repository->tableExists();
    }

    public function create(array $input): WriteResult
    {
        if (!$this->tableReady()) {
            return WriteResult::fail('Supplier table not available. Apply migration 0003 manually first.');
        }

        $name = trim((string) ($input['supplier_name'] ?? ''));
        if ($name === '') {
            return WriteResult::fail('Supplier name is required.', ['supplier_name' => 'Required']);
        }

        $status = trim((string) ($input['status'] ?? 'active'));
        if (!in_array($status, ['active', 'inactive'], true)) {
            $status = 'active';
        }

        $sourceId = $input['linked_business_source_id'] ?? null;
        $sourceId = ($sourceId === '' || $sourceId === null) ? null : (int) $sourceId;

        $id = $this->repository->create([
            'supplier_name' => $name,
            'contact_person' => trim((string) ($input['contact_person'] ?? '')) ?: null,
            'phone' => trim((string) ($input['phone'] ?? '')) ?: null,
            'email' => trim((string) ($input['email'] ?? '')) ?: null,
            'address' => trim((string) ($input['address'] ?? '')) ?: null,
            'payment_terms' => trim((string) ($input['payment_terms'] ?? '')) ?: null,
            'status' => $status,
            'linked_business_source_id' => $sourceId,
        ]);

        ActivityLog::record('supplier_created', 'Supplier created via write service', ['supplier_id' => $id]);

        return WriteResult::ok('Supplier created successfully.', $id);
    }

    public function applyEdit(int $id, array $input): WriteResult
    {
        if (!$this->tableReady()) {
            return WriteResult::fail('Supplier table not available.');
        }

        if ($this->repository->find($id) === null) {
            return WriteResult::fail('Supplier not found.');
        }

        $name = trim((string) ($input['supplier_name'] ?? ''));
        if ($name === '') {
            return WriteResult::fail('Supplier name is required.');
        }

        $status = trim((string) ($input['status'] ?? 'active'));
        if (!in_array($status, ['active', 'inactive'], true)) {
            $status = 'active';
        }

        $sourceId = $input['linked_business_source_id'] ?? null;
        $sourceId = ($sourceId === '' || $sourceId === null) ? null : (int) $sourceId;

        $this->repository->update($id, [
            'supplier_name' => $name,
            'contact_person' => trim((string) ($input['contact_person'] ?? '')) ?: null,
            'phone' => trim((string) ($input['phone'] ?? '')) ?: null,
            'email' => trim((string) ($input['email'] ?? '')) ?: null,
            'address' => trim((string) ($input['address'] ?? '')) ?: null,
            'payment_terms' => trim((string) ($input['payment_terms'] ?? '')) ?: null,
            'status' => $status,
            'linked_business_source_id' => $sourceId,
        ]);

        ActivityLog::record('supplier_updated', 'Supplier updated via write service', ['supplier_id' => $id]);

        return WriteResult::ok('Supplier updated successfully.', $id);
    }
}
