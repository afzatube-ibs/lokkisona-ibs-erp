# Local Checkpoint Runner

Run the local checkpoint after every build or foundation change:

```powershell
powershell -ExecutionPolicy Bypass -File tools/check-local.ps1
```

The checkpoint:

- Auto-detects PHP, preferring `E:\xampp\php\php.exe`.
- Lints PHP files in `app`, `config`, `public`, `resources`, and `routes`.
- Starts a temporary PHP server on `127.0.0.1:8020` when needed.
- Smoke tests `/login`, `/dashboard`, `/activity-log`, `/roles-permissions`, `/database-safety`, `/health`, and `/version`.
- Confirms `/version` contains the current release version.
- Checks for forbidden legacy branding/runtime text.
- Checks runtime code for unsafe schema changes.
- Prints `git status --short` without committing or pushing.
