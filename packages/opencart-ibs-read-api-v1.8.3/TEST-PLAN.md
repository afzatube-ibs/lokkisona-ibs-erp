# IBS Read API — Test Plan (v1.8.3)

Test on **staging.lokkisona.com** after FTP upload and `system/config/ibs_api.php` is configured.

Base URL: `https://www.staging.lokkisona.com/index.php`

Replace `YOUR_KEY` with the token from `system/config/ibs_api.php`.

## 1. Connection test

```
GET ?route=api/ibs/connection_test&api_token=YOUR_KEY
```

**Pass:** JSON with `success: true`, `read_only: true`, `bridge_available: true`.

**Option images (v1.8.3.2+):** Response should include `option_image_probe` with:

- `join_active: true`
- `detected_tables` listing POIP / option image tables
- `sample_images_non_empty` > 0 for ST-A5 stroller value ids (971, 972, 1011, 1024) when POIP data exists on the store

**Fail checks:**

- Missing token → `success: false`, error about `api_token`
- Wrong token → `Invalid api_token`

## 2. Products page 1

```
GET ?route=api/ibs/products&api_token=YOUR_KEY&page=1&limit=20
```

**Pass:**

- JSON only (not HTML login page)
- `bridge_available: true`
- Every product has `from_warehouse: 1`
- No shop-only products (`from_warehouse: 0`)
- Variable products include `options` array when options exist in OC
- `has_next` / `has_previous` present

## 3. Products pagination

If total warehouse products > 20:

```
GET ?route=api/ibs/products&api_token=YOUR_KEY&page=2&limit=20
```

**Pass:** Different product IDs than page 1; `has_previous: true`.

## 4. Limit cap

```
GET ?route=api/ibs/products&api_token=YOUR_KEY&page=1&limit=50
```

**Pass:** Response contains at most 20 products (server caps at 20).

## 5. Orders page 1

```
GET ?route=api/ibs/orders&api_token=YOUR_KEY&page=1&limit=20
```

**Pass:**

- `orders` array with `order_id`, customer fields, `order_status`, `products`
- Line items include `name`, `quantity`, `price`, optional `option`
- `courier_status` / `consignment_id` populated when columns exist on staging DB

## 6. Orders optional filters

```
GET ?route=api/ibs/orders&api_token=YOUR_KEY&page=1&limit=20&status_id=3
GET ?route=api/ibs/orders&api_token=YOUR_KEY&page=1&limit=20&date_from=2026-01-01&date_to=2026-06-07
```

**Pass:** JSON success; filtered subset (manual spot-check in OC admin).

## 7. ERP integration (after OC tests pass)

1. ERP **Sync/API Settings** — Staging URL, routes, API key saved
2. **Test Connection** — OK with bridge message
3. `/sync-preview` — Load product preview (bridge products only)
4. `/sync-preview` — Load order preview (ERP applies status mapping on import)

## Postman collection tips

- Save `api_token` as collection variable
- Set `Accept: application/json`
- Do not log production tokens in shared Postman workspaces
