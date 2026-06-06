# ERP Staging — FTP Deploy Guide

Use this when your ERP staging host has **FTP/cPanel only** (no Git on server).

Product sync source: **https://staging.lokkisona.com** (OpenCart).  
Full product-sync runbook: [STAGING-PRODUCT-SYNC.md](STAGING-PRODUCT-SYNC.md).

## Step 1 — Build upload package on your PC

```powershell
cd D:\IBS-ERP\lokkisona-ibs-erp
powershell -ExecutionPolicy Bypass -File tools/staging-product-sync-readiness.ps1
powershell -ExecutionPolicy Bypass -File tools/check-local.ps1
powershell -ExecutionPolicy Bypass -File tools/staging-ftp-package.ps1 -Zip
```

This creates:

- Folder: `dist/staging-ftp-upload/`
- ZIP: `dist/staging-ftp-upload.zip`

Upload **one** of these to the server (ZIP is easier in FileZilla/cPanel).

## Step 2 — FTP upload layout

Upload the package so the server has this structure:

```text
/home/USERNAME/lokkisona-ibs-erp/
  app/
  config/
  database/
  public/          <-- web document root must point HERE
  resources/
  routes/
  storage/
  tools/
  ...
```

**Important:** `public/index.php` loads code from the **parent** folder (`app/`, `config/`, etc.).  
Do **not** upload only `public/` — the full repo must sit above the web root.

### cPanel subdomain

1. **Subdomains** → `ibs-staging.lokkisona.com`
2. **Document Root:** `/home/USERNAME/lokkisona-ibs-erp/public`
3. Enable **PHP 8.2+** for that domain

### If you must use `public_html` only

Some hosts only allow `public_html`. Options:

- **Best:** ask host to point subdomain docroot to a subfolder `public/` (as above).
- **Workaround:** upload repo to `/home/USERNAME/lokkisona-ibs-erp/` and set subdomain root to `.../public` in cPanel.

## Step 3 — Server permissions

Make storage writable (cPanel File Manager or FTP):

- `storage/` → 755
- `storage/logs/` → 755 (or 775 if activity log fails)

## Step 4 — Server config (first time only)

On the server, copy examples and edit (never commit secrets to Git):

| Copy from | To |
|-----------|-----|
| `config/database.staging.example.php` | `config/database.php` |
| `config/app.staging.example.php` | merge into `config/app.php` |
| `config/opencart.staging.example.php` | `config/opencart.php` |

### `config/opencart.php` for staging.lokkisona.com

```php
'enabled' => true,
'demo_mode' => false,
'api_base_url' => 'https://staging.lokkisona.com',
'api_key' => 'YOUR_STAGING_OC_API_KEY',
'product_api_route' => 'YOUR_OC_WAREHOUSE_PRODUCT_ROUTE',
```

### `config/app.php`

```php
'env' => 'staging',
'staging_gate' => ['enabled' => true, 'username' => '...', 'password' => '...'],
```

Change default `admin` / `supplier` passwords.

## Step 5 — Database migrations

Owner backup first. In phpMyAdmin or SQL client, run migrations manually:

**Minimum for product sync:** `0003`, `0004`, `0011`  
**Fresh DB:** full order in [STAGING-QA-CHECKLIST.md](STAGING-QA-CHECKLIST.md)

## Step 6 — Smoke test in browser

| URL | Expected |
|-----|----------|
| `https://ibs-staging.lokkisona.com/version` | v1.5.2 Staging Product Sync Deploy |
| `https://ibs-staging.lokkisona.com/dev-db-activation` | Groups A–F Ready |
| `https://ibs-staging.lokkisona.com/sync-preview` | Pull warehouse products button (if route set) |

## Step 7 — Product sync QA

1. `/status-mapping` — active mappings
2. `/sync-preview` — **Pull warehouse products**
3. `/product-control` — synced rows; supplier fields editable
4. `/sync-preview` — Test Sync + import 1–3 orders
5. `/activity-log` — pull + import events

## FTP updates (later releases)

1. Run `tools/staging-ftp-package.ps1 -Zip` on PC
2. Upload changed files **or** full package
3. **Skip overwriting** `config/database.php`, `config/opencart.php`, `config/app.php`
4. Re-check `/version` and `/health`
