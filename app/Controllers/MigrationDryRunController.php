<?php

namespace App\Controllers;

use App\ActivityLog;
use App\Permission;

class MigrationDryRunController extends Controller
{
    public function index()
    {
        $this->authorize('migration_dry_run.view');
        ActivityLog::record('migration_dry_run_access', 'Migration Dry Run Validator planning foundation page viewed');

        $this->render('migration-dry-run.index', [
            'pageTitle' => 'Migration Dry Run',
            'breadcrumbs' => [
                ['label' => 'System', 'active' => false],
                ['label' => 'Migration Dry Run', 'active' => true],
            ],
            'accessMode' => Permission::accessMode(),
            'rules' => $this->rules(),
            'plannedChecks' => $this->plannedChecks(),
            'resultFields' => $this->resultFields(),
            'issueFields' => $this->issueFields(),
            'previewRows' => $this->previewRows(),
        ]);
    }

    private function rules()
    {
        return [
            [
                'title' => 'Dry-Run Validator Purpose',
                'points' => [
                    'Validate migration draft files before any future real apply.',
                    'Show what would be checked, warned, or blocked without touching the database.',
                    'Give owner/admin users a clear Red Issues Summary before any production risk.',
                ],
            ],
            [
                'title' => 'No Database Write Rule',
                'points' => [
                    'Dry-run must never execute SQL or modify schema/data.',
                    'It must not change schema or data.',
                    'It must remain separate from page-load, staff, supplier, sync, and build queue flows.',
                ],
            ],
            [
                'title' => 'Migration File Scan Rule',
                'points' => [
                    'Scan database/migrations draft files for existence, naming, order, and required warning headers.',
                    'Detect duplicate migration keys before any future apply is considered.',
                    'Show missing files or unexpected names as warnings or red issues.',
                ],
            ],
            [
                'title' => 'SQL Safety Scan Rule',
                'points' => [
                    'Check detected operations and affected tables without executing the SQL.',
                    'Block destructive SQL unless explicit owner-reviewed warning and rollback planning exist later.',
                    'Confirm runtime app code still has no migration execution path.',
                ],
            ],
            [
                'title' => 'Apply-Order Validation Rule',
                'points' => [
                    'Validate that migration files run in sequence and module dependency order.',
                    'Core users/roles should precede business, product, sync, order, payable, and invoice groups.',
                    'A missing or skipped sequence should stop future apply planning.',
                ],
            ],
            [
                'title' => 'Checksum Planning',
                'points' => [
                    'Future dry-run should calculate a checksum for each migration file.',
                    'Checksum changes after owner review should require re-review.',
                    'Checksums are planned only in this release and are not stored.',
                ],
            ],
            [
                'title' => 'Environment Safety Check',
                'points' => [
                    'Future dry-run should show local/staging/production environment before apply planning.',
                    'Production must require extra confirmation later.',
                    'Backup reminder must appear before any future apply action.',
                ],
            ],
            [
                'title' => 'Owner Approval Rule',
                'points' => [
                    'Successful dry-run is required before future real apply.',
                    'Owner/admin approval is still required after dry-run passes.',
                    'A passed dry-run must not automatically apply migrations.',
                ],
            ],
            [
                'title' => 'Red Issues Summary Behavior',
                'points' => [
                    'Any red issue stops future migration apply planning immediately.',
                    'Summary should include severity, file path, line number, issue detail, and suggested fix.',
                    'No next migration, build, commit, push, sync, or import should continue after a red issue.',
                ],
            ],
        ];
    }

    private function plannedChecks()
    {
        return [
            'migration file exists',
            'migration file naming is valid',
            'migration order is valid',
            'draft warning header exists',
            'no runtime SQL execution',
            'no schema DDL outside migration files',
            'no destructive SQL without explicit warning',
            'no duplicate migration key',
            'checksum can be generated later',
            'estimated tables/columns affected',
            'rollback plan reference exists later',
        ];
    }

    private function resultFields()
    {
        return [
            'dry_run_id',
            'migration_file',
            'migration_key',
            'migration_group',
            'check_status',
            'safety_status',
            'detected_operations',
            'affected_tables',
            'warning_count',
            'red_issue_count',
            'checksum',
            'checked_by',
            'checked_at',
        ];
    }

    private function issueFields()
    {
        return [
            'dry_run_issue_id',
            'dry_run_id',
            'severity',
            'file_path',
            'line_number',
            'issue_title',
            'issue_detail',
            'suggested_fix',
            'status',
            'created_at',
        ];
    }

    private function previewRows()
    {
        return [
            ['file' => '0002_core_users_roles_activity.sql', 'group' => 'Core users and roles', 'status' => 'Planned check only'],
            ['file' => '0003_business_sources_suppliers_products.sql', 'group' => 'Business sources and products', 'status' => 'Planned check only'],
            ['file' => '0004_status_mapping_sync_preview.sql', 'group' => 'Status mapping and sync preview', 'status' => 'Planned check only'],
            ['file' => '0005_orders_manual_orders_workflow.sql', 'group' => 'Orders and workflow', 'status' => 'Planned check only'],
            ['file' => '0006_dispatch_returns_payables.sql', 'group' => 'Dispatch, returns, payables', 'status' => 'Planned check only'],
            ['file' => '0007_invoices_printing_supplier_tools.sql', 'group' => 'Invoices, printing, supplier tools', 'status' => 'Planned check only'],
        ];
    }
}
