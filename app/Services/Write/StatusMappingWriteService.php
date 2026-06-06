<?php

namespace App\Services\Write;

use App\ActivityLog;
use App\ReadFoundation\WriteGate;
use App\Repositories\Write\StatusMappingWriteRepository;

class StatusMappingWriteService
{
    private StatusMappingWriteRepository $mappings;

    public function __construct(?StatusMappingWriteRepository $mappings = null)
    {
        $this->mappings = $mappings ?? new StatusMappingWriteRepository();
    }

    public function create(array $input): WriteResult
    {
        if (!WriteGate::statusMappingSync()['ready']) {
            return WriteResult::fail(WriteGate::WARNING_MESSAGE);
        }

        $sourceId = (int) ($input['business_source_id'] ?? 0);
        if ($sourceId <= 0) {
            return WriteResult::fail('Business source is required.');
        }

        $sourceStatus = trim((string) ($input['source_status'] ?? ''));
        $ibsStatus = trim((string) ($input['ibs_status'] ?? ''));
        if ($sourceStatus === '' || $ibsStatus === '') {
            return WriteResult::fail('Source status and IBS status are required.');
        }

        $id = $this->mappings->create([
            'business_source_id' => $sourceId,
            'source_status' => $sourceStatus,
            'ibs_status' => $ibsStatus,
            'workflow_group' => trim((string) ($input['workflow_group'] ?? '')) ?: null,
            'return_type' => trim((string) ($input['return_type'] ?? '')) ?: null,
            'courier_status' => trim((string) ($input['courier_status'] ?? '')) ?: null,
            'is_active' => 1,
            'created_by' => null,
        ]);

        ActivityLog::record('status_mapping_created', 'Status mapping row created', [
            'status_mapping_id' => $id,
            'source_status' => $sourceStatus,
            'ibs_status' => $ibsStatus,
        ]);

        return WriteResult::ok('Status mapping saved.', $id);
    }

    public function toggleActive(int $id, array $input): WriteResult
    {
        if (!WriteGate::statusMappingSync()['ready']) {
            return WriteResult::fail(WriteGate::WARNING_MESSAGE);
        }

        if ($id <= 0) {
            return WriteResult::fail('Mapping ID is required.');
        }

        $active = in_array((string) ($input['is_active'] ?? ''), ['1', 'on', 'yes'], true);
        if (!$this->mappings->setActive($id, $active)) {
            return WriteResult::fail('Could not update mapping status.');
        }

        ActivityLog::record('status_mapping_toggled', 'Status mapping active flag updated', [
            'status_mapping_id' => $id,
            'is_active' => $active,
        ]);

        return WriteResult::ok($active ? 'Mapping activated.' : 'Mapping deactivated.');
    }

    public function seedDefaults(int $businessSourceId): WriteResult
    {
        if (!WriteGate::statusMappingSync()['ready']) {
            return WriteResult::fail(WriteGate::WARNING_MESSAGE);
        }

        if ($businessSourceId <= 0) {
            return WriteResult::fail('Business source is required.');
        }

        $defaults = [
            ['source_status' => 'Supplier Processing', 'ibs_status' => 'new_order'],
            ['source_status' => 'Processing', 'ibs_status' => 'new_order'],
            ['source_status' => 'Returning', 'ibs_status' => 'order_returning'],
            ['source_status' => 'Return', 'ibs_status' => 'order_returning'],
        ];

        $created = 0;
        foreach ($defaults as $row) {
            if ($this->mappings->findBySourceStatus($businessSourceId, $row['source_status']) !== null) {
                continue;
            }
            $this->mappings->create([
                'business_source_id' => $businessSourceId,
                'source_status' => $row['source_status'],
                'ibs_status' => $row['ibs_status'],
                'workflow_group' => 'workflow',
                'return_type' => null,
                'courier_status' => null,
                'is_active' => 1,
                'created_by' => null,
            ]);
            $created++;
        }

        ActivityLog::record('status_mapping_seeded', 'Default Lokkisona status mappings seeded', [
            'business_source_id' => $businessSourceId,
            'created' => $created,
        ]);

        return WriteResult::ok('Seeded ' . $created . ' default mapping row(s). Status 0 / Missing remain skipped by sync rules.');
    }
}
