<?php

namespace App\Services\Write;

use App\ActivityLog;
use App\Repositories\Write\BusinessSourceWriteRepository;

class BusinessSourceWriteService
{
    private BusinessSourceWriteRepository $repository;

    public function __construct(?BusinessSourceWriteRepository $repository = null)
    {
        $this->repository = $repository ?? new BusinessSourceWriteRepository();
    }

    public function tableReady(): bool
    {
        return $this->repository->tableExists();
    }

    public function create(array $input): WriteResult
    {
        if (!$this->tableReady()) {
            return WriteResult::fail('Business source table not available. Apply migration 0003 manually first.');
        }

        $name = trim((string) ($input['source_name'] ?? ''));
        $type = trim((string) ($input['source_type'] ?? ''));
        if ($name === '' || $type === '') {
            return WriteResult::fail('Source name and source type are required.');
        }

        $id = $this->repository->create($this->normalizeInput($input));

        ActivityLog::record('business_source_created', 'Business source created', ['business_source_id' => $id]);

        return WriteResult::ok('Business source created successfully.', $id);
    }

    public function applyEdit(int $id, array $input): WriteResult
    {
        if (!$this->tableReady()) {
            return WriteResult::fail('Business source table not available.');
        }

        if ($this->repository->find($id) === null) {
            return WriteResult::fail('Business source not found.');
        }

        $name = trim((string) ($input['source_name'] ?? ''));
        $type = trim((string) ($input['source_type'] ?? ''));
        if ($name === '' || $type === '') {
            return WriteResult::fail('Source name and source type are required.');
        }

        $this->repository->update($id, $this->normalizeInput($input));

        ActivityLog::record('business_source_updated', 'Business source updated', ['business_source_id' => $id]);

        return WriteResult::ok('Business source updated successfully.', $id);
    }

    private function normalizeInput(array $input): array
    {
        $status = trim((string) ($input['status'] ?? 'active'));
        if (!in_array($status, ['active', 'inactive'], true)) {
            $status = 'active';
        }

        $nullableInt = function ($key) use ($input) {
            $val = $input[$key] ?? null;

            return ($val === '' || $val === null) ? null : (int) $val;
        };

        return [
            'business_id' => $nullableInt('business_id'),
            'source_name' => trim((string) ($input['source_name'] ?? '')),
            'source_type' => trim((string) ($input['source_type'] ?? '')),
            'website_domain' => trim((string) ($input['website_domain'] ?? '')) ?: null,
            'order_source_label' => trim((string) ($input['order_source_label'] ?? '')) ?: null,
            'default_supplier_id' => $nullableInt('default_supplier_id'),
            'default_workflow' => trim((string) ($input['default_workflow'] ?? '')) ?: null,
            'status' => $status,
        ];
    }
}
