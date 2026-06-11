# IBS OpenCart Sync Connector v1.0.0

Installable OpenCart extension for **sync sites** (staging/live). Provides read-only JSON API for IBS ERP:

- Connection test + diagnostics
- Warehouse products (`from_warehouse = 1`)
- Product variants + **POIP / Improved Options** option images
- Orders + status + courier fields when columns exist
- Version endpoint

**No OpenCart writes.** No CREATE/ALTER on product/order tables. Settings stored in OpenCart `setting` table only.

## Build release zip (ERP repo)

```powershell
powershell -ExecutionPolicy Bypass -File packages/opencart-ibs-sync-connector/build-release.ps1
```

Output: `dist/ibs-opencart-sync-connector-v1.0.0.ocmod.zip`

## Install on OpenCart (admin — no manual FTP)

1. **Backup** OpenCart files and database.
2. OpenCart Admin → **Extensions → Installer**.
3. Upload `dist/ibs-opencart-sync-connector-v1.0.0.ocmod.zip`.
4. **Extensions → Modifications → Refresh** (if Modifications are used).
5. **Extensions → Extensions** → choose **Modules** from the extension type dropdown (top-left).
6. Find **IBS Sync Connector** → **Install** (green +).

### Module not in list?

- Confirm files exist on server (Linux paths must use `/`, not `\`):
  - `admin/controller/extension/module/ibs_sync_connector.php`
  - `admin/language/en-gb/extension/module/ibs_sync_connector.php`
  - `admin/view/template/extension/module/ibs_sync_connector.twig`
- Rebuild zip with forward-slash paths: `build-release.ps1`
- **System → Users → User Groups → Administrator** → enable Access + Modify for `extension/module/ibs_sync_connector`
- Clear theme/cache if admin menu is stale
6. Click **Edit** (blue pencil):
   - Set **Status** = Enabled
   - Copy **API Token** (or click **Generate Token**)
   - Bridge table = `dispatch_location_product` (default)
   - **Save**
7. Open **Connection test** URL from the settings page in browser — expect JSON with `connector_version: 1.0.0` and `option_image_probe`.

## ERP configuration

**System → Sync/API Settings** (or `config/opencart.local.php`):

| Setting | Value |
|---------|-------|
| Source URL | `https://www.staging.lokkisona.com` |
| API key | Same as module API token |
| Product API route | `api/ibs/products` |
| Order API route | `api/ibs/orders` |
| Connection test route | `api/ibs/connection_test` |

Then ERP **Test Connection** → Product Control **Refresh Products**.

## API routes (catalog)

| Route | Purpose |
|-------|---------|
| `api/ibs/connection_test` | Auth, bridge, compatibility, option_image_probe, counts |
| `api/ibs/version` | connector_version + compatibility |
| `api/ibs/products` | Warehouse products + options (POIP images) |
| `api/ibs/orders` | Read-only orders |

Example:

```
https://YOUR-STORE/index.php?route=api/ibs/connection_test&api_token=YOUR_TOKEN
```

## Bootstrap pattern (OpenCart 3)

OpenCart 3 `$this->load->library('ibs/api_auth')` registers the instance as registry key **`api_auth`** (basename only), **not** `ibs_api_auth`. Controllers that call `$this->ibs_api_auth->authenticate()` crash with *Call to a member function authenticate() on null*.

All catalog API controllers use `system/library/ibs/bootstrap.php` instead:

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

## Package file tree

```
upload/
  admin/controller/extension/module/ibs_sync_connector.php
  admin/language/en-gb/extension/module/ibs_sync_connector.php
  admin/view/template/extension/module/ibs_sync_connector.twig
  catalog/controller/api/ibs/connection_test.php
  catalog/controller/api/ibs/version.php
  catalog/controller/api/ibs/products.php
  catalog/controller/api/ibs/orders.php
  catalog/model/api/ibs/connector.php
  catalog/model/api/ibs/product.php
  catalog/model/api/ibs/order.php
  system/library/ibs/bootstrap.php
  system/library/ibs/option_image_schema.php
  system/library/ibs/connector_version.php
  system/library/ibs/api_settings.php
  system/library/ibs/api_auth.php
  system/library/ibs/api_response.php
```

## Manual FTP upload (staging repair)

Upload from `packages/opencart-ibs-sync-connector/` to OpenCart web root (same level as `catalog/`, `system/`). Required for bootstrap repair and connection test JSON:

| Upload to (staging) |
|---------------------|
| `system/library/ibs/bootstrap.php` |
| `system/library/ibs/option_image_schema.php` |
| `system/library/ibs/api_auth.php` |
| `system/library/ibs/api_response.php` |
| `system/library/ibs/api_settings.php` |
| `system/library/ibs/connector_version.php` |
| `catalog/controller/api/ibs/connection_test.php` |
| `catalog/controller/api/ibs/products.php` |
| `catalog/controller/api/ibs/orders.php` |
| `catalog/controller/api/ibs/version.php` |
| `catalog/model/api/ibs/product.php` |
| `catalog/model/api/ibs/order.php` |
| `catalog/model/api/ibs/connector.php` |

**Pass:** `https://YOUR-STORE/index.php?route=api/ibs/connection_test&api_token=TOKEN` returns JSON with `connector_version: "1.0.0"`.

See `DEPLOY-STAGING.md` for full repair checklist.

## Legacy FTP package

Superseded by this installer zip. The old read-api manual package (`packages/opencart-ibs-read-api-v1.8.3/`) used `$this->ibs_api_auth` and is **not** compatible with OpenCart 3 loader keys — use this sync connector instead.

## Safety

See `SAFETY-CHECKLIST.md`. See `TEST-PLAN.md` for acceptance tests.
