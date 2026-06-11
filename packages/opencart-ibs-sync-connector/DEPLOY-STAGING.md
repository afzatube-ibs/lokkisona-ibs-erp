# IBS Sync Connector — Staging Deploy / Bootstrap Repair (v1.0.0)

Use this when staging returns PHP errors on `api/ibs/connection_test`, especially:

```
Call to a member function authenticate() on null
catalog/controller/api/ibs/connection_test.php
```

## Root cause

OpenCart 3 `$this->load->library('ibs/api_auth')` stores the library as registry key **`api_auth`** (basename of route), not `ibs_api_auth`. Old controllers referencing `$this->ibs_api_auth` or `$this->ibs_api_response` get `null` and crash.

## Fix

Upload bootstrap-aware controllers and `bootstrap.php`. Controllers instantiate auth/response directly via `ibs_sync_api_services($this->registry)` — no loader magic properties.

## Exact FTP upload list

Upload from `packages/opencart-ibs-sync-connector/` to **OpenCart staging root** (same level as `catalog/`, `system/`, `index.php`).

| # | Repo path | Staging path |
|---|-----------|--------------|
| 1 | `system/library/ibs/bootstrap.php` | `system/library/ibs/bootstrap.php` |
| 2 | `system/library/ibs/option_image_schema.php` | `system/library/ibs/option_image_schema.php` |
| 3 | `system/library/ibs/api_auth.php` | `system/library/ibs/api_auth.php` |
| 4 | `system/library/ibs/api_response.php` | `system/library/ibs/api_response.php` |
| 5 | `system/library/ibs/api_settings.php` | `system/library/ibs/api_settings.php` |
| 6 | `system/library/ibs/connector_version.php` | `system/library/ibs/connector_version.php` |
| 7 | `catalog/controller/api/ibs/connection_test.php` | `catalog/controller/api/ibs/connection_test.php` |
| 8 | `catalog/controller/api/ibs/products.php` | `catalog/controller/api/ibs/products.php` |
| 9 | `catalog/controller/api/ibs/orders.php` | `catalog/controller/api/ibs/orders.php` |
| 10 | `catalog/controller/api/ibs/version.php` | `catalog/controller/api/ibs/version.php` |
| 11 | `catalog/model/api/ibs/product.php` | `catalog/model/api/ibs/product.php` |
| 12 | `catalog/model/api/ibs/order.php` | `catalog/model/api/ibs/order.php` |
| 13 | `catalog/model/api/ibs/connector.php` | `catalog/model/api/ibs/connector.php` |

Create `system/library/ibs/` on the server if missing.

### Alternative: installer zip

```powershell
powershell -ExecutionPolicy Bypass -File packages/opencart-ibs-sync-connector/build-release.ps1
```

Upload `dist/ibs-opencart-sync-connector-v1.0.0.ocmod.zip` via **Extensions → Installer**, then enable the module and set API token in admin.

## POIP option images (`oc_poip_option_image`)

Staging uses Product Option Image PRO tables (`oc_poip_option_image`, etc.). The connector introspects columns via `SHOW COLUMNS`, joins on `product_option_value_id` + `product_id` when present, and COALESCEs the `image` column.

**Pass after upload:**

- `option_image_probe.join_active: true`
- `option_image_probe.sample_images_non_empty > 0` for ids 971, 972, 1011, 1024
- `compatibility.poip_schema_probe.oc_poip_option_image` lists detected columns and join keys

## Verify

1. Browser:

   ```
   https://staging.lokkisona.com/index.php?route=api/ibs/connection_test&api_token=YOUR_TOKEN
   ```

   **Pass:** JSON with `success: true`, `connector_version: "1.0.0"`, `option_image_probe`, `compatibility`.

2. ERP probe (from repo root):

   ```powershell
   D:\xampp\php\php.exe tools\probe-staging-api.php
   ```

   **Pass:** `connector_version_ok=PASS`, `option_image_probe_present=PASS`.

## Controller pattern (reference)

```php
require_once DIR_SYSTEM . 'library/ibs/bootstrap.php';
list($apiAuth, $apiResponse) = ibs_sync_api_services($this->registry);

$authError = $apiAuth->authenticate();
if ($authError !== null) {
    $apiResponse->error($authError, 401);
    return;
}
$apiResponse->send([...]);
```

Do **not** use `$this->load->library('ibs/api_auth')`, `$this->ibs_api_auth`, or `$this->ibs_api_response`.
