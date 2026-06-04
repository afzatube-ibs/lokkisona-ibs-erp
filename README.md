# IBS-LK Business Manager

**Version 0.1.14 - Status Mapping and Sync Planning Foundation**

A standalone Enterprise Resource Planning foundation built for PHP 8.2+. This is **not** an OpenCart extension — no OCMOD, no ZIP installer. Deploy via Git.

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
| GET    | `/users` | User management foundation (auth) |
| GET    | `/suppliers` | Supplier foundation (auth) |
| GET    | `/business-sources` | Business source and sales channel foundation (auth) |
| GET    | `/product-control` | Product control foundation (auth) |
| GET    | `/order-workflow` | Order workflow planning foundation (auth) |
| GET    | `/dispatch-reports` | Dispatch report planning foundation (auth) |
| GET    | `/supplier-payables` | Supplier payable planning foundation (auth) |
| GET    | `/return-receive` | Return receive planning foundation (auth) |
| GET    | `/status-mapping` | Status mapping and sync planning foundation (auth) |

## Database

Edit `config/database.php` with your MySQL credentials. The Health Check page reports connection status without blocking the application.

The application uses PHP PDO directly through `App\Database`; no OpenCart database layer or ERP modules are included in v0.1.9.

Database schema changes must be explicit and manual. The application does not run `CREATE TABLE`, `ALTER TABLE`, or schema repair during page loads.

Manual migration notes and planned schema files live in `database/migrations/`. They are owner/admin action files only; the application does not execute them automatically.

## Local Checkpoint

Run the local checkpoint after every build or foundation change:

```powershell
powershell -ExecutionPolicy Bypass -File tools/check-local.ps1
```

The checkpoint runs PHP lint, route smoke tests, version checks, forbidden text checks, database safety checks, and a git status summary. It does not commit or push.

## Business Architecture Direction

IBS-LK Business Manager starts with Iqbal & Brothers supplier operations and Lokkisona order workflow references, but the architecture stays channel-neutral. Future tables and modules should support multiple businesses, sales channels, manual/offline orders, supplier workflows, payable workflows, return workflows, and later expansion beyond one channel.

## Database Safety

The authenticated `/database-safety` page reports the current database connection, manual migration rules, no page-load schema rules, and pending planned tables.

Planned future tables are documented only:

- users
- roles
- user_roles
- activity_logs
- businesses
- sales_channels
- suppliers
- products
- product_variants
- supplier_product_costs
- product_stock_histories
- product_cost_histories
- orders
- order_items
- order_status_mappings
- status_mappings
- courier_status_mappings
- sync_previews
- sync_logs
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

No users table is created automatically and no database user records are written in v0.1.9.

## Supplier Management

The authenticated `/suppliers` page documents the Supplier Foundation only. It shows the current primary supplier (Iqbal & Brothers), supplier operation purpose, future supplier account structure, future payable/settlement, product cost/stock, order fulfillment and return/damage deduction links, and multi-supplier/multi-business readiness.

Operations begin with Iqbal & Brothers and the Lokkisona order workflow, but the architecture is channel-neutral and not hard-coded to a single supplier or sales channel.

Planned supplier fields documented only: supplier name, contact person, phone, email, address, payment terms, payable balance, status, linked business/channel, created at, updated at.

Supplier accounting wording: Product Cost Payable, Supplier Invoice, Additional Payable, Return/Damage Deduction, Payment Made to Supplier, Advance Received from Supplier, Net Payable to Supplier.

No suppliers table is created automatically and no supplier records are written in v0.1.9.

## Business Source & Sales Channel Management

The authenticated `/business-sources` page documents the Business Source and Sales Channel Foundation only. It shows the current primary source (Lokkisona.com), the current primary supplier relationship (Iqbal & Brothers), future source/channel structure, manual/offline order support, ecommerce channel support, marketplace/channel support, multi-business readiness, and how orders will later connect to supplier workflow, dispatch, returns, and payable.

The first source is Lokkisona.com, but the architecture is not hard-coded to one website. Future source types include Ecommerce Website, Manual Order, Offline Retail, Marketplace, Wholesale, and Other.

Planned business/source fields documented only: business name, channel name, source type, website/domain, order source label, status, default supplier, default workflow, created at, updated at.

No business, source, or sales channel tables are created automatically and no database records are written in v0.1.9.

## Product Control

The authenticated `/product-control` page documents the Product Control Foundation only. It shows the current supplier context (Iqbal & Brothers), product control purpose, future synced product structure, supplier-editable fields, read-only platform fields, cost/stock history rules, low stock warning rules, option/image reference rules, and the future payable/dispatch cost snapshot rule.

Business rules documented: OpenCart/improved option model and stock are read-only when synced later; supplier model, product cost, and vendor stock are editable with history; low warning is alert-only and does not auto-block workflows; option images should follow POIP/PIT Order Manager image reference logic; dispatch and payable must use cost snapshots, not live changing cost.

Planned product fields documented only: product_id/source_product_id, product name, image, source/channel, supplier, OC/source model read-only, OC/source stock read-only, supplier model, product cost, vendor stock, low warning threshold, status, last synced at, updated at.

Planned variant/option fields documented only: option/variant name, option value, source option id, source option value id, improved option model read-only, improved option stock read-only, supplier model, product cost, vendor stock, option image reference, POIP/PIT image reference note.

No product, variant, cost, or stock history tables are created automatically and no database records are written in v0.1.14. OpenCart sync is not connected in this release.

## Status Mapping & Sync Planning

The authenticated `/status-mapping` page documents the Status Mapping and Sync Planning Foundation only. It shows mapping purpose, source status to IBS workflow rules, Supplier Return and Lokkisona Return mapping rules, courier status mapping, independent IBS workflow after sync, skip Missing/status 0, unmapped status safety, Test Sync preview rules, performance/sync safety limits, manual/offline order support, and future mapping settings.

Sync rules documented: read Settings/Status Mapping first; no import without valid mapping; use current source status only at first sync; IBS workflow stays independent afterward; unmapped statuses go to review/blocked preview; Test Sync visible later with preview counts; Full Sync hidden from normal UI; max 50 orders per request; no background loops or retry storms.

Planned status mapping fields, sync preview fields, sync log fields, and order/sync list columns are documented only.

No status mapping, sync preview, or sync log tables are created automatically and no mapping/sync records are written in v0.1.14. OpenCart is not connected in this release.

## Roles & Permissions

Roles and permissions are config-backed in `config/permissions.php` and enforced through `App\Permission` plus controller authorization helpers. The current configured admin is treated as owner-level access for now.

Prepared roles:

- owner
- admin
- staff
- supplier

Prepared permission groups include dashboard, health, version, activity log, roles and permissions, database safety, users, suppliers, business sources, orders, order workflow, product control, dispatch, dispatch reports, returns, return receive, status mapping, sync, payable, supplier payables, and settings.

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
- Users page access
- Suppliers page access
- Business Sources page access
- Product Control page access
- Status Mapping page access
- Denied permission checks

## Health Check

The authenticated `/health` page reports:

- App Version v0.1.14
- PHP Version
- Database Connection Status
- Storage Writable Status
- Environment
- Current Server Time

## License

Proprietary — IBS-LK Business Manager.
