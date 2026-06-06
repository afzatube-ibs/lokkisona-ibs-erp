# IBS-LK Production Launch Guide (v0.6.0)

Complete [STAGING-QA-CHECKLIST.md](STAGING-QA-CHECKLIST.md) and obtain owner sign-off before following this guide.

## Pre-launch requirements

- Staging E2E chain passed: manual order → workflow → dispatch → payable → reports
- v0.5.7 sync tested with demo or live OpenCart (1–3 test imports)
- v0.5.8 invoice generate + print log verified on staging
- v0.5.9 settlement period prepared and closed on staging
- `tools/check-local.ps1` — **ALL GREEN** on release tag

## 1. Production database

1. Owner backup production database (empty or cutover-ready).
2. Apply migrations manually in order: `0002`, `0003`, `0004`, `0005`, `0006`, `0007`, `0008`, `0009`.
3. Confirm `/dev-db-activation` Groups A–F Ready (settlements use migration 0009).
4. Run opening balance approve only with owner present (`/supplier-opening-balances`).

## 2. Launch cutover lock

1. Complete supplier opening balances on production.
2. POST launch cutover lock on `/supplier-opening-balances` with owner confirmation.
3. Verify launch cutover shows **locked** in read inventory.

## 3. Configuration hardening

Edit `config/app.php` on production server (never commit secrets):

```php
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

Edit `config/opencart.php` when ready for live Lokkisona sync:

```php
'enabled' => true,
'demo_mode' => false,
'api_base_url' => 'https://lokkisona.com/...',
'api_key' => 'OWNER_PROVIDED_KEY',
```

## 4. Supplier access

- Distribute supplier login to **Iqbal & Brothers** only.
- Supplier role sees dashboard, order workflow, dispatch, returns — not owner payables/settlements/sync import.

## 5. CommerceOS pause

- Lift CommerceOS / legacy pause only after owner documents sign-off in team notes.
- First live imports: max 50 orders, Test Sync preview first, owner-approved import only.

## 6. Post-launch monitoring

| Check | Frequency |
|-------|-----------|
| `/health` | Daily |
| `/activity-log` sync/payable events | Daily first week |
| Payable running balance vs settlement closing | Each settlement period |
| Checkpoint on deploy machine | After each hotfix |

## Rollback

- Do not force-push `main`.
- Restore database backup and redeploy previous release tag if critical defect found.
- Disable `opencart.enabled` immediately if sync causes data issues.
