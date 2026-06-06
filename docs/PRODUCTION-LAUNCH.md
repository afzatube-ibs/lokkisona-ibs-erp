# IBS-LK Production Launch Guide (v1.0.0)

Complete [STAGING-QA-CHECKLIST.md](STAGING-QA-CHECKLIST.md) and obtain **owner sign-off** before following this guide.

This is the controlled production cutover for **IBS-LK Business Manager v1.0.0** — supplier fulfillment for Iqbal & Brothers (Lokkisona / Sonamoni). Git tag: `1.0.0` / commit with `config/app.php` version `1.0.0`.

## Pre-launch requirements

- [ ] Staging E2E chain passed (see checklist below)
- [ ] v0.5.7+ Test Sync tested with demo or live OpenCart (1–3 test imports on staging)
- [ ] v0.5.8 invoice generate + print log verified on staging
- [ ] v0.5.9 settlement period prepared and closed on staging
- [ ] v0.7.x return batch → payable deduction draft tested on staging (if returns scenario)
- [ ] `tools/check-local.ps1` — **ALL GREEN** on release tag
- [ ] Owner documents sign-off (date + initials)

## Staging E2E chain (must pass before production)

| Step | Page / action | Expected |
|------|---------------|----------|
| 1 | `/product-control` | Create product + variant with cost |
| 2 | `/manual-orders` or `/order-workflow` modal | Create manual order |
| 3 | `/order-workflow` | Advance to Shipped (Receive → Packaging → Shipped) |
| 4 | `/dispatch-reports` | Create dispatch batch; cost snapshot locked |
| 5 | `/supplier-payables` | Post + approve dispatch payable draft |
| 6 | `/return-receive` | Confirm return (if test scenario) |
| 7 | `/return-receive` | Create return batch → Owner Approve |
| 8 | `/supplier-payables` | Create return deduction draft → approve/post |
| 9 | `/settlements` | Prepare → approve → mark paid → close |
| 10 | `/reports` | Supplier ledger + settlement summary |
| 11 | `/activity-log` | Workflow + payable events recorded |
| 12 | `/status-mapping` + `/sync-preview` | Test Sync + import 1–3 rows (staging) |
| 13 | `/invoice-printing` | Generate + print packing slip |

## 1. Production database

1. Owner **backup** production database (empty or cutover-ready).
2. Apply migrations **manually** in SQL client — never via page load:

| Order | File | Group |
|-------|------|-------|
| 1 | `0002_core_users_roles_activity.sql` | A |
| 2 | `0003_business_sources_suppliers_products.sql` | B |
| 3 | `0005_orders_manual_orders_workflow.sql` | C |
| 4 | `0006_dispatch_returns_payables.sql` | D |
| 5 | `0008_supplier_opening_balances_launch.sql` | F |
| 6 | `0004_status_mapping_sync_preview.sql` | Sync |
| 7 | `0007_invoices_printing_supplier_tools.sql` | Invoice |
| 8 | `0009_settlements_workflow.sql` | Settlements |
| 9 | `0010_supplier_quick_invoice_totals.sql` | Quick invoice |

3. Confirm `/dev-db-activation` — Groups A–F **Ready**.
4. Run opening balance approve **only with owner present** (`/supplier-opening-balances`).

## 2. Launch cutover lock

1. Complete supplier opening balances on production.
2. POST launch cutover lock on `/supplier-opening-balances` with owner confirmation.
3. Verify launch cutover shows **locked** in read inventory.

## 3. Configuration hardening (on server only — never commit secrets)

Edit `config/app.php` on the production server:

```php
'version' => '1.0.0',
'release_label' => 'Production Launch',
'env' => 'production',
'staging_gate' => [
    'enabled' => false,
    'username' => '',
    'password' => '',
],
'auth' => [
    'username' => 'REPLACE_OWNER_USER',
    'password' => 'REPLACE_STRONG_PASSWORD',
    'supplier_username' => 'REPLACE_SUPPLIER_USER',
    'supplier_password' => 'REPLACE_STRONG_PASSWORD',
],
```

Edit `config/database.php` with production MySQL credentials.

When ready for **controlled** Lokkisona OpenCart sync (owner-approved only):

```php
// config/opencart.php
'enabled' => true,
'demo_mode' => false,
'api_base_url' => 'https://lokkisona.com/...',
'api_key' => 'OWNER_PROVIDED_KEY',
```

**First live imports:** Test Sync preview → owner confirms → import **max 50**, **1–3 orders first**. No Full Sync. No background loops.

## 4. Supplier access

- Distribute supplier login to **Iqbal & Brothers** only.
- Supplier role: dashboard, order workflow, return receive, supplier tools — **not** payables, settlements, sync, or migrations.
- Login page hides dev credentials when `env` is not `local`.

## 5. CommerceOS pause

- Lift CommerceOS / legacy pause only after owner documents sign-off in team notes.
- IBS-LK ERP is the system of record for supplier-handled fulfillment after cutover.

## 6. Post-launch monitoring

| Check | Frequency |
|-------|-----------|
| `/health` | Daily |
| `/activity-log` sync/payable events | Daily first week |
| Payable running balance vs settlement closing | Each settlement period |
| `tools/check-local.ps1` on deploy machine | After each hotfix |

## Rollback

- Do **not** force-push `main`.
- Restore database backup and redeploy previous release tag if critical defect found.
- Set `opencart.enabled` to `false` immediately if sync causes data issues.

## Still blocked after v1.0.0 (separate owner-approved builds)

- Stock deduction on fulfill/pack/ship
- Returned-stock restore for owner warehouse returns
- WooCommerce / Sonamoni auto-sync
- Full Sync in normal UI
- Auto migration apply
- Page-load CREATE TABLE / ALTER TABLE

See `docs/V1.0.0-PRODUCTION-LAUNCH-HANDOFF.md` for the release file list and owner checklist.
