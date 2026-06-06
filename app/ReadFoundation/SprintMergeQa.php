<?php

namespace App\ReadFoundation;

/**
 * Sprint merge QA and dev database activation checklist (v0.4.2.1).
 * Documentation only — no SQL execution, no migration apply, no writes.
 */
class SprintMergeQa
{
    public static function activationChecklist(): array
    {
        return [
            [
                'item' => 'All routes open',
                'detail' => 'All checkpoint smoke routes return HTTP 200, 301, 302, or 303 without fatal errors.',
            ],
            [
                'item' => 'Checkpoint green',
                'detail' => 'tools/check-local.ps1 shows [OK] ALL GREEN with Red Issues: none before any dev DB activation.',
            ],
            [
                'item' => 'CSRF enabled on write forms',
                'detail' => 'All v0.3.1–v0.4.2 POST write forms include CSRF tokens and validate on submit.',
            ],
            [
                'item' => 'Write forms hidden/blocked when tables missing',
                'detail' => 'Write forms appear only when required ibs_* tables exist; otherwise pages show read inventory and planning content only.',
            ],
            [
                'item' => 'No page-load schema repair',
                'detail' => 'No CREATE TABLE, ALTER TABLE, DROP TABLE, or schema repair during page load or request handling.',
            ],
            [
                'item' => 'No auto migration apply',
                'detail' => 'Application, build queue, and checkpoint never apply migration SQL automatically.',
            ],
            [
                'item' => 'OpenCart sync gated',
                'detail' => 'Test Sync and controlled import require migration 0004 + mapping rows; live API disabled until opencart.enabled=true.',
            ],
            [
                'item' => 'No live data mutation without manual dev DB activation',
                'detail' => 'Write services are code-ready but inactive for mutation until owner manually applies migrations on dev/staging.',
            ],
            [
                'item' => 'Manual migration order confirmed',
                'detail' => 'Owner reviewed apply order 0002 through 0008 and phase gates before any SQL apply.',
            ],
            [
                'item' => 'Owner backup required before SQL apply',
                'detail' => 'Full database backup taken and confirmed before any manual migration SQL is executed.',
            ],
        ];
    }

    public static function devActivationGuide(): array
    {
        return [
            'Apply migrations manually only — never through page load, build queue, or checkpoint automation.',
            'Start with 0002_core_users_roles_activity.sql + 0003_business_sources_suppliers_products.sql as the minimum set for suppliers, business sources, and products.',
            'Apply 0005_orders_manual_orders_workflow.sql before manual order create and order workflow action tests.',
            'Apply 0006_dispatch_returns_payables.sql before dispatch report create, return receive, and payable read/write tests.',
            'Apply 0008_supplier_opening_balances_launch_cutovers.sql before opening balance approve and launch cutover lock tests.',
            'Verify ibs_* tables exist after each apply (SHOW TABLES or read inventory table_exists badges).',
            'Re-run tools/check-local.ps1 after apply and confirm [OK] ALL GREEN.',
            'Test write forms only on dev/staging first — no live production activation yet.',
        ];
    }

    public static function migrationPhaseMap(): array
    {
        return [
            ['migration' => '0002', 'file' => '0002_core_users_roles_activity.sql', 'enables' => 'Users, roles, activity logs (admin read inventory)'],
            ['migration' => '0003', 'file' => '0003_business_sources_suppliers_products.sql', 'enables' => 'Suppliers, business sources, products, variants, cost/stock write'],
            ['migration' => '0005', 'file' => '0005_orders_manual_orders_workflow.sql', 'enables' => 'Manual orders, order workflow actions'],
            ['migration' => '0004', 'file' => '0004_status_mapping_sync_preview.sql', 'enables' => 'Status mapping writes, Test Sync preview, controlled import'],
            ['migration' => '0006', 'file' => '0006_dispatch_returns_payables.sql', 'enables' => 'Dispatch report create, returns, payables read'],
            ['migration' => '0007', 'file' => '0007_invoices_printing_supplier_tools.sql', 'enables' => 'Invoice generate, print logs'],
            ['migration' => '0008', 'file' => '0008_supplier_opening_balances_launch_cutovers.sql', 'enables' => 'Opening balance approve, launch cutover lock'],
            ['migration' => '0009', 'file' => '0009_settlements_workflow.sql', 'enables' => 'Settlement workflow on /settlements'],
            ['migration' => '0010', 'file' => '0010_supplier_quick_invoice_totals.sql', 'enables' => 'Professional supplier quick invoice totals and customer fields'],
        ];
    }

    public static function writeModuleMatrix(): array
    {
        return [
            [
                'module' => 'Supplier create/edit',
                'page' => '/suppliers',
                'required_tables' => 'suppliers',
                'write_service' => 'SupplierWriteService',
                'safety_status' => 'CSRF + table gate + whitelist path',
                'migration_required' => '0003',
                'testing_status' => 'Pending dev DB activation',
            ],
            [
                'module' => 'Business source create/edit',
                'page' => '/business-sources',
                'required_tables' => 'business_sources, businesses',
                'write_service' => 'BusinessSourceWriteService',
                'safety_status' => 'CSRF + table gate + whitelist path',
                'migration_required' => '0003',
                'testing_status' => 'Pending dev DB activation',
            ],
            [
                'module' => 'Product create/edit',
                'page' => '/product-control',
                'required_tables' => 'products',
                'write_service' => 'ProductWriteService',
                'safety_status' => 'CSRF + table gate + whitelist path',
                'migration_required' => '0003',
                'testing_status' => 'Pending dev DB activation',
            ],
            [
                'module' => 'Variant create/edit',
                'page' => '/product-control',
                'required_tables' => 'product_variants',
                'write_service' => 'ProductVariantWriteService',
                'safety_status' => 'CSRF + table gate + whitelist path',
                'migration_required' => '0003',
                'testing_status' => 'Pending dev DB activation',
            ],
            [
                'module' => 'Cost/stock change',
                'page' => '/product-control',
                'required_tables' => 'supplier_product_costs, product_stock_histories, product_cost_histories',
                'write_service' => 'ProductCostStockWriteService',
                'safety_status' => 'CSRF + table gate + history rows',
                'migration_required' => '0003',
                'testing_status' => 'Pending dev DB activation',
            ],
            [
                'module' => 'Opening balance create/approve',
                'page' => '/supplier-opening-balances',
                'required_tables' => 'supplier_opening_balances, payable_ledgers',
                'write_service' => 'SupplierOpeningBalanceWriteService',
                'safety_status' => 'CSRF + table gate + owner approve gate',
                'migration_required' => '0008',
                'testing_status' => 'Pending dev DB activation',
            ],
            [
                'module' => 'Launch cutover lock',
                'page' => '/supplier-opening-balances',
                'required_tables' => 'launch_cutovers',
                'write_service' => 'LaunchCutoverWriteService',
                'safety_status' => 'CSRF + table gate + lock state',
                'migration_required' => '0008',
                'testing_status' => 'Pending dev DB activation',
            ],
            [
                'module' => 'Manual order create',
                'page' => '/manual-orders',
                'required_tables' => 'manual_orders, manual_order_items, orders, order_items, order_workflow_histories, products, product_variants, business_sources, suppliers',
                'write_service' => 'ManualOrderWriteService',
                'safety_status' => 'CSRF + table gate + duplicate ref block',
                'migration_required' => '0005',
                'testing_status' => 'Pending dev DB activation',
            ],
            [
                'module' => 'Order workflow actions',
                'page' => '/order-workflow',
                'required_tables' => 'orders, order_workflow_histories',
                'write_service' => 'OrderWorkflowWriteService',
                'safety_status' => 'CSRF + table gate + state machine',
                'migration_required' => '0005',
                'testing_status' => 'Pending dev DB activation',
            ],
            [
                'module' => 'Dispatch report create',
                'page' => '/dispatch-reports',
                'required_tables' => 'dispatch_reports, dispatch_report_items, orders, order_items, order_workflow_histories',
                'write_service' => 'DispatchReportWriteService::createDailyBatch',
                'safety_status' => 'CSRF + table gate + shipped eligibility + duplicate block + immutable cost snapshot + locked on create + workflow dispatch_report_created',
                'migration_required' => '0006',
                'testing_status' => 'Pending dev DB activation',
            ],
            [
                'module' => 'Return receive confirm',
                'page' => '/return-receive',
                'required_tables' => 'return_receives, return_batches, return_batch_items, orders, order_items, order_workflow_histories',
                'write_service' => 'ReturnReceiveWriteService::confirmReceive',
                'safety_status' => 'CSRF + table gate + hub_return/order_returning eligibility + duplicate block + reason/received/condition notes in workflow history + receive confirmation only',
                'migration_required' => '0006',
                'testing_status' => 'Pending dev DB activation',
            ],
            [
                'module' => 'Payable ledger create/approve',
                'page' => '/supplier-payables',
                'required_tables' => 'payable_ledgers, suppliers',
                'write_service' => 'PayableLedgerWriteService',
                'safety_status' => 'CSRF + table gate + owner post/reject + dispatch snapshot source only',
                'migration_required' => '0006',
                'testing_status' => 'Pending dev DB activation',
            ],
            [
                'module' => 'Return batch owner approve',
                'page' => '/return-receive',
                'required_tables' => 'return_batches',
                'write_service' => 'ReturnBatchWriteService::approveBatch',
                'safety_status' => 'CSRF + owner approve only; deduction requires separate payable draft',
                'migration_required' => '0006',
                'testing_status' => 'Pending dev DB activation',
            ],
            [
                'module' => 'Status mapping create/seed',
                'page' => '/status-mapping',
                'required_tables' => 'status_mappings, business_sources',
                'write_service' => 'StatusMappingWriteService',
                'safety_status' => 'CSRF + table gate + mapping required before sync',
                'migration_required' => '0004',
                'testing_status' => 'Pending dev DB activation',
            ],
            [
                'module' => 'Test Sync + controlled import',
                'page' => '/sync-preview',
                'required_tables' => 'sync_previews, sync_preview_items, sync_imports, orders',
                'write_service' => 'SyncPreviewWriteService, SyncImportWriteService',
                'safety_status' => 'CSRF + owner confirmation + max 50 + no background loops',
                'migration_required' => '0004 + 0005',
                'testing_status' => 'Pending dev DB activation',
            ],
            [
                'module' => 'Invoice generate + print log',
                'page' => '/invoice-printing',
                'required_tables' => 'invoices, invoice_items, print_logs',
                'write_service' => 'InvoiceWriteService, PrintLogWriteService',
                'safety_status' => 'CSRF + table gate + order snapshot only',
                'migration_required' => '0007',
                'testing_status' => 'Pending dev DB activation',
            ],
            [
                'module' => 'Settlement workflow',
                'page' => '/settlements',
                'required_tables' => 'settlements, payable_ledgers',
                'write_service' => 'SettlementWriteService',
                'safety_status' => 'CSRF + owner approve before paid/closed',
                'migration_required' => '0009',
                'testing_status' => 'Pending dev DB activation',
            ],
            [
                'module' => 'Supplier quick invoice generator',
                'page' => 'Header modal + /supplier-tools',
                'required_tables' => 'supplier_quick_invoices, supplier_quick_invoice_items, supplier_quick_invoice_audits',
                'write_service' => 'SupplierQuickInvoiceWriteService',
                'safety_status' => 'CSRF + one-time supplier access + no ERP payable impact',
                'migration_required' => '0007 + 0010',
                'testing_status' => 'Pending dev DB activation',
            ],
        ];
    }

    public static function postActivationNextBuilds(): array
    {
        return [
            [
                'version' => 'v0.6.0',
                'title' => 'Production launch after staging sign-off',
                'note' => 'Follow docs/PRODUCTION-LAUNCH.md — disable staging_gate, harden credentials, owner present for DB activation.',
            ],
        ];
    }
}
