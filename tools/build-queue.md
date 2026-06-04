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

Migration-related build tasks require successful dry-run, owner approval, backup confirmation, and manual apply only. The Build Queue must never apply migration SQL automatically.

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
