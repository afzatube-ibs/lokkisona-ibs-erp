# IBS-LK Staging QA Checklist (v0.5.6+ Gate)

Complete this checklist on **staging only** before starting v0.5.7+ builds on production paths.

## Prerequisites

1. Owner commits and pushes v0.5.6 work to `origin/main`.
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
| 5 | `0008_supplier_opening_balances_launch_cutovers.sql` | Group F |
| 6 | `0004_status_mapping_sync_preview.sql` | Before v0.5.7 sync tests |
| 7 | `0007_invoices_printing_supplier_tools.sql` | Before v0.5.8 invoice tests |
| 8 | `0009_settlements_workflow.sql` | Before v0.5.9 settlement tests |

## Activation verification

- [ ] `/dev-db-activation` — Groups A–F show **Ready**
- [ ] `powershell -ExecutionPolicy Bypass -File tools/check-local.ps1` — **ALL GREEN**
- [ ] `/version` shows expected release label

## End-to-end browser test chain

| Step | Page / action | Expected |
|------|---------------|----------|
| 1 | `/product-control` | Create product + variant with cost |
| 2 | `/manual-orders` | Create manual order with confirmation note |
| 3 | `/order-workflow` | Advance order through fulfillment stages |
| 4 | `/dispatch-reports` | Create dispatch batch from shipped orders |
| 5 | `/supplier-payables` | Approve/post dispatch payable draft |
| 6 | `/return-receive` | Confirm return receive (if test order returned) |
| 7 | `/reports` | Run supplier ledger + settlement summary |
| 8 | `/activity-log` | Sync/import/payable events recorded |

## v0.5.7+ add-on tests (after code deploy)

| Step | Page | Expected |
|------|------|----------|
| A | `/status-mapping` | Create Lokkisona status mappings |
| B | `/sync-preview` | Run Test Sync (demo or live OC) |
| C | `/sync-preview` | Import 1–3 eligible preview rows |
| D | `/invoice-printing` | Generate + print packing slip |
| E | `/settlements` | Prepare → approve → close one period |

## Sign-off

- [ ] Owner sign-off recorded (date + initials in team notes)
- [ ] No `[FAIL] RED ISSUES SUMMARY` from checkpoint
- [ ] Ready to proceed to production cutover planning (v0.6.0)
