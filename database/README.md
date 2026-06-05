# Database

Place SQL migrations and schema files here for IBS-LK Business Manager.

Configure connection settings in `config/database.php`.

Manual migration notes live in `database/migrations/`.

Database changes must be reviewed and applied by an owner/admin action outside page load. The application must not automatically create, alter, or repair schema while serving ERP pages.

## Model-to-Migration Contract

The classes under `app/Models/` are the metadata contract layer for these migration drafts. Each model declares its target table, an ordered `$columns` list, and an explicit primary key that mirror the corresponding `database/migrations/*.sql` draft. The models are pure metadata: they contain no PDO connection, no query building, and no read/write logic, so loading or reading a model never touches the database.

Writes are deliberately not implemented in the model classes. A future write service layer (owner-approved) will own all inserts, updates, and deletes once the migrations have been manually applied. The models describe the intended shape only; they do not execute SQL, apply migrations, or create/alter/drop tables. `App\Models\ModelRegistry` provides a read-only, in-memory table-to-model map for planning and coverage display only.

## Read-Only Repository and Service Layer (v0.2.2)

v0.2.2 adds a read-only database access foundation:

- `app/Database/Connection.php` — PDO factory (delegated from `App\Database`)
- `app/Database/TableName.php` — resolves `ibs_` prefixed physical names from logical model table names
- `app/Database/QueryGuard.php` — rejects non-SELECT SQL before repository execution
- `app/Repositories/` — six read-only repositories bound to existing model contracts
- `app/Services/ReadOnly/` — thin read services with no write methods

Repositories use `INFORMATION_SCHEMA` probes for `tableExists()` and return empty results when MySQL is down or migration drafts are not manually applied yet. The Database Safety page displays repository inventory and table-exists status only.

## Module Read Inventory (v0.2.3+)

v0.2.3 through v0.2.7 wire read inventory across suppliers, business sources, products, opening balances, orders, dispatch, returns, and payables. v0.2.8 completes coverage for invoices, database activity logs, users, and roles on `/invoice-printing`, `/activity-log`, `/users`, and `/roles-permissions`. Module pages use read services with try/catch fallbacks and show graceful empty states when `ibs_*` tables are not manually applied yet (migration `0002` for users/roles/activity, `0003` for supplier/product, `0005` for orders, `0006` for dispatch/return/payable, `0007` for invoices, `0008` for opening balance and launch cutover).

For future production, consider a MySQL read-only database user for reporting and read services. Write services remain a future owner-approved build.

v0.2.2 adds Supplier Opening Balance and Launch Cutover read-only repository skeletons only. The `/supplier-opening-balances` page documents old/manual supplier payable as a controlled ERP starting balance with cut-off date, supplier/source selection, reference note, proof planning, owner approval, audit requirement, adjustment safety, and launch cutover checklist. It does not create payable ledger records, change stock, upload files, or write opening balance records.

Opening balance workflow:

1. Confirm the old/manual payable calculation.
2. Select supplier, business source, or all sources.
3. Choose payable-to-supplier, advance-from-supplier, or neutral-zero-start.
4. Set cut-off date as the day before ERP real launch.
5. Require owner approval and audit trail before launch lock.
6. Start new ERP ledger transactions after cut-off only.

The `/migration-execution-lock` page documents future locked-by-default behavior, wrong environment protection, dirty Git protection, failed dry-run protection, missing approval protection, backup missing protection, checksum mismatch protection, duplicate apply protection, emergency stop planning, and final lock state preview. It does not execute SQL or change the database.

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
- `database/migrations/0008_supplier_opening_balances_launch_cutovers.sql`

These files are not executed by application page load, Build Queue, Migration Runner, Migration Dry Run, Migration Approval, Migration Execution Lock, Supplier Opening Balances, sync/import, staff pages, or supplier pages. Apply manually only after dry-run passes, approval gate is complete, execution lock is ready, owner approval is captured, rollback plan is reviewed, and database backup is confirmed.
