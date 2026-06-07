# IBS Read API — Safety Checklist (v1.8.3)

## Package design

- [x] Controllers return JSON only
- [x] Models use **SELECT** queries only (no INSERT / runtime mutation SQL)
- [x] No CREATE TABLE or ALTER TABLE in package
- [x] No OpenCart order status change
- [x] No stock, customer, or product writes
- [x] No admin session required — shared secret `api_token` only
- [x] `limit` capped at 20 server-side
- [x] Products filtered with `dispatch_location_product.from_warehouse = 1`
- [x] ERP still re-filters `from_warehouse` as defence in depth

## Before staging upload

- [ ] Owner backup of OpenCart files + DB
- [ ] Token generated (32+ random characters)
- [ ] `system/config/ibs_api.php` created on server only (not in Git)
- [ ] HTTPS enforced on staging URL

## After staging upload

- [ ] Invalid token returns 401 JSON
- [ ] `limit=999` returns at most 20 rows
- [ ] Products endpoint excludes shop-only rows
- [ ] No unexpected write queries in MySQL general log during tests
- [ ] ERP Test Connection succeeds
- [ ] ERP product preview import preserves supplier fields on re-import

## ERP repo (this build)

- [ ] No changes to dispatch / return / payable workflow
- [ ] No changes to `OpenCartReadClient` business logic
- [ ] Docs + config examples only (routes point to `api/ibs/*`)
- [ ] `tools/check-local.ps1` — ALL GREEN
