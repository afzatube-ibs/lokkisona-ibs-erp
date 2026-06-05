<?php

namespace App\Services\Write;

use App\ActivityLog;
use App\Repositories\Write\LaunchCutoverWriteRepository;

class LaunchCutoverWriteService
{
    private LaunchCutoverWriteRepository $repository;

    public function __construct(?LaunchCutoverWriteRepository $repository = null)
    {
        $this->repository = $repository ?? new LaunchCutoverWriteRepository();
    }

    public function lock(array $input): WriteResult
    {
        if (!$this->repository->tableExists()) {
            return WriteResult::fail('Launch cutover table not available. Apply migration 0008 first.');
        }

        if ($this->repository->latestLocked() !== null) {
            return WriteResult::fail('Launch is already locked. Owner unlock required before re-lock.');
        }

        $goLive = trim((string) ($input['go_live_date'] ?? ''));
        $cutoff = trim((string) ($input['cutoff_date'] ?? ''));
        if ($goLive === '' || $cutoff === '') {
            return WriteResult::fail('Go-live date and cut-off date are required.');
        }

        $id = $this->repository->createLock([
            'go_live_date' => $goLive,
            'cutoff_date' => $cutoff,
            'supplier_id' => ($input['supplier_id'] ?? '') !== '' ? (int) $input['supplier_id'] : null,
            'status' => 'locked',
            'notes' => trim((string) ($input['notes'] ?? '')) ?: null,
        ]);

        ActivityLog::record('launch_cutover_locked', 'Launch cutover locked', ['cutover_id' => $id]);

        return WriteResult::ok('Launch cutover locked. Pre-launch edits should be restricted.', $id);
    }

    public function isLocked(): bool
    {
        return $this->repository->latestLocked() !== null;
    }
}
