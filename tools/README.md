# Local Checkpoint Runner

Run the local checkpoint after every build or foundation change:

```powershell
powershell -ExecutionPolicy Bypass -File tools/check-local.ps1
```

The checkpoint:

- Auto-detects PHP, checking `D:\xampp\php\php.exe` (Office PC), `E:\xampp\php\php.exe` (Home PC), `C:\xampp\php\php.exe`, then `php` from PATH.
- Lints PHP files in `app`, `config`, `public`, `resources`, and `routes`.
- Starts a temporary PHP server on `127.0.0.1:8020` when needed.
- Smoke tests all planned foundation routes.
- Confirms `/version` contains the current release version.
- Checks for forbidden legacy branding/runtime text.
- Checks runtime code for unsafe schema changes and allows draft schema statements only in `database/migrations/*.sql` or documentation.
- Prints `git status --short` without committing or pushing.
- Supports the Build Queue safety workflow by stopping on Red Issues Summary and leaving commit/push for manual owner approval.

## Final Footer

Every run ends with a compact footer so the result is easy to copy into ChatGPT.

Passing runs end with:

```text
[OK] ALL GREEN
Version: v0.1.23 Migration Dry Run Validator Planning Foundation
Checkpoint: passed
Browser/Routes: passed
Git: summary printed above
Red Issues: none
```

Failing runs keep the detailed error output above and end with:

```text
[FAIL] RED ISSUES SUMMARY
1. Issue:
   Area:
   File/Page:
   What to fix:
```
