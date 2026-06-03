# IBS-LK Business Manager

**Version 0.1.2 - Authentication + Activity Log Foundation**

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

## Database

Edit `config/database.php` with your MySQL credentials. The Health Check page reports connection status without blocking the application.

The application uses PHP PDO directly through `App\Database`; no OpenCart database layer or ERP modules are included in v0.1.2.

Database schema changes must be explicit and manual. The application does not run `CREATE TABLE`, `ALTER TABLE`, or schema repair during page loads.

## Authentication

The current release keeps the configured single-admin login in `config/app.php` working. It prepares owner, admin, and staff wording for future role work, but does not add database-backed multi-user authentication yet.

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

## Health Check

The authenticated `/health` page reports:

- App Version v0.1.2
- PHP Version
- Database Connection Status
- Storage Writable Status
- Environment
- Current Server Time

## License

Proprietary — IBS-LK Business Manager.
