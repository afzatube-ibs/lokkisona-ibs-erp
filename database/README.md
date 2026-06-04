# Database

Place SQL migrations and schema files here for IBS-LK Business Manager.

Configure connection settings in `config/database.php`.

Manual migration notes live in `database/migrations/`.

Database changes must be reviewed and applied by an owner/admin action outside page load. The application must not automatically create, alter, or repair schema while serving ERP pages.

v0.1.25 adds Migration Execution Lock planning only. The `/migration-execution-lock` page documents future locked-by-default behavior, wrong environment protection, dirty Git protection, failed dry-run protection, missing approval protection, backup missing protection, checksum mismatch protection, duplicate apply protection, emergency stop planning, and final lock state preview. It does not execute SQL or change the database.

The `/migration-approval` page documents future backup confirmation, environment confirmation, dry-run pass requirement, Red Issues clear state, checksum confirmation, apply order review, rollback planning, owner/admin approval, audit trail, and future manual execution only. It does not execute SQL or change the database.

The `/migration-dry-run` page documents future file scanning, safety validation, warnings/red issues, checksum planning, and owner approval before any future real apply. It does not execute SQL or change the database.

The migration file foundation added real SQL draft files for planning only. The `/migration-files` page documents the draft files, manual apply rule, backup-before-apply rule, dry-run/check-first rule, apply order, rollback planning, and Red Issues Summary behavior.

Draft migration files:

- `database/migrations/0002_core_users_roles_activity.sql`
- `database/migrations/0003_business_sources_suppliers_products.sql`
- `database/migrations/0004_status_mapping_sync_preview.sql`
- `database/migrations/0005_orders_manual_orders_workflow.sql`
- `database/migrations/0006_dispatch_returns_payables.sql`
- `database/migrations/0007_invoices_printing_supplier_tools.sql`

These files are not executed by application page load, Build Queue, Migration Runner, Migration Dry Run, Migration Approval, Migration Execution Lock, sync/import, staff pages, or supplier pages. Apply manually only after dry-run passes, approval gate is complete, execution lock is ready, owner approval is captured, rollback plan is reviewed, and database backup is confirmed.
