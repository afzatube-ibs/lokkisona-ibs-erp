# IBS-LK Business Manager

**Version 0.1.5 - Local Checkpoint Runner Foundation**

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

## Database

Edit `config/database.php` with your MySQL credentials. The Health Check page reports connection status without blocking the application.

The application uses PHP PDO directly through `App\Database`; no OpenCart database layer or ERP modules are included in v0.1.5.

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
- orders
- order_items
- order_status_mappings
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

## Roles & Permissions

Roles and permissions are config-backed in `config/permissions.php` and enforced through `App\Permission` plus controller authorization helpers. The current configured admin is treated as owner-level access for now.

Prepared roles:

- owner
- admin
- staff
- supplier

Prepared permission groups include dashboard, health, version, activity log, roles and permissions, database safety, orders, product control, dispatch, returns, payable, and settings.

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
- Denied permission checks

## Health Check

The authenticated `/health` page reports:

- App Version v0.1.5
- PHP Version
- Database Connection Status
- Storage Writable Status
- Environment
- Current Server Time

## License

Proprietary — IBS-LK Business Manager.
