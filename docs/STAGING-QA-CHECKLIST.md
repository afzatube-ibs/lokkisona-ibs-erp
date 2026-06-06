# IBS-LK Staging QA Checklist (v1.0.0 Gate)

Complete this checklist on **staging only** before production launch per [PRODUCTION-LAUNCH.md](PRODUCTION-LAUNCH.md).

## Prerequisites

1. Owner commits and pushes v1.0.0 work to `origin/main`.
2. Deploy staging per [STAGING-DEPLOYMENT.md](STAGING-DEPLOYMENT.md).
3. Enable `staging_gate` in `config/app.php` on the staging server.
4. Owner backup of staging database before any SQL apply.

## Migration apply order (staging DB only)

Apply manually in SQL client — never via page load:

| Order | File | Required for |
|-------|------|--------------|
| 1 | `0002_core_users_roles_activity.sql` | Group A |
| 2 | `0003_business_sources_suppliers_products.sql` | Group B |
| 3 | `0005_orders_manual_orders_workflow.sql` | Group C |
| 4 | `0006_dispatch_returns_payables.sql` | Group D |
| 5 | `0008_supplier_opening_balances_launch.sql` | Group F |
| 6 | `0004_status_mapping_sync_preview.sql` | Before sync tests |
| 7 | `0007_invoices_printing_supplier_tools.sql` | Before invoice tests |
| 8 | `0009_settlements_workflow.sql` | Before settlement tests |
| 9 | `0010_supplier_quick_invoice_totals.sql` | Quick invoice totals |

## Activation verification

- [ ] `/dev-db-activation` — Groups A–F show **Ready**
- [ ] `powershell -ExecutionPolicy Bypass -File tools/check-local.ps1` — **ALL GREEN**
- [ ] `/version` shows **v1.0.0 — Production Launch**

## End-to-end browser test chain

| Step | Page / action | Expected |
|------|---------------|----------|
| 1 | `/product-control` | Create product + variant with cost |
| 2 | `/manual-orders` or `/order-workflow` modal | Create manual order with confirmation |
| 3 | `/order-workflow` | Advance order through fulfillment stages to Shipped |
| 4 | `/dispatch-reports` | Create dispatch batch from shipped orders |
| 5 | `/supplier-payables` | Approve/post dispatch payable draft |
| 6 | `/return-receive` | Confirm return receive (if test order returned) |
| 7 | `/return-receive` | Create return batch → Owner Approve |
| 8 | `/supplier-payables` | Create return deduction draft → approve/post |
| 9 | `/reports` | Run supplier ledger + settlement summary |
| 10 | `/activity-log` | Sync/import/payable events recorded |

## v1.0.0 add-on tests

| Step | Page | Expected |
|------|------|----------|
| A | `/status-mapping` | Create Lokkisona status mappings |
| B | `/sync-preview` | Run Test Sync (demo or live OC) |
| C | `/sync-preview` | Import 1–3 eligible preview rows |
| D | `/invoice-printing` | Generate + print packing slip |
| E | `/settlements` | Prepare → approve → mark paid → close period |

## Sign-off

- [ ] Owner sign-off recorded (date + initials in team notes)
- [ ] No `[FAIL] RED ISSUES SUMMARY` from checkpoint
- [ ] Ready to execute [PRODUCTION-LAUNCH.md](PRODUCTION-LAUNCH.md) on production
