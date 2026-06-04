<?php

namespace App\Controllers;

use App\ActivityLog;
use App\Permission;

class MigrationRunnerController extends Controller
{
    public function index()
    {
        $this->authorize('migration_runner.view');
        ActivityLog::record('migration_runner_access', 'Migration Runner planning foundation page viewed');

        $this->render('migration-runner.index', [
            'pageTitle' => 'Migration Runner',
            'breadcrumbs' => [
                ['label' => 'System', 'active' => false],
                ['label' => 'Migration Runner', 'active' => true],
            ],
            'accessMode' => Permission::accessMode(),
            'rules' => $this->rules(),
            'plannedGroups' => $this->plannedGroups(),
            'migrationFields' => $this->migrationFields(),
            'runLogFields' => $this->runLogFields(),
            'rollbackFields' => $this->rollbackFields(),
        ]);
    }

    private function rules()
    {
        return [
            [
                'title' => 'Migration Runner Purpose',
                'points' => [
                    'Provide an owner/admin-controlled place to review future database migration plans.',
                    'Keep database structure changes controlled, manual, auditable, and separate from normal ERP pages.',
                    'Document the future runner before any SQL execution capability is added.',
                    'v0.1.22 adds draft migration files, but the runner still does not execute them.',
                ],
            ],
            [
                'title' => 'Manual-Only Migration Rule',
                'points' => [
                    'No migration will run from page load, staff pages, supplier pages, background loops, or hidden installers.',
                    'Future apply actions must require an explicit owner/admin decision.',
                    'This release does not execute migration SQL.',
                    'Draft SQL files under database/migrations are review material until manually applied later.',
                ],
            ],
            [
                'title' => 'Dry-Run / Check-First Rule',
                'points' => [
                    'The future runner must support check mode before apply mode.',
                    'Check mode must show pending migrations, file paths, checksum status, expected groups, and possible blockers.',
                    'Apply mode must not be available until the owner/admin has reviewed the check output.',
                ],
            ],
            [
                'title' => 'Backup-Before-Apply Rule',
                'points' => [
                    'The future apply flow must show a clear database backup reminder.',
                    'Production apply must require extra confirmation that a backup exists.',
                    'The current page is planning only and does not touch the database.',
                ],
            ],
            [
                'title' => 'Owner/Admin Confirmation Rule',
                'points' => [
                    'Owner/admin roles may review this planning page.',
                    'Staff and supplier roles must not manage migrations.',
                    'Future production apply must require stronger confirmation than normal navigation.',
                ],
            ],
            [
                'title' => 'Migration Audit / Log Rule',
                'points' => [
                    'Future migration runs must record who started them, when they started, when they finished, and the result.',
                    'Migration files must be identified by key, group, file path, checksum, status, and timing.',
                    'This release records only the page-view activity log entry.',
                ],
            ],
            [
                'title' => 'Rollback Planning Rule',
                'points' => [
                    'Every risky migration should have a documented rollback plan before production apply.',
                    'Rollback execution must be separately approved and logged later.',
                    'Rollback records are planned fields only in this release.',
                ],
            ],
            [
                'title' => 'Production Safety Rule',
                'points' => [
                    'Production migration must be owner/admin controlled and must show environment, pending count, backup reminder, and Red Issues Summary behavior before apply.',
                    'Failed migration must stop clearly and show what failed, where it failed, and what to fix.',
                    'No CREATE TABLE / ALTER TABLE / DROP TABLE runs from this planning foundation.',
                ],
            ],
            [
                'title' => 'Future CLI / Web Runner Plan',
                'points' => [
                    'A future CLI command can provide dry-run and controlled apply for deployment use.',
                    'A future web page can show the same plan to owner/admin users with stronger confirmation.',
                    'Both paths must share one audited safety model and must not run from normal ERP pages.',
                    'Build Queue or semi-automation must never trigger migration apply automatically.',
                ],
            ],
            [
                'title' => 'Red Issues Summary Behavior',
                'points' => [
                    'Check/apply failures must summarize issue, area, file/page or migration file, and what to fix.',
                    'The local checkpoint keeps the plain text ALL GREEN or RED ISSUES SUMMARY footer.',
                    'Migration failures later should use the same plain-language failure style.',
                ],
            ],
        ];
    }

    private function plannedGroups()
    {
        return [
            'Core users and roles',
            'Activity logs',
            'Business sources',
            'Suppliers',
            'Products and variants',
            'Product cost and stock histories',
            'Status mappings',
            'Sync previews and imports',
            'Orders and order items',
            'Manual/external orders',
            'Dispatch reports',
            'Supplier payables and settlements',
            'Return receive and return batches',
            'Invoice and print logs',
            'Supplier tools audit',
        ];
    }

    private function migrationFields()
    {
        return [
            'migration_id',
            'migration_key',
            'migration_name',
            'migration_group',
            'file_path',
            'checksum',
            'status',
            'applied_by',
            'applied_at',
            'execution_time_ms',
            'error_message',
            'created_at',
        ];
    }

    private function runLogFields()
    {
        return [
            'migration_run_id',
            'run_type',
            'environment',
            'total_pending',
            'total_applied',
            'total_failed',
            'started_by',
            'started_at',
            'finished_at',
            'result_status',
            'red_issues_summary',
        ];
    }

    private function rollbackFields()
    {
        return [
            'rollback_id',
            'migration_id',
            'rollback_plan',
            'rollback_file_path',
            'approved_by',
            'executed_by',
            'executed_at',
            'status',
        ];
    }
}
