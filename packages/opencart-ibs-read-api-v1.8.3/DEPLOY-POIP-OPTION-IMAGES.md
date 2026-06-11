# OpenCart Staging Deploy — POIP Option Image API Repair (v1.8.3.2)

ERP release: **v2.1.8.3.2** (not committed until UI screenshot proof).

This patch makes the IBS read API return real per-option images from POIP / Improved Options data (COALESCE join on OpenCart server). Without this deploy, staging returns `"image":""` for every option and ERP option rows all show the parent product thumbnail.

## Package source (ERP repo)

```
packages/opencart-ibs-read-api-v1.8.3/UPLOAD/
```

Upload to **OpenCart staging web root** (same level as `catalog/`, `system/`, `index.php`).

## Package v1.8.3.2 — full file tree

### `system/library/ibs/` (required for all IBS API routes)

| File | Class | Loaded as |
|------|-------|-----------|
| `api_auth.php` | `IbsApiAuth` | `$this->load->library('ibs/api_auth')` → `$this->ibs_api_auth` |
| `api_response.php` | `IbsApiResponse` | `$this->load->library('ibs/api_response')` → `$this->ibs_api_response` |

Reads config from `system/config/ibs_api.php` (not in `UPLOAD/` — create on server from `config/ibs_api.example.php`).

### `catalog/model/api/ibs/`

| File | Class | Loaded as |
|------|-------|-----------|
| `product.php` | `ModelApiIbsProduct` | `$this->load->model('api/ibs/product')` → `$this->model_api_ibs_product` |
| `order.php` | `ModelApiIbsOrder` | `$this->load->model('api/ibs/order')` → `$this->model_api_ibs_order` |

### `catalog/controller/api/ibs/`

| File | Class | Route |
|------|-------|-------|
| `connection_test.php` | `ControllerApiIbsConnectionTest` | `api/ibs/connection_test` |
| `products.php` | `ControllerApiIbsProducts` | `api/ibs/products` |
| `orders.php` | `ControllerApiIbsOrders` | `api/ibs/orders` |

### Dependency graph

```
connection_test.php ──┬── library ibs/api_auth.php
products.php       ──┤── library ibs/api_response.php
orders.php         ──┘
                     │
connection_test.php ── model api/ibs/product.php
products.php       ── model api/ibs/product.php
orders.php         ── model api/ibs/order.php
                     │
api_auth.php         ── config system/config/ibs_api.php (server)
```

**Crash fix:** `Could not load library 'ibs/api_auth'` means `system/library/ibs/api_auth.php` (and usually `api_response.php`) were **not** uploaded.

## Exact FTP upload list

Upload from `packages/opencart-ibs-read-api-v1.8.3/UPLOAD/` to **OpenCart staging root** (same level as existing `catalog/` and `system/`).

### POIP repair minimum (5 files)

Required for `connection_test` + `products` refresh + option images:

| # | Upload from (repo) | Upload to (staging) |
|---|-------------------|---------------------|
| 1 | `UPLOAD/system/library/ibs/api_auth.php` | `system/library/ibs/api_auth.php` |
| 2 | `UPLOAD/system/library/ibs/api_response.php` | `system/library/ibs/api_response.php` |
| 3 | `UPLOAD/catalog/controller/api/ibs/connection_test.php` | `catalog/controller/api/ibs/connection_test.php` |
| 4 | `UPLOAD/catalog/controller/api/ibs/products.php` | `catalog/controller/api/ibs/products.php` |
| 5 | `UPLOAD/catalog/model/api/ibs/product.php` | `catalog/model/api/ibs/product.php` |

Create `system/library/ibs/` on the server if the folder does not exist.

### Full package (7 files — recommended)

Add order sync parity with v1.8.3:

| # | Upload from (repo) | Upload to (staging) |
|---|-------------------|---------------------|
| 6 | `UPLOAD/catalog/controller/api/ibs/orders.php` | `catalog/controller/api/ibs/orders.php` |
| 7 | `UPLOAD/catalog/model/api/ibs/order.php` | `catalog/model/api/ibs/order.php` |

### Server config (not in `UPLOAD/`)

If `system/config/ibs_api.php` is missing, copy `config/ibs_api.example.php` from the package to `system/config/ibs_api.php` on staging and set `api_token` to match ERP Sync/API Settings.

**Recommended:** upload all 7 `UPLOAD/` files together so versions stay aligned.

## Pre-deploy checklist

- [ ] Backup OpenCart `catalog/` and `system/` (and DB if owner requires).
- [ ] FTP/SFTP access to **staging.lokkisona.com** OpenCart root.
- [ ] ERP `config/opencart.local.php` already points to staging (`api_base_url`, `api_key`, `api/ibs/products` route).
- [ ] Do **not** commit ERP v2.1.8.3.2 until UI screenshot shows distinct option images.

## Deploy steps (OpenCart staging)

1. Upload the two files above (or full `UPLOAD/`) preserving paths.
2. If OpenCart Modifications (OCMOD) are used: **Extensions → Modifications → Refresh**.
3. Clear theme/template cache if your staging stack uses it (admin or hosting panel).

No database migration. No CREATE/ALTER. Read-only SELECT only.

## Post-deploy — Step 1: API probe (ERP machine)

From ERP repo root:

```powershell
D:\xampp\php\php.exe tools\probe-staging-api.php
```

### Expected output

| Check | Expected |
|-------|----------|
| `version` | `1.8.3.2` |
| `option_image_probe` | Present (not “not present — deploy…”) |
| `option_image_probe.join_active` | `true` |
| `option_image_probe.sample_images_non_empty` | **> 0** |

Sample block should show non-empty paths for ST-A5 stroller value ids **971, 972, 1011, 1024** (Black, Olive Green, Khaki, Cream), e.g. under `catalog/PRODUCT/stroller-2026/...`.

### If probe fails

| Symptom | Action |
|---------|--------|
| `option_image_probe` missing | Files not deployed to correct paths, or wrong server. Re-upload `connection_test.php` + `product.php`. |
| `join_active: false` | POIP / option image tables not detected on staging DB. Confirm POIP extension installed and option images exist in OC admin for product **9759**. |
| `sample_images_non_empty: 0` | Join works but POIP rows empty for sample ids. Add/fix option images in OpenCart admin for that product, then re-probe. |

Manual URL (replace `YOUR_KEY`):

```
https://www.staging.lokkisona.com/index.php?route=api/ibs/connection_test&api_token=YOUR_KEY
```

## Post-deploy — Step 2: ERP product refresh

1. Open local ERP → **Product Control**.
2. Run **Refresh Products** (warehouse pull from staging API).
3. Wait for success message (products/variants updated).

This re-imports `option_image_path` into `ibs_product_variants` from the API.

## Post-deploy — Step 3: ERP DB + read-path verify

```powershell
D:\xampp\php\php.exe tools\verify-poip-option-images.php 9759
```

### Expected

- Exit code **0** with `PASS: Option images present for workspace display.`
- Local product: **product_id 69** (source **9759**, model **St-A5**)
- Four variants with **non-empty** `option_image_path`
- Workspace read shows **4 distinct** `option_image_url` values:

| Option | Expected image |
|--------|----------------|
| Black | Black stroller image |
| Olive Green | Olive stroller image |
| Khaki | Khaki stroller image |
| Cream | Cream stroller image |

## Post-deploy — Step 4: UI screenshot (commit gate)

1. Start ERP dev server with router (static assets):

   ```powershell
   D:\xampp\php\php.exe -S 127.0.0.1:8017 -t public public/router.php
   ```

2. Open **Product Control** → local product for source **9759** (often `product_id` **153** or **69** after re-sync) → modal.
3. Confirm **Option Rows** show **four different** thumbnails (not the same parent khaki image).
4. Save screenshot e.g. `storage/screenshots/v2.1.8.3.2/poip-option-images-product-153.png`.

**Commit gate:** Commit ERP v2.1.8.3.2 only after this screenshot shows distinct option images.

## Quick reference — product mapping

| System | ID | Notes |
|--------|-----|--------|
| OpenCart `source_product_id` | 9759 | St-A5 stroller |
| ERP `ibs_products.product_id` | 153 or 69 | Local snapshot (varies after re-sync; match by `source_product_id` 9759) |
| Option value ids (POV) | 971, 972, 1011, 1024 | Black, Olive Green, Khaki, Cream |

## Optional: ERP DB enrichment (no OpenCart file deploy)

If you cannot FTP immediately but have read-only MySQL access to the OpenCart database, set in `config/opencart.local.php`:

```php
'option_image_db_enrichment' => true,
'db' => [
    'host' => '...',
    'database' => '...',
    'username' => '...',
    'password' => '...',
    'prefix' => 'oc_',
],
```

Then **Refresh Products** or re-open the workspace. Prefer the API deploy above for production parity.

## Related docs

- `README-INSTALL.md` — full package install
- `TEST-PLAN.md` — connection test + option image probe criteria
- `SAFETY-CHECKLIST.md` — read-only guarantees
