<?php

namespace App\Controllers;

use App\ActivityLog;
use App\Permission;

class MigrationApprovalController extends Controller
{
    public function index()
    {
        $this->authorize('migration_approval.view');
        ActivityLog::record('migration_approval_access', 'Migration Apply Approval Gate planning foundation page viewed');

        $this->render('migration-approval.index', [
            'pageTitle' => 'Migration Approval',
            'breadcrumbs' => [
                ['label' => 'System', 'active' => false],
                ['label' => 'Migration Approval', 'active' => true],
            ],
            'accessMode' => Permission::accessMode(),
            'rules' => $this->rules(),
            'checklistItems' => $this->checklistItems(),
            'approvalFields' => $this->approvalFields(),
            'checkFields' => $this->checkFields(),
            'auditFields' => $this->auditFields(),
        ]);
    }

    private function rules()
    {
        return [
            [
                'title' => 'Apply Approval Gate Purpose',
                'points' => [
                    'Provide the future final gate before any real migration apply.',
                    'Require owner/admin confirmation, backup proof, environment confirmation, and clear dry-run result.',
                    'Document the approval process without executing SQL or changing the database.',
                ],
            ],
            [
                'title' => 'Dry-Run Must Pass First',
                'points' => [
                    'Approval cannot start until Migration Dry Run reports passed status.',
                    'Dry-run warnings must be reviewed before approval.',
                    'Dry-run red issues must be zero before approval can continue.',
                ],
            ],
            [
                'title' => 'Backup Confirmation Rule',
                'points' => [
                    'A database backup must be completed before approval.',
                    'Backup reference should be captured later for audit.',
                    'No future production apply may continue without backup confirmation.',
                ],
            ],
            [
                'title' => 'Environment Confirmation Rule',
                'points' => [
                    'Owner/admin must confirm the target environment before apply.',
                    'Production must show an extra safety warning.',
                    'Wrong environment selection should block approval.',
                ],
            ],
            [
                'title' => 'Owner/Admin Approval Rule',
                'points' => [
                    'Owner approval and admin/operator confirmation are separate planned checks.',
                    'Approval actor, role, time, IP address, and note should be auditable later.',
                    'Approval never means automatic execution in this planning release.',
                ],
            ],
            [
                'title' => 'Checksum and Apply Order Confirmation',
                'points' => [
                    'Migration file checksums should match the reviewed dry-run result.',
                    'Apply order must be reviewed before future apply.',
                    'Checksum or order mismatch should create a Red Issues Summary.',
                ],
            ],
            [
                'title' => 'Rollback Plan Confirmation',
                'points' => [
                    'Rollback plan must be reviewed before approval.',
                    'Rollback plan reference should be captured later.',
                    'Missing rollback planning should block production apply.',
                ],
            ],
            [
                'title' => 'Final Apply Lock Planning',
                'points' => [
                    'Future apply should lock the approved file set and checklist snapshot.',
                    'No new build queue task, sync, import, commit, or push should bypass approval.',
                    'This release does not implement apply locks or write approval records.',
                ],
            ],
        ];
    }

    private function checklistItems()
    {
        return [
            'Current Git branch confirmed',
            'Git status clean/synced',
            'Database backup completed',
            'Correct environment confirmed',
            'Dry-run result passed',
            'Red Issues count is zero',
            'Migration files checksum confirmed',
            'Apply order reviewed',
            'Rollback plan reviewed',
            'Owner approval captured',
            'Admin/operator confirmation captured',
        ];
    }

    private function approvalFields()
    {
        return [
            'migration_approval_id',
            'migration_run_id',
            'approval_reference',
            'environment',
            'git_branch',
            'git_commit',
            'dry_run_status',
            'red_issues_count',
            'backup_confirmed',
            'backup_reference',
            'checksum_confirmed',
            'apply_order_confirmed',
            'rollback_plan_confirmed',
            'owner_approved_by',
            'owner_approved_at',
            'operator_confirmed_by',
            'operator_confirmed_at',
            'approval_status',
            'approval_note',
            'created_at',
            'updated_at',
        ];
    }

    private function checkFields()
    {
        return [
            'migration_approval_check_id',
            'migration_approval_id',
            'check_key',
            'check_label',
            'check_status',
            'checked_by',
            'checked_at',
            'note',
        ];
    }

    private function auditFields()
    {
        return [
            'migration_approval_audit_id',
            'migration_approval_id',
            'action',
            'actor_user_id',
            'actor_role',
            'ip_address',
            'user_agent',
            'note',
            'created_at',
        ];
    }
}
