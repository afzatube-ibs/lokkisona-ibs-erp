# IBS Sync Connector — Test Plan (v1.0.0)

## 1. Install from admin

1. Upload `dist/ibs-opencart-sync-connector-v1.0.0.ocmod.zip` via **Extensions → Installer**.
2. Refresh modifications if prompted.
3. **Extensions → Modules → IBS Sync Connector → Install → Edit → Enable → Save**.

**Pass:** Settings page shows API token and endpoint URLs.

## 2. Connection test

```
GET /index.php?route=api/ibs/connection_test&api_token=TOKEN
```

**Prerequisite:** `system/library/ibs/bootstrap.php` deployed; controllers use `ibs_sync_api_services()` (not `$this->ibs_api_auth`). See `DEPLOY-STAGING.md` if authenticate-on-null crash occurs.

**Pass:**

- `success: true`, `read_only: true`
- `connector_version: "1.0.0"`
- `compatibility` with `opencart_version`, `poip_detected`, `improved_options_detected`
- `option_image_probe.join_active: true`
- `option_image_probe.sample_images_non_empty > 0` (when POIP images exist)
- `product_count_probe.warehouse_product_count >= 0`
- `order_count_probe.order_count >= 0`

## 3. Version endpoint

```
GET /index.php?route=api/ibs/version&api_token=TOKEN
```

**Pass:** `connector_version: 1.0.0`

## 4. Products + POIP images

```
GET /index.php?route=api/ibs/products&api_token=TOKEN&page=1&limit=20
```

**Pass:**

- Variable product (e.g. source id 9759) has `options[]` with non-empty `image` per color variant
- All products have `from_warehouse: 1`

## 5. Orders

```
GET /index.php?route=api/ibs/orders&api_token=TOKEN&page=1&limit=20
```

**Pass:** JSON orders array; courier fields populated when columns exist on `oc_order`.

## 6. ERP integration

```powershell
D:\xampp\php\php.exe tools\probe-staging-api.php
D:\xampp\php\php.exe tools\verify-poip-option-images.php 9759
```

**Pass:**

- Probe: `version_1.0.0=PASS`, `option_image_probe_present=PASS`, `sample_images_non_empty=PASS`
- Verify: `PASS`, `distinct_option_image_urls=4`

## 7. UI acceptance

Open Product Control → product with source 9759 → Option Rows show **four different** thumbnails.

**Pass:** Screenshot saved; ERP v2.1.8.3.2 commit gate cleared.
