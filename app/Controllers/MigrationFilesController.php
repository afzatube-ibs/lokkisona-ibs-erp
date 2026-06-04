<?php

namespace App\Controllers;

use App\ActivityLog;
use App\Permission;

class MigrationFilesController extends Controller
{
    public function index()
    {
        $this->authorize('migration_files.view');
        ActivityLog::record('migration_files_access', 'Migration Files planning foundation page viewed');

        $this->render('migration-files.index', [
            'pageTitle' => 'Migration Files',
            'breadcrumbs' => [
                ['label' => 'System', 'active' => false],
                ['label' => 'Migration Files', 'active' => true],
            ],
            'accessMode' => Permission::accessMode(),
            'draftFiles' => $this->draftFiles(),
            'migrationGroups' => $this->migrationGroups(),
            'safetyRules' => $this->safetyRules(),
            'applyOrder' => $this->applyOrder(),
        ]);
    }

    private function draftFiles()
    {
        return [
            '0002_core_users_roles_activity.sql',
            '0003_business_sources_suppliers_products.sql',
            '0004_status_mapping_sync_preview.sql',
            '0005_orders_manual_orders_workflow.sql',
            '0006_dispatch_returns_payables.sql',
            '0007_invoices_printing_supplier_tools.sql',
        ];
    }

    private function migrationGroups()
    {
        return [
            'Core users, roles, user role links, and activity logs',
            'Business sources, suppliers, products, variants, costs, and stock/cost histories',
            'Status mappings, courier mappings, sync previews, sync imports, and sync logs',
            'Orders, order items, manual orders, manual order items, and workflow histories',
            'Dispatch reports, returns, return batches, payables, supplier invoices, supplier payments, and adjustments',
            'Invoices, invoice items, templates, packing prints, print logs, and supplier quick invoice audit tables',
        ];
    }

    private function safetyRules()
    {
        return [
            [
                'title' => 'Migration File Purpose',
                'points' => [
                    'Provide reviewable SQL drafts for the future real database foundation.',
                    'Keep planned schema work visible before any owner-approved manual apply.',
                    'Document table groups, safe indexes, and logical relationships for ERP service-layer enforcement first.',
                ],
            ],
            [
                'title' => 'Draft-Only Warning',
                'points' => [
                    'The files under database/migrations are drafts only.',
                    'The application does not load, parse, or execute these SQL files during page load.',
                    'The draft files are not proof that production schema has been applied.',
                ],
            ],
            [
                'title' => 'Manual Apply Rule',
                'points' => [
                    'Apply only through a trusted database client or controlled deployment process.',
                    'Owner/admin approval is required before any real database apply.',
                    'No app page, build queue, sync workflow, or normal staff/supplier page may apply migrations.',
                ],
            ],
            [
                'title' => 'Backup-Before-Apply Rule',
                'points' => [
                    'Back up the target database before applying any draft migration.',
                    'Production apply later must require extra confirmation.',
                    'Keep a copy of the applied file set and result summary for audit.',
                ],
            ],
            [
                'title' => 'Dry-Run / Check-First Rule',
                'points' => [
                    'Review file names, group order, expected tables, and indexes before apply.',
                    'Future runner check mode must show what would change before apply mode is available.',
                    'Any mismatch or uncertainty should stop the apply and produce a Red Issues Summary.',
                ],
            ],
            [
                'title' => 'File Naming Convention',
                'points' => [
                    'Use a four-digit sequence prefix, short group name, and .sql extension.',
                    'Keep files ordered by dependencies and module readiness.',
                    'Do not rename applied files after production use later.',
                ],
            ],
            [
                'title' => 'Rollback Planning',
                'points' => [
                    'Every applied migration should have a reviewed rollback plan before production apply.',
                    'Rollback files are not executed automatically.',
                    'Failed migration handling must stop and report issue, area, file, and suggested fix.',
                ],
            ],
            [
                'title' => 'Red Issues Summary Behavior',
                'points' => [
                    'Any failed check or apply later must stop immediately.',
                    'The summary must show severity, area, route or file path, issue detail, and suggested fix.',
                    'No next migration, build task, commit, or push should continue after a red issue.',
                ],
            ],
        ];
    }

    private function applyOrder()
    {
        return [
            '0002 core users, roles, and activity logs',
            '0003 business sources, suppliers, products, and stock/cost planning',
            '0004 status mapping and sync preview/import planning',
            '0005 orders, manual orders, and workflow history planning',
            '0006 dispatch, returns, payables, and settlement planning',
            '0007 invoices, printing, and supplier tool audit planning',
        ];
    }
}
