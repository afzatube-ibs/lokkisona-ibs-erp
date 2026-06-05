# IBS-LK Business Manager

**v0.2.4 Product and Variant Read Foundation**

A standalone Enterprise Resource Planning foundation built for PHP 8.2+. This is **not** an OpenCart extension — no OCMOD, no ZIP installer. Deploy via Git.

## What's New in v0.2.4

v0.2.4 extends the v0.2.3 read-only module pattern to Product Control. `/product-control` now shows live read-only inventory for products and product variants while keeping all planning foundation content below. No CRUD, no stock changes, no product cost changes, no database writes, no migrations applied, and no sync.

- `/product-control` calls `ProductReadService` and `ProductVariantReadService` through safe `buildEntityReadInventory()` helpers wrapped in try/catch.
- Read-Only Product Inventory section shows separate cards for `ibs_products` and `ibs_product_variants` with connection status, table readiness, model contract columns, row count, and up to 50 SELECT rows each.
- Graceful empty states when MySQL is unavailable or migration `0003_business_sources_suppliers_products.sql` is not manually applied yet.
- Shared partial `read-inventory-card.php` now accepts a configurable card title for reuse across module pages.
- No database writes, no sync, no stock change, no product cost change, and no migration apply from this page.

### Product and Variant Read Inventory

Read services delegate to existing repositories and `QueryGuard`. If tables do not exist yet, the page shows "Not applied" badges and clear migration-not-applied messages while the checkpoint stays green.

## What's New in v0.2.3

v0.2.3 wires the v0.2.2 read-only repository layer into the first real module pages: Suppliers and Business Sources. Pages use a hybrid layout — live read-only inventory at the top, planning foundation content below. No CRUD, no database writes, no migrations applied, and no sync.

- `/suppliers` and `/business-sources` call `SupplierReadService` and `BusinessSourceReadService` through controller `buildReadInventory()` helpers wrapped in try/catch.
- Read-Only Inventory cards show database connection, table readiness, prefixed table name (`ibs_*`), model contract columns, repository/service readiness, row count, and up to 50 SELECT rows when data exists.
- Graceful empty states when MySQL is unavailable or migration `0003_business_sources_suppliers_products.sql` is not manually applied yet — no blank page and no fatal error.
- Empty-state copy cites the `ibs_` prefix from `config/database.php` and manual migration requirement.
- Planning foundation sections (primary supplier/source, architecture cards, planned fields) are preserved under a "Planning Foundation" heading.
- No create, edit, delete, sync, or migration apply from these pages.

### Supplier and Business Source Read Inventory

Read services delegate to existing repositories and `QueryGuard`. If tables do not exist yet, pages show "Not applied" badges and a clear migration-not-applied message while the checkpoint stays green.

## What's New in v0.2.2

v0.2.2 adds a read-only database service/repository foundation on top of the v0.2.1 model contracts. This build prepares SELECT-only access structure only: no CRUD screens, no database writes, no migrations applied, and module pages were still planning-only until v0.2.3.

- Added `app/Database/` helpers: `Connection`, `TableName` (prefix-aware), `QueryGuard`, and `ReadOnlyQueryException`.
- Evolved `App\Database` facade to delegate PDO creation to `App\Database\Connection` while keeping `check()` for health and database safety pages.
- Added `app/Repositories/` with `ReadOnlyRepository`, `BaseReadOnlyRepository`, `ReadOnlyRepositoryRegistry`, and six read-only repositories for suppliers, business sources, products, product variants, supplier opening balances, and launch cutovers.
- Added `app/Services/ReadOnly/` thin read services delegating to the repositories with no write methods.
- `QueryGuard` allows only SELECT, SHOW, DESCRIBE, and EXPLAIN SQL and rejects mutation keywords at runtime.
- Database Safety page shows read-only repository inventory, query guard status, and per-table existence probes.
- Health check includes a Read-Only Query Guard status row.
- Checkpoint database safety scan now blocks runtime INSERT, UPDATE, DELETE, TRUNCATE, and REPLACE in PHP code.

### Read-Only Service Layer

Repositories bind to existing `app/Models/` metadata contracts and resolve prefixed table names from `config/database.php`. All queries pass through `QueryGuard` before execution. If MySQL is unavailable or migration drafts are not manually applied yet, repositories return empty results gracefully. Write services remain a future owner-approved build.

## What's New in v0.2.1

v0.2.1 organizes and corrects the model contract layer introduced in v0.2.0. This build is metadata/model-contract only: no CRUD screens, no SQL execution, no migrations applied, and no database writes.

- All 16 `app/Models/` classes corrected so each `TABLE`, `$columns` (ordered), and explicit primary key exactly mirror the manual migration drafts in `database/migrations/`.
- Added metadata-only `app/Models/BaseModel.php` (abstract) exposing read-only `table()`, `columns()`, and `primaryKey()` accessors — no PDO, query, `find`/`save`/`create`/`update`/`delete`, or schema logic.
- Added read-only `app/Models/ModelRegistry.php` providing an in-memory table-to-model map with no filesystem scanning side effects and no database calls.
- `App\Models\ActivityLog` is a future database contract only and is kept separate from the current file-based runtime logger `App\ActivityLog`.
- Remaining migration draft tables that have no class yet are recorded as "model pending" rather than mass-adding new model classes in this version.

### Model Layer / Database Contract

The classes under `app/Models/` are pure metadata contracts that describe the future database shape. Each model declares only its target table, its ordered column list (aligned to the migration draft), and an explicit primary key. Models contain no query builder, no PDO connection, and no read/write behavior. All future database writes will be owned by a dedicated service layer — never by the model classes — and schema changes remain manual, owner-approved migrations that the application never executes on page load.

## What's New in v0.2.0

v0.2.0 establishes the real database schema foundation. These additions are schema/skeleton scaffolding only; the application still does not execute any SQL, and schema changes remain manual owner/admin actions.

- `app/Models/` folder with 16 model skeleton classes (`ActivityLog`, `BusinessSource`, `DispatchReport`, `Invoice`, `LaunchCutover`, `Order`, `OrderItem`, `OrderWorkflowHistory`, `PayableLedger`, `Product`, `ProductVariant`, `ReturnReceive`, `Role`, `Supplier`, `SupplierOpeningBalance`, `User`).
- `database/migrations/` SQL draft files `0002` through `0008`, covering core users/roles/activity, business sources/suppliers/products, status mapping/sync, orders/manual orders/workflow, dispatch/returns/payables, invoices/printing/supplier tools, and supplier opening balances/launch cutovers.
- New draft `0008_supplier_opening_balances_launch_cutovers.sql` adds the `supplier_opening_balances`, `supplier_opening_balance_adjustments`, `supplier_opening_balance_audits`, and `launch_cutovers` tables.

All migration files remain manual drafts (`DRAFT ONLY` / `DO NOT AUTO RUN`) and are never executed by application page load.

## Requirements

- PHP 8.2 or higher
- MySQL 5.7+ for PDO database connection checks
- Apache with `mod_rewrite` or PHP built-in server

## Quick Start

### PHP Built-in Server

From the project root:

```bash
php -S localhost:8080 -t public public/router.php
```

Open [http://localhost:8080](http://localhost:8080)

### Apache

Point the document root to the `public/` directory. Ensure `mod_rewrite` is enabled.

## Default Login

| Field    | Value  |
|----------|--------|
| Username | `admin` |
| Password | `admin` |

Change credentials in `config/app.php` under the `auth` key.

## Project Structure

```
/public          Web root (index.php, assets)
/app             Application code (Router, Auth, Controllers)
/config          Configuration (app, database)
/database        Migrations and schema (future)
/resources/views Blade-style PHP views
/routes          Route definitions
/storage/logs    Application logs
```

## Routes

| Method | Path        | Description        |
|--------|-------------|--------------------|
| GET    | `/`         | Redirect to login or dashboard |
| GET    | `/login`    | Login page         |
| POST   | `/login`    | Authenticate       |
| GET    | `/logout`   | Sign out           |
| GET    | `/dashboard`| Dashboard (auth)   |
| GET    | `/health`   | Health check (auth)|
| GET    | `/version`  | Version info (auth)|
| GET    | `/activity-log` | Activity log (auth) |
| GET    | `/roles-permissions` | Role and permission foundation (auth) |
| GET    | `/database-safety` | Database safety and manual migration rules (auth) |
| GET    | `/migration-runner` | Real database migration runner planning foundation (auth) |
| GET    | `/migration-files` | Draft migration files planning foundation (auth) |
| GET    | `/migration-dry-run` | Migration dry-run validator planning foundation (auth) |
| GET    | `/migration-approval` | Migration apply approval gate planning foundation (auth) |
| GET    | `/migration-execution-lock` | Migration execution lock planning foundation (auth) |
| GET    | `/build-queue` | Build queue and semi-automation planning foundation (auth) |
| GET    | `/users` | User management foundation (auth) |
| GET    | `/suppliers` | Supplier foundation (auth) |
| GET    | `/business-sources` | Business source and sales channel foundation (auth) |
| GET    | `/product-control` | Product control foundation (auth) |
| GET    | `/order-workflow` | Order workflow planning foundation (auth) |
| GET    | `/dispatch-reports` | Dispatch report planning foundation (auth) |
| GET    | `/supplier-payables` | Supplier payable planning foundation (auth) |
| GET    | `/supplier-opening-balances` | Supplier opening balance and launch cutover planning foundation (auth) |
| GET    | `/return-receive` | Return receive planning foundation (auth) |
| GET    | `/status-mapping` | Status mapping and sync planning foundation (auth) |
| GET    | `/sync-preview` | Sync preview and import safety planning foundation (auth) |
| GET    | `/invoice-printing` | ERP invoice and packing print planning foundation (auth) |
| GET    | `/supplier-tools` | Supplier tools planning foundation (auth) |
| GET    | `/manual-orders` | Manual and external order planning foundation (auth) |

## Database

Edit `config/database.php` with your MySQL credentials. The Health Check page reports connection status without blocking the application.

The application uses PHP PDO directly through `App\Database`; no OpenCart database layer or ERP modules are included in v0.1.26.

Database schema changes must be explicit and manual. The application does not run `CREATE TABLE`, `ALTER TABLE`, `DROP TABLE`, or schema repair during page loads.

Manual migration notes and planned schema files live in `database/migrations/`. They are owner/admin action files only; the application does not execute them automatically.

The authenticated `/migration-runner` page is planning-only. It documents the future real migration runner workflow, including manual-only execution, dry-run/check-first review, backup-before-apply, owner/admin confirmation, audit/log requirements, rollback planning, production safety, and Red Issues Summary behavior. It does not run SQL, write migration records, or create migration tables. Build Queue and semi-automation must never trigger migration apply automatically.

The authenticated `/migration-files` page documents draft SQL migration files. The SQL files under `database/migrations/` are manual drafts only and are not executed by application page load.

The authenticated `/migration-dry-run` page documents the future dry-run/check layer. It will scan files, validate safety, show warnings/red issues, and require owner approval before any future real apply. v0.1.26 does not execute SQL, write dry-run records, or change the database.

The authenticated `/migration-approval` page documents the future apply approval gate. It requires migration file drafts, successful dry-run validation, backup confirmation, owner/admin approval, apply gate review, future manual execution only, audit trail planning, and rollback plan confirmation. It does not execute SQL, approve records, or apply migrations.

The authenticated `/migration-execution-lock` page documents the future final execution lock. It protects against wrong environment, dirty Git, failed dry-run, missing approval, missing backup, checksum mismatch, duplicate apply, and emergency stop conditions. It does not execute SQL, unlock execution, write records, or apply migrations.

The authenticated `/supplier-opening-balances` page documents Supplier Opening Balance and Launch Cutover planning. It treats the estimated old/manual payable to Iqbal & Brothers, about 1,200,000 BDT, as a controlled ERP starting balance with cut-off date, owner approval, proof planning, audit planning, and launch lock. It does not create payable ledger records, change stock, upload files, or write opening balance records.

Draft migration files:

- `0002_core_users_roles_activity.sql`
- `0003_business_sources_suppliers_products.sql`
- `0004_status_mapping_sync_preview.sql`
- `0005_orders_manual_orders_workflow.sql`
- `0006_dispatch_returns_payables.sql`
- `0007_invoices_printing_supplier_tools.sql`
- `0008_supplier_opening_balances_launch_cutovers.sql`

Migration draft safety workflow:

1. Review the draft file and apply order.
2. Back up the target database.
3. Run dry-run/check-first review.
4. Apply manually only after owner approval.
5. Stop on any Red Issues Summary.
6. Do not apply from page load, Build Queue, sync/import, staff pages, or supplier pages.

Migration dry-run workflow:

1. Scan migration files.
2. Validate file naming, order, required warning headers, duplicate migration keys, and runtime SQL safety.
3. Show detected operations, affected tables, warnings, and red issues.
4. Make no database changes.
5. Require owner approval and backup confirmation before any future real apply.

Migration apply approval workflow:

1. Confirm migration file drafts and checksums.
2. Confirm dry-run validation passed.
3. Confirm database backup and backup reference.
4. Confirm target environment and production warning if needed.
5. Confirm Red Issues count is zero.
6. Capture owner/admin approval and operator confirmation later.
7. Keep audit trail and rollback plan reference.
8. Future execution remains manual only.

Migration execution lock workflow:

1. Start locked by default.
2. Confirm migration draft files and dry-run result.
3. Confirm approval gate, backup, clean/synced Git, checksum, rollback plan, and zero Red Issues.
4. Keep wrong environment, duplicate apply, missing rollback, and emergency stop as blocking lock states.
5. Allow only a ready-but-manual-only state later; no automatic execution is planned.
6. Preserve audit trail for lock state changes later.

Full future migration safety workflow: migration file draft -> dry-run validation -> approval gate -> execution lock -> future manual-only apply -> emergency stop support -> audit trail.

Supplier opening balance workflow:

1. Confirm the old/manual supplier payable calculation before ERP launch.
2. Select supplier, business source, or all sources.
3. Choose balance type: payable to supplier, advance from supplier, or neutral zero start.
4. Set the cut-off date as the day before ERP real launch.
5. Add calculation summary, reference note, and proof attachment later.
6. Require owner approval and audit trail before launch lock.
7. Start new ERP ledger transactions after cut-off only.

Old manual balance vs new ERP ledger:

- Old product costs, supplier received amounts, return deductions, and manual adjustments are summarized into opening balance.
- New Product Cost Payable starts after cut-off from dispatch/payable workflow.
- New Payment Made, Return Deduction, Additional Payable, and Advance Received affect the balance after opening.
- Old manual payable must not be mixed into new dispatch payable or normal order payable.

## Local Checkpoint

Run the local checkpoint after every build or foundation change:

```powershell
powershell -ExecutionPolicy Bypass -File tools/check-local.ps1
```

The checkpoint runs PHP lint, route smoke tests, version checks, forbidden text checks, database safety checks, and a git status summary. It does not commit or push.

Every checkpoint ends with a compact plain text footer. Passing runs show `[OK] ALL GREEN`, version, checkpoint status, browser/route status, git summary note, and `Red Issues: none`. Failing runs keep detailed error output and end with `[FAIL] RED ISSUES SUMMARY` listing each issue, area, file/page, and what to fix for easy copy/paste into ChatGPT.

PHP path notes:

- Home PC: `E:\xampp\php\php.exe`
- Office PC: `D:\xampp\php\php.exe`
- The checkpoint also tries `C:\xampp\php\php.exe` and `php` from PATH.

Owner-triggered finish script:

```powershell
powershell -ExecutionPolicy Bypass -File tools/finish-build.ps1 "v0.1.26 Supplier Opening Balance and Launch Cutover Planning Foundation"
```

`tools/finish-build.ps1` is owner-triggered only. It runs the checkpoint first, stops without commit or push on Red Issues, and never applies database migrations, syncs/imports orders, or changes stock, payables, or invoices.

## Build Queue & Semi-Automation

The authenticated `/build-queue` page documents safe build queue planning only. v0.1.26 does not create build queue tables, write build queue records, auto-run next tasks, commit, or push.

Safe build workflow:

1. Read the next build task from the build queue.
2. Apply one build or one small safe batch.
3. Run `powershell -ExecutionPolicy Bypass -File tools/check-local.ps1`.
4. If `[OK] ALL GREEN`, show version, changed files, browser/route count, `Red Issues: none`, and recommended next build.
5. If `[FAIL] RED ISSUES SUMMARY`, stop immediately and do not continue to the next task.
6. Wait for owner approval before commit or push.
7. Start the next build only after Git is synced with `origin/main`.

Semi-automation levels:

- Level 1: Manual task prompt plus manual checkpoint plus manual commit/push.
- Level 2: Build queue suggests the next task, checkpoint footer is shown, commit/push stay manual.
- Level 3: Small safe batch of 2-3 related planning pages, checkpoint, then manual owner review.

Blocked automation: automatic commit, automatic push, automatic database migration apply, automatic OpenCart/WooCommerce sync, automatic order import, automatic payable mutation, automatic stock deduction, automatic opening balance ledger creation, and automatic invoice generation. Migration dry-run, approval gate planning, and execution lock readiness must pass before migration-related build work can move forward later.

Planned build queue fields are documented only: `build_queue_id`, `build_version`, `build_title`, `build_type`, `module_area`, `priority`, `status`, `depends_on_version`, `expected_routes`, `expected_permissions`, `checkpoint_required`, `browser_check_required`, `owner_approval_required`, `created_by`, `created_at`, `completed_at`.

Planned build run fields are documented only: `build_run_id`, `build_queue_id`, `started_by`, `started_at`, `finished_at`, `checkpoint_status`, `route_smoke_status`, `red_issues_count`, `git_status`, `result_summary`, `next_recommended_build`.

Planned red issue fields are documented only: `red_issue_id`, `build_run_id`, `severity`, `area`, `file_path`, `route`, `issue_title`, `issue_detail`, `suggested_fix`, `status`, `created_at`.

## Business Architecture Direction

IBS-LK Business Manager starts with Iqbal & Brothers supplier operations and Lokkisona order workflow references, but the architecture stays channel-neutral. Future tables and modules should support multiple businesses, sales channels, manual/offline orders, supplier workflows, payable workflows, return workflows, and later expansion beyond one channel.

## Database Safety

The authenticated `/database-safety` page reports the current database connection, manual migration rules, draft migration files, dry-run validation planning, approval gate planning, execution lock planning, no page-load schema rules, migration runner planning, build automation boundaries, and pending planned tables.

The authenticated `/migration-runner` page documents the future controlled runner. Current scope is planning only:

- No SQL migration execution.
- No migration tables or migration records.
- No page-load schema changes.
- No hidden installer or automatic self-healing database code.
- Future apply must be explicit owner/admin action only.
- Future apply must require successful dry-run/check-first output and a backup reminder first.
- Future production apply must require extra confirmation.
- Future failed runs must show a clear Red Issues Summary.
- Build Queue and semi-automation must never trigger migration apply automatically.
- Draft migration files exist under `database/migrations/` but remain manual-only.
- Dry-run validation must pass before future migration apply planning can continue.
- Migration Approval Gate must confirm backup, environment, checksum, apply order, rollback plan, owner/admin approval, and Red Issues clear state before future manual execution.
- Migration Execution Lock must protect against wrong environment, dirty Git, failed dry-run, missing approval, missing backup, checksum mismatch, duplicate apply, missing rollback, and emergency stop conditions.

Planned migration groups are documented only:

- Core users and roles
- Activity logs
- Business sources
- Suppliers
- Products and variants
- Product cost and stock histories
- Status mappings
- Sync previews and imports
- Orders and order items
- Manual/external orders
- Dispatch reports
- Supplier payables and settlements
- Return receive and return batches
- Invoice and print logs
- Supplier tools audit

Planned migration runner fields are documented only: `migration_id`, `migration_key`, `migration_name`, `migration_group`, `file_path`, `checksum`, `status`, `applied_by`, `applied_at`, `execution_time_ms`, `error_message`, `created_at`.

Planned migration run log fields are documented only: `migration_run_id`, `run_type`, `environment`, `total_pending`, `total_applied`, `total_failed`, `started_by`, `started_at`, `finished_at`, `result_status`, `red_issues_summary`.

Planned rollback fields are documented only: `rollback_id`, `migration_id`, `rollback_plan`, `rollback_file_path`, `approved_by`, `executed_by`, `executed_at`, `status`.

Planned future tables are documented only:

- users
- roles
- user_roles
- activity_logs
- businesses
- sales_channels
- suppliers
- supplier_quick_invoices
- supplier_quick_invoice_items
- supplier_quick_invoice_audits
- products
- product_variants
- supplier_product_costs
- product_stock_histories
- product_cost_histories
- orders
- order_items
- manual_orders
- manual_order_items
- manual_order_audits
- order_status_mappings
- status_mappings
- courier_status_mappings
- sync_previews
- sync_preview_items
- sync_imports
- sync_logs
- source_product_mappings
- courier_accounts
- invoices
- invoice_items
- invoice_templates
- packing_prints
- print_logs
- dispatch_reports
- dispatch_report_items
- supplier_returns
- owner_returns
- payable_ledgers
- supplier_invoices
- supplier_payments
- settings

## Authentication

The current release keeps the configured single-admin login in `config/app.php` working. It prepares owner, admin, staff, and supplier wording for future role work, but does not add database-backed multi-user authentication yet.

## User Management

The authenticated `/users` page documents the User Management foundation only. It shows the current config-based admin login mode, planned roles, planned user fields, security rules, and the manual migration requirement before real database users are enabled.

No users table is created automatically and no database user records are written in v0.1.26.

## Supplier Management

The authenticated `/suppliers` page shows live read-only supplier inventory (v0.2.3) plus Supplier Foundation planning content. The read inventory uses `SupplierReadService` with graceful empty states when the database or `ibs_suppliers` table is unavailable. Planning sections document the current primary supplier (Iqbal & Brothers), supplier operation purpose, future supplier account structure, future payable/settlement, product cost/stock, order fulfillment and return/damage deduction links, and multi-supplier/multi-business readiness.

Operations begin with Iqbal & Brothers and the Lokkisona order workflow, but the architecture is channel-neutral and not hard-coded to a single supplier or sales channel.

Planned supplier fields documented only: supplier name, contact person, phone, email, address, payment terms, payable balance, status, linked business/channel, created at, updated at.

Supplier accounting wording: Product Cost Payable, Supplier Invoice, Additional Payable, Return/Damage Deduction, Payment Made to Supplier, Advance Received from Supplier, Net Payable to Supplier.

No suppliers table is created automatically and no supplier records are written in this release. When migration `0003` is manually applied with the `ibs_` prefix, the page shows up to 50 read-only rows.

## Business Source & Sales Channel Management

The authenticated `/business-sources` page shows live read-only business source inventory (v0.2.3) plus Business Source and Sales Channel Foundation planning content. The read inventory uses `BusinessSourceReadService` with graceful empty states when the database or `ibs_business_sources` table is unavailable. Planning sections document the current primary source (Lokkisona.com), the current primary supplier relationship (Iqbal & Brothers), future source/channel structure, manual/offline order support, ecommerce channel support, marketplace/channel support, multi-business readiness, and how orders will later connect to supplier workflow, dispatch, returns, and payable.

The first source is Lokkisona.com, but the architecture is not hard-coded to one website. Future source types include Ecommerce Website, Manual Order, Offline Retail, Marketplace, Wholesale, and Other.

Planned business/source fields documented only: business name, channel name, source type, website/domain, order source label, status, default supplier, default workflow, created at, updated at.

No business, source, or sales channel tables are created automatically and no database records are written in this release. When migration `0003` is manually applied with the `ibs_` prefix, the page shows up to 50 read-only rows.

## Product Control

The authenticated `/product-control` page shows live read-only product and variant inventory (v0.2.4) plus Product Control Foundation planning content. Read inventory uses `ProductReadService` and `ProductVariantReadService` with graceful empty states when the database or `ibs_products` / `ibs_product_variants` tables are unavailable. Planning sections document the current supplier context (Iqbal & Brothers), product control purpose, future synced product structure, supplier-editable fields, read-only platform fields, cost/stock history rules, low stock warning rules, option/image reference rules, and the future payable/dispatch cost snapshot rule.

Business rules documented: OpenCart/improved option model and stock are read-only when synced later; supplier model, product cost, and vendor stock are editable with history; low warning is alert-only and does not auto-block workflows; option images should follow POIP/PIT Order Manager image reference logic; dispatch and payable must use cost snapshots, not live changing cost.

Planned product fields documented only: product_id/source_product_id, product name, image, source/channel, supplier, OC/source model read-only, OC/source stock read-only, supplier model, product cost, vendor stock, low warning threshold, status, last synced at, updated at.

Planned variant/option fields documented only: option/variant name, option value, source option id, source option value id, improved option model read-only, improved option stock read-only, supplier model, product cost, vendor stock, option image reference, POIP/PIT image reference note.

No product, variant, cost, or stock history tables are created automatically and no database records are written in this release. When migration `0003` is manually applied with the `ibs_` prefix, the page shows up to 50 read-only rows per table. OpenCart sync is not connected in this release.

## Status Mapping & Sync Planning

The authenticated `/status-mapping` page documents the Status Mapping and Sync Planning Foundation only. It shows mapping purpose, source status to IBS workflow rules, Supplier Return and Lokkisona Return mapping rules, courier status mapping, independent IBS workflow after sync, skip Missing/status 0, unmapped status safety, Test Sync preview rules, performance/sync safety limits, manual/offline order support, and future mapping settings.

Sync rules documented: read Settings/Status Mapping first; no import without valid mapping; use current source status only at first sync; IBS workflow stays independent afterward; unmapped statuses go to review/blocked preview; Test Sync visible later with preview counts; Full Sync hidden from normal UI; max 50 orders per request; no background loops or retry storms.

Planned status mapping fields, sync preview fields, sync log fields, and order/sync list columns are documented only.

No status mapping, sync preview, or sync log tables are created automatically and no mapping/sync records are written in v0.1.26. OpenCart is not connected in this release.

## Sync Preview & Import Safety

The authenticated `/sync-preview` page documents the Sync Preview and Import Safety Foundation only. It covers multi-source sync planning (Lokkisona/OpenCart, Sonamoni/WooCommerce, Manual/Offline), shared supplier stock, invoice reference/template preparation, mapping-first sync, preview-before-import, duplicate/existing order blocking, independent IBS workflow, return candidate separation, and import confirmation/audit rules.

Sync/import should prepare source invoice reference and ERP invoice template type only. Full invoice and packing print planning lives on `/invoice-printing`.

Preview totals, preview table columns, and planned sync preview, preview item, and import approval fields are documented only.

No sync preview, sync import, sync log, or order tables are created automatically and no sync/import records are written in v0.1.26. OpenCart and WooCommerce are not connected in this release.

## ERP Invoice & Packing Print Planning

The authenticated `/invoice-printing` page documents the ERP Invoice and Packing Print Planning Foundation only. ERP must have its own invoice print system: Lokkisona orders use an ERP Lokkisona-style invoice later, Sonamoni orders use an ERP Sonamoni-style invoice later, and manual/offline orders use an ERP manual invoice later.

Source invoice references can be stored, but ERP print must be independent and must not depend on source admin login. The current invoice extension, real Lokkisona invoice sample, and PIT Order Manager are read-only business/layout references only; no old extension code is copied.

Planned invoice layout sections: Header, Customer/Order block, Payment summary, Product table, Courier/tracking block, Footer. PIT courier reference fields: courier_account_id, courier_name, consignment_id, tracking_number, tracking_url, courier_status, courier_qr_reference.

Print rules documented: customer invoice must not show supplier cost; supplier model/cost can be used in internal packing/dispatch documents only; ERP invoice prints from ERP order snapshot; print/download actions should be logged later; reprint rules are planned later.

Planned document types: Customer Invoice, Packing Invoice / Packing Slip, Dispatch Batch Report, Supplier Product Summary, Return Receive Batch Print, Supplier Payable Settlement Summary.

No invoice, invoice item, packing print, print log, or invoice template tables are created automatically and no invoice/print records are written in v0.1.26.

## Supplier Tools Planning

The authenticated `/supplier-tools` page documents the Supplier Tools Planning Foundation only. Supplier Tools are independent engagement tools for supplier convenience and must not affect official ERP financial workflow.

Planned tools: Supplier Quick Invoice Generator and Simple Calculator.

Supplier Quick Invoice Generator rules: independent tool only; does not create ERP orders; does not affect supplier payable, settlement, official ERP invoice, stock, courier, dispatch, returns, sync/import, or accounting; supplier can create and print/download once; supplier cannot reopen/edit/view after creation/download; owner/admin backend can see audit/history/details; every generated invoice later must have audit; owner/admin may later review/convert manually, with no automatic conversion.

Simple Calculator rules: basic standalone calculator only; no payable calculation; no settlement helper; no product cost calculation; no courier charge calculation; no save to ERP accounting; no system impact; no database write required for calculator.

Planned supplier tool fields are documented only for supplier_quick_invoices, supplier_quick_invoice_items, and supplier_quick_invoice_audits. No supplier tools tables are created automatically, no supplier quick invoice records are written, no real invoice generator form is built, and no real calculator is built in v0.1.26.

## Manual & External Order Planning

The authenticated `/manual-orders` page documents the Manual and External Order Planning Foundation only. It supports planning for Sonamoni.com.bd WooCommerce orders as Manual / External Reference Orders before direct WooCommerce sync, plus offline/manual sales through direct ERP entry.

Manual / External Orders must behave like normal IBS orders after entry while clearly showing source/reference. Planned examples include Sonamoni manual reference orders with WooCommerce order/invoice references and Manual / Offline orders with manual invoice/reference.

Safety rules documented: business source selection, external reference preservation, product/variant mapping, shared vendor stock, cost snapshot capture, workflow entry after confirmation, source-aware ERP invoice template planning, confirmation/audit, duplicate external reference blocking, and future direct WooCommerce sync upgrade.

Planned manual order, manual order item, and manual order audit fields are documented only. No manual order tables are created automatically, no manual/external order records are written, no payable records are created, no stock is deducted, no invoice is generated, and no OpenCart/WooCommerce sync is connected in v0.1.26.

## Roles & Permissions

Roles and permissions are config-backed in `config/permissions.php` and enforced through `App\Permission` plus controller authorization helpers. The current configured admin is treated as owner-level access for now.

Prepared roles:

- owner
- admin
- staff
- supplier

Prepared permission groups include dashboard, health, version, activity log, roles and permissions, database safety, migration runner, migration files, migration dry-run, migration approval, migration execution lock, build queue, users, suppliers, supplier opening balances, supplier tools, business sources, orders, manual orders, order workflow, product control, dispatch, dispatch reports, returns, return receive, status mapping, sync, sync preview, sync import, invoice printing, payable, supplier payables, and settings.

Migration planning permissions are prepared as `migrations.view`, `migrations.manage`, `migration_runner.view`, `migration_runner.manage`, `migration_files.view`, `migration_files.manage`, `migration_dry_run.view`, `migration_dry_run.manage`, `migration_approval.view`, `migration_approval.manage`, `migration_apply.view`, `migration_apply.manage`, `migration_execution_lock.view`, and `migration_execution_lock.manage`. Owner has full access; admin has migration planning access; staff and supplier do not manage migrations.

Build queue planning permissions are prepared as `build_queue.view`, `build_queue.manage`, `build_automation.view`, and `build_automation.manage`. Owner has full access; admin has build planning access; staff and supplier do not manage build automation.

Supplier opening balance permissions are prepared as `supplier_opening_balances.view`, `supplier_opening_balances.manage`, and `supplier_opening_balances.approve`. Owner has full access; admin has planning access; staff and supplier do not approve opening balances.

## Activity Log

Activity events are appended to `storage/logs/activity.log` as JSON lines through `App\ActivityLog`. The log is file-backed and safe for the foundation release; it does not require database tables.

Logged foundation events include:

- Login
- Logout
- Failed login
- Dashboard access
- Health check access
- Version page access
- Activity log access
- Roles and permissions page access
- Database safety page access
- Migration Runner page access
- Migration Files page access
- Migration Dry Run page access
- Migration Approval page access
- Migration Execution Lock page access
- Supplier Opening Balances page access
- Build Queue page access
- Users page access
- Suppliers page access
- Business Sources page access
- Product Control page access
- Status Mapping page access
- Sync Preview page access
- Invoice Printing page access
- Supplier Tools page access
- Manual Orders page access
- Denied permission checks

## Health Check

The authenticated `/health` page reports:

- App Version v0.1.26
- PHP Version
- Database Connection Status
- Storage Writable Status
- Environment
- Current Server Time

## License

Proprietary — IBS-LK Business Manager.
