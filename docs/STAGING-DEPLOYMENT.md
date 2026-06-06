# IBS-LK Staging Domain Deployment Guide (v0.5.0)

**Product sync from OpenCart staging:** see [STAGING-PRODUCT-SYNC.md](STAGING-PRODUCT-SYNC.md) (`staging.lokkisona.com` → ERP staging host).

## Requirements

- PHP 8.2+
- MySQL 5.7+ (dedicated **staging database** — never use production data for first QA)
- Apache with `mod_rewrite` **or** PHP built-in server for temporary testing
- Git clone synced to `origin/main`

## 1. Clone and configure

```bash
git clone https://github.com/afzatube-ibs/lokkisona-ibs-erp.git
cd lokkisona-ibs-erp
git pull --ff-only origin main
```

Edit `config/database.php` on the staging server with staging MySQL credentials. **Do not commit credentials.**

Set `config/app.php`:

```php
'env' => 'staging',
'staging_gate' => [
    'enabled' => true,
    'username' => 'your-staging-user',
    'password' => 'your-staging-password',
],
```

## 2. Web server

**Apache:** Point document root to `public/`. Enable `mod_rewrite`.

**PHP built-in (temporary):**

```bash
php -S 127.0.0.1:8010 -t public public/router.php
```

## 3. Database activation (manual only)

1. Owner backup staging database.
2. Apply migration drafts in order: `0002`, `0003`, `0005`, `0006`, `0008`. Add `0004` before sync tests, `0007` before invoice tests, `0009` before settlement tests, `0010` for quick invoice, `0011` before Product Control category/sync QA.
3. Follow [STAGING-QA-CHECKLIST.md](STAGING-QA-CHECKLIST.md) for full E2E sign-off.
4. Open `/dev-db-activation` and confirm Groups A–F show **Ready**.
5. Run `powershell -ExecutionPolicy Bypass -File tools/check-local.ps1` — expect `[OK] ALL GREEN`.

## 4. Browser test checklist

| Page | Purpose |
|------|---------|
| `/dashboard` | Owner and supplier dashboards |
| `/product-control` | Product / variant writes |
| `/manual-orders` | Manual order create |
| `/order-workflow` | Fulfillment actions |
| `/dispatch-reports` | Dispatch batch + payable draft |
| `/return-receive` | Return confirm |
| `/supplier-payables` | Ledger approve/post |
| `/reports` | Owner reports |
| `/version` | Release version |
| `/dev-db-activation` | Table readiness |

## 5. Login accounts

| Role | Default (change on staging) |
|------|----------------------------|
| Owner/Admin | `admin` / `admin` |
| Supplier | `supplier` / `supplier` |

## 6. Safety rules

- No automatic migration apply from pages or checkpoint.
- No OpenCart/WooCommerce live sync until v0.5.3+ module is owner-approved.
- Git syncs code only — MySQL data does not sync between PCs.
- Stop on `[FAIL] RED ISSUES SUMMARY` before any production promotion.

## 7. Go-live promotion

1. Complete staging QA with real supplier test orders.
2. Owner approves payable and return deduction workflows.
3. Copy staging DB backup plan to production.
4. Apply same migration order on production with owner present.
5. Enable staging gate on staging only; production uses proper auth hardening later.
