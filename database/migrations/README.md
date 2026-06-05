# Manual Migrations

Migrations in this directory are manual owner-action files. The application must not run these files automatically during page load.

The classes under `app/Models/` mirror these draft tables as metadata-only contracts (table name, ordered columns, explicit primary key). They contain no SQL and perform no execution; the migrations here remain manual-only and non-executing.

## Safety Rules

- Review every migration before running it.
- Back up the target database before applying changes.
- Run migrations manually from a trusted SQL client or controlled deployment process.
- Do not add page-load `CREATE TABLE`, `ALTER TABLE`, or schema repair code.
- Do not connect to OpenCart or depend on OpenCart admin tables.
- Do not connect to WooCommerce or import/sync orders from migration work.
- Future runner apply must be owner/admin controlled and must follow dry-run/check-first output first.
- Future production apply must show a backup reminder and require extra confirmation.
- Failed future runs must show a clear Red Issues Summary.

## Migration Files and Dry Run Planning

The `/migration-files` page documents manual SQL draft files. The `/migration-dry-run` page documents future dry-run validation before any real apply. The `/migration-runner` page still does not execute SQL, write migration records, or create migration tracking tables.

Draft files in this directory:

- `0002_core_users_roles_activity.sql`
- `0003_business_sources_suppliers_products.sql`
- `0004_status_mapping_sync_preview.sql`
- `0005_orders_manual_orders_workflow.sql`
- `0006_dispatch_returns_payables.sql`
- `0007_invoices_printing_supplier_tools.sql`
- `0008_supplier_opening_balances_launch_cutovers.sql`

Each draft must keep this header:

- DRAFT ONLY
- DO NOT AUTO RUN
- APPLY MANUALLY ONLY AFTER OWNER APPROVAL
- BACKUP DATABASE FIRST
- NOT EXECUTED BY APPLICATION PAGE LOAD

Planned future runner responsibilities:

1. List pending migration files and groups.
2. Verify file path and checksum details.
3. Show dry-run/check-first results before any apply action.
4. Require owner/admin confirmation before future apply.
5. Log actor, environment, timing, result, and Red Issues Summary later.
6. Keep rollback plans documented and separately approved.

## Future Process

1. Create a numbered migration file in this directory.
2. Review the SQL with the owner/admin.
3. Back up the database.
4. Apply the SQL manually.
5. Record the applied migration in release notes or a future migrations table created by an explicit migration.
