# Staging Product Sync — Operator Runbook

Deploy IBS-LK ERP to your **ERP staging host** and sync products/orders from OpenCart at **https://staging.lokkisona.com**.

General staging setup: [STAGING-DEPLOYMENT.md](STAGING-DEPLOYMENT.md). Product Control handoff: [V1.4.1-PRODUCT-CONTROL-HANDOFF.md](V1.4.1-PRODUCT-CONTROL-HANDOFF.md).

## Two sites

| Site | Role |
|------|------|
| ERP staging (e.g. `ibs-staging.lokkisona.com`) | This app — Product Control, Sync Preview |
| `staging.lokkisona.com` | OpenCart **source** — products + orders via API |

## Phase 0 — PC prep (before upload)

1. Confirm `origin/main` is current: `git pull --ff-only origin main`
2. Run: `powershell -ExecutionPolicy Bypass -File tools/check-local.ps1` — expect **ALL GREEN**
3. Run: `powershell -ExecutionPolicy Bypass -File tools/staging-product-sync-readiness.ps1`

## Phase 1 — Deploy ERP code

### Git (preferred)

On ERP staging server:

```bash
git clone https://github.com/afzatube-ibs/lokkisona-ibs-erp.git
cd lokkisona-ibs-erp
git pull --ff-only origin main
```

Point document root to `public/`. PHP 8.2+, MySQL 5.7+.

### FTP (fallback)

Upload the **entire repo** preserving folders. Web root = `public/`.

Required top-level paths: `app/`, `config/`, `database/`, `public/`, `resources/`, `routes/`, `tools/`.

**After first setup, do not overwrite** server-only files: `config/database.php`, `config/opencart.php`, `config/app.php` (credentials and `env`).

## Phase 2 — Server configuration

Copy from examples on server (edit values; **never commit secrets**):

| Example file | Copy to |
|--------------|---------|
| `config/database.staging.example.php` | `config/database.php` |
| `config/app.staging.example.php` | `config/app.php` |
| `config/opencart.staging.example.php` | `config/opencart.php` |

### OpenCart staging (`config/opencart.php`)

```php
'enabled' => true,
'demo_mode' => false,
'api_base_url' => 'https://staging.lokkisona.com',
'api_key' => 'OWNER_STAGING_OC_API_KEY',
'product_api_route' => 'YOUR_OC_WAREHOUSE_PRODUCT_ROUTE',
```

**Blockers on OpenCart staging:**

1. API key from OpenCart admin
2. `product_api_route` — warehouse product list endpoint (`product_id`, `name`, `model`, `quantity`, `from_warehouse`)
3. Products with **From Warehouse = Yes** (Dispatch Location)

Orders use: `{api_base_url}/index.php?route=api/order&api_token=...`

## Phase 3 — Database migrations

Owner **backup** staging DB first. Apply manually in SQL client — never via page load.

**Minimum for product sync:**

1. `database/migrations/0003_business_sources_suppliers_products.sql`
2. `database/migrations/0004_status_mapping_sync_preview.sql`
3. `database/migrations/0011_supplier_product_category.sql`

**Fresh ERP staging DB** — full order from [STAGING-QA-CHECKLIST.md](STAGING-QA-CHECKLIST.md): `0002` → `0003` → `0005` → `0006` → `0008` → `0004` → `0007` → `0009` → `0010` → `0011`.

Verify:

- `/dev-db-activation` — Groups A–F **Ready**
- `tools/check-local.ps1` on deploy machine — **ALL GREEN**

## Phase 4 — Product sync QA

| Step | Page | Action | Expected |
|------|------|--------|----------|
| 1 | `/status-mapping` | Seed/create Lokkisona status mappings | At least one active mapping |
| 2 | `/sync-preview` | **Pull warehouse products** | Products in Product Control; `from_warehouse=1` only; OC fields updated; supplier cost/stock preserved |
| 3 | `/product-control` | Open workspace | OC fields read-only; edit supplier fields |
| 4 | `/sync-preview` | **Run Test Sync** | Orders from `staging.lokkisona.com` |
| 5 | `/sync-preview` | **Import 1–3 orders** | `source_product_id` on lines; cost from mapped ERP product |
| 6 | `/activity-log` | Review | `warehouse_product_pull` + import events |

**Limits:** max 50 orders per batch; no Full Sync; manual product create disabled.

## Phase 5 — Sign-off

Complete full E2E in [STAGING-QA-CHECKLIST.md](STAGING-QA-CHECKLIST.md). Owner sign-off before [PRODUCTION-LAUNCH.md](PRODUCTION-LAUNCH.md).

## Gather before go-live

- [ ] ERP staging host + Git or FTP access
- [ ] Staging MySQL credentials
- [ ] OpenCart staging API key
- [ ] Confirmed `product_api_route` on staging OpenCart
- [ ] Staging products with From Warehouse = Yes
- [ ] Owner approval for `opencart.enabled=true`
