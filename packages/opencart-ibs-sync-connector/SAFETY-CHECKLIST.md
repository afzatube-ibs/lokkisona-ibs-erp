# IBS Sync Connector — Safety Checklist (v1.0.0)

## Design guarantees

- [x] Catalog API controllers return JSON only
- [x] Models use **SELECT** queries only
- [x] No CREATE TABLE / ALTER TABLE in extension
- [x] No product, stock, customer, or order writes
- [x] No order status changes
- [x] Admin module writes **settings only** (`module_ibs_sync_connector_*`)
- [x] `install()` / `uninstall()` touch settings keys only
- [x] `api_token` required on all catalog routes
- [x] `limit` capped at 20 server-side
- [x] Products filtered `dispatch_location_product.from_warehouse = 1`
- [x] ERP re-validates `from_warehouse` on import

## Before install

- [ ] Owner backup of OpenCart files + DB
- [ ] HTTPS on store URL
- [ ] API token 48+ hex chars (Generate Token in admin)

## After install

- [ ] Invalid token → 401 JSON
- [ ] `connection_test` returns `connector_version: 1.0.0`
- [ ] `option_image_probe.sample_images_non_empty > 0` when POIP data exists
- [ ] Products endpoint excludes shop-only rows
- [ ] No INSERT/UPDATE/DELETE in MySQL log during API tests
- [ ] ERP Test Connection → Ready
- [ ] ERP Refresh Products imports option images
- [ ] Product Control modal shows distinct option thumbnails

## Uninstall

- [ ] Module uninstall removes `module_ibs_sync_connector_*` settings only
- [ ] Uploaded PHP files remain on disk until manually removed (standard OC behavior)
