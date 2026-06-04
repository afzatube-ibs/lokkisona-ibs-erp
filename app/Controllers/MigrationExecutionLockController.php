<?php

namespace App\Controllers;

use App\ActivityLog;
use App\Permission;

class MigrationExecutionLockController extends Controller
{
    public function index()
    {
        $this->authorize('migration_execution_lock.view');
        ActivityLog::record('migration_execution_lock_access', 'Migration Execution Lock planning foundation page viewed');

        $this->render('migration-execution-lock.index', [
            'pageTitle' => 'Migration Execution Lock',
            'breadcrumbs' => [
                ['label' => 'System', 'active' => false],
                ['label' => 'Migration Execution Lock', 'active' => true],
            ],
            'accessMode' => Permission::accessMode(),
            'rules' => $this->rules(),
            'lockStates' => $this->lockStates(),
            'lockFields' => $this->lockFields(),
            'auditFields' => $this->auditFields(),
            'previewRows' => $this->previewRows(),
        ]);
    }

    private function rules()
    {
        return [
            [
                'title' => 'Execution Lock Purpose',
                'points' => [
                    'Keep future migration apply locked by default until every required gate is complete.',
                    'Protect the ERP from accidental, duplicated, or wrong-environment migration execution.',
                    'Document the lock model only; this release does not execute SQL or write lock records.',
                ],
            ],
            [
                'title' => 'Manual-Only Apply Protection',
                'points' => [
                    'Ready state must still mean manual owner/admin execution only.',
                    'No page, build queue, sync/import, staff area, or supplier area may trigger migration execution.',
                    'The lock is planned as the final visible stop before any future manual apply.',
                ],
            ],
            [
                'title' => 'Wrong Environment Protection',
                'points' => [
                    'Target environment must match the approved environment before the lock can become ready.',
                    'Production requires extra confirmation and backup reference planning.',
                    'Wrong environment should force a blocked lock state.',
                ],
            ],
            [
                'title' => 'Dirty Git Protection',
                'points' => [
                    'Future apply should require confirmed branch, commit, and clean/synced Git status.',
                    'Uncommitted or unsynced changes should keep the execution lock closed.',
                    'The lock should preserve Git branch and commit in planned audit fields.',
                ],
            ],
            [
                'title' => 'Failed Dry-Run Protection',
                'points' => [
                    'Dry-run must pass before the execution lock can become ready.',
                    'Warnings and Red Issues must be reviewed before approval and lock readiness.',
                    'Failed dry-run keeps the lock waiting or blocked.',
                ],
            ],
            [
                'title' => 'Missing Approval Protection',
                'points' => [
                    'Migration Approval Gate must be complete before execution lock readiness.',
                    'Approval does not automatically unlock or execute anything.',
                    'Missing approval keeps the lock closed.',
                ],
            ],
            [
                'title' => 'Backup and Checksum Protection',
                'points' => [
                    'Backup confirmation is required before readiness.',
                    'Checksum confirmation must match reviewed migration files.',
                    'Checksum mismatch or missing backup should block future apply.',
                ],
            ],
            [
                'title' => 'Double-Apply and Emergency Stop Planning',
                'points' => [
                    'Already-applied migration detection should block duplicate apply attempts later.',
                    'Emergency stop should force an emergency locked state.',
                    'Emergency lock changes must be auditable later.',
                ],
            ],
        ];
    }

    private function lockStates()
    {
        return [
            'locked_by_default',
            'waiting_for_dry_run',
            'waiting_for_backup',
            'waiting_for_owner_approval',
            'waiting_for_clean_git',
            'waiting_for_checksum_confirmation',
            'ready_but_manual_only',
            'blocked_red_issues',
            'blocked_wrong_environment',
            'blocked_missing_rollback',
            'emergency_locked',
        ];
    }

    private function lockFields()
    {
        return [
            'migration_execution_lock_id',
            'migration_approval_id',
            'lock_reference',
            'environment',
            'git_branch',
            'git_commit',
            'lock_state',
            'dry_run_required',
            'dry_run_passed',
            'backup_required',
            'backup_confirmed',
            'owner_approval_required',
            'owner_approved',
            'clean_git_required',
            'clean_git_confirmed',
            'checksum_required',
            'checksum_confirmed',
            'rollback_required',
            'rollback_confirmed',
            'red_issues_count',
            'emergency_stop_enabled',
            'locked_by',
            'locked_at',
            'unlocked_by',
            'unlocked_at',
            'lock_note',
            'created_at',
            'updated_at',
        ];
    }

    private function auditFields()
    {
        return [
            'migration_execution_lock_audit_id',
            'migration_execution_lock_id',
            'action',
            'previous_state',
            'new_state',
            'actor_user_id',
            'actor_role',
            'note',
            'created_at',
        ];
    }

    private function previewRows()
    {
        return [
            ['state' => 'locked_by_default', 'meaning' => 'Initial safe state for every future migration apply plan'],
            ['state' => 'waiting_for_dry_run', 'meaning' => 'Migration Dry Run has not passed yet'],
            ['state' => 'waiting_for_backup', 'meaning' => 'Backup confirmation is missing'],
            ['state' => 'ready_but_manual_only', 'meaning' => 'All gates are ready, but execution remains manual only'],
            ['state' => 'emergency_locked', 'meaning' => 'Owner/admin emergency stop blocks apply immediately'],
        ];
    }
}
