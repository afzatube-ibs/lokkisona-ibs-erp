# Lokkisona IBS ERP

**Version 0.1.1 - Database Connection + Health Check**

A standalone Enterprise Resource Planning foundation built with PHP 7.4+. This is **not** an OpenCart extension — no OCMOD, no ZIP installer. Deploy via Git.

## Requirements

- PHP 7.4 or higher
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

## Database

Edit `config/database.php` with your MySQL credentials. The Health Check page reports connection status without blocking the application.

The application uses PHP PDO directly through `App\Database`; no OpenCart database layer or ERP modules are included in v0.1.1.

## Health Check

The authenticated `/health` page reports:

- App Version v0.1.1
- PHP Version
- Database Connection Status
- Storage Writable Status
- Environment
- Current Server Time

## License

Proprietary — Lokkisona IBS ERP.
