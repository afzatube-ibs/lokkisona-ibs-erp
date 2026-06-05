# Build Queue and Semi-Automation Planning

This is planning/foundation only. It documents a safe build queue workflow for IBS-LK Business Manager and does not create build queue tables, write build queue records, auto-run tasks, commit, or push.

## Safe Workflow

1. Read the next build task from the build queue.
2. Apply one build or one small safe batch.
3. Run `powershell -ExecutionPolicy Bypass -File tools/check-local.ps1`.
4. If `[OK] ALL GREEN`, show version, changed files, browser/route count, `Red Issues: none`, and the recommended next build.
5. If `[FAIL] RED ISSUES SUMMARY`, stop immediately and do not continue to the next task.
6. Wait for owner approval before commit or push.
7. Start the next build only after Git is synced with `origin/main`.

Migration-related build tasks require successful dry-run, manual approval gate completion, execution lock readiness, owner approval, backup confirmation, and manual apply only. The Build Queue must never apply migration SQL automatically or bypass the execution lock.

v0.2.2 completes the read-only database service/repository foundation (SELECT-only repositories and read services for suppliers, business sources, products, product variants, supplier opening balances, and launch cutovers).

v0.2.3 wires the first module read inventory into `/suppliers` and `/business-sources` with hybrid planning plus live SELECT-only display and graceful empty states when tables are not applied yet.

v0.2.4 extends the same read-only pattern to `/product-control` with Product and Product Variant read inventory cards.

v0.2.5 extends the same read-only pattern to `/supplier-opening-balances` with Supplier Opening Balance and Launch Cutover read inventory cards.

v0.2.6 extends the same read-only pattern to `/order-workflow` with Order, Order Item, and Order Workflow History read inventory cards plus new SELECT-only repositories and read services.

The next major phase should move toward owner-approved write service layer work after manual migration apply. Order workflow actions, opening balance write services, and launch lock actions remain future owner-approved builds.

## Semi-Automation Levels

- Level 1: Manual task prompt plus manual checkpoint plus manual commit/push.
- Level 2: Build queue suggests the next task, checkpoint footer is shown, commit/push stay manual.
- Level 3: Small safe batch of 2-3 related planning pages, checkpoint, then manual owner review.

## Blocked Automation

- Automatic commit
- Automatic push
- Automatic database migration apply
- Automatic OpenCart/WooCommerce sync
- Automatic order import
- Automatic payable mutation
- Automatic stock deduction
- Automatic invoice generation

## PHP Path Notes

- Home PC: `E:\xampp\php\php.exe`
- Office PC: `D:\xampp\php\php.exe`
- The local checkpoint also tries `C:\xampp\php\php.exe` and `php` from PATH.

## Planned Fields Only

Build queue, build run, and red issue fields are documented on `/build-queue` only. No database tables or records are created automatically.
