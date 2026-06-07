# IBS OpenCart Read-Only API — Install (v1.8.3)

Read-only JSON connector for **staging.lokkisona.com** (OpenCart 3.0.4.1).  
No OpenCart writes. No CREATE/ALTER. API key required. Max 20 rows per request.

## Routes

| Route | Purpose |
|-------|---------|
| `api/ibs/connection_test` | Auth + bridge probe |
| `api/ibs/products` | Dispatch Location products (`from_warehouse = 1`) + options |
| `api/ibs/orders` | Orders + line items + courier/consignment when columns exist |

Example:

```
https://www.staging.lokkisona.com/index.php?route=api/ibs/connection_test&api_token=YOUR_KEY
```

## Install on OpenCart staging

1. **Backup** OpenCart files and database (owner).
2. Upload `UPLOAD/` contents to OpenCart root preserving paths:
   - `catalog/controller/api/ibs/*.php`
   - `catalog/model/api/ibs/*.php`
   - `system/library/ibs/*.php`
3. Copy `config/ibs_api.example.php` → `system/config/ibs_api.php` on the server.
4. Set a long random `api_token` in `system/config/ibs_api.php` (never commit to Git).
5. Confirm `bridge_table` is `dispatch_location_product` (Dispatch Location extension).
6. Clear OpenCart modification cache if used: **Extensions → Modifications → Refresh**.
7. Test with Postman (see `TEST-PLAN.md`).

## ERP configuration (after OpenCart deploy)

In **System → Sync/API Settings** (or `config/opencart.local.php`):

| Setting | Value |
|---------|-------|
| Source Mode | Staging |
| Source URL | `https://www.staging.lokkisona.com` |
| API key | Same token as `system/config/ibs_api.php` |
| Product API route | `api/ibs/products` |
| Order API route | `api/ibs/orders` |

Then **Test Connection** → `/sync-preview` product/order preview.

## Safety

See `SAFETY-CHECKLIST.md`. Package uses **SELECT-only** SQL. No admin session required.

## Version

Package version: **1.8.3** — OpenCart Read-Only API Foundation
