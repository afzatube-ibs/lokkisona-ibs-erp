# Manual Migrations

Migrations in this directory are manual owner-action files. The application must not run these files automatically during page load.

## Safety Rules

- Review every migration before running it.
- Back up the target database before applying changes.
- Run migrations manually from a trusted SQL client or controlled deployment process.
- Do not add page-load `CREATE TABLE`, `ALTER TABLE`, or schema repair code.
- Do not connect to OpenCart or depend on OpenCart admin tables.
- Do not connect to WooCommerce or import/sync orders from migration work.
- Future runner apply must be owner/admin controlled and must follow dry-run/check-first output first.
- Future production apply must show a backup reminder and require extra confirmation.
- Failed future runs must show a clear Red Issues Summary.

## v0.1.20 Migration Runner Planning

The `/migration-runner` page documents the future controlled runner only. It does not execute SQL, write migration records, or create migration tracking tables.

Planned future runner responsibilities:

1. List pending migration files and groups.
2. Verify file path and checksum details.
3. Show dry-run/check-first results before any apply action.
4. Require owner/admin confirmation before future apply.
5. Log actor, environment, timing, result, and Red Issues Summary later.
6. Keep rollback plans documented and separately approved.

## Future Process

1. Create a numbered migration file in this directory.
2. Review the SQL with the owner/admin.
3. Back up the database.
4. Apply the SQL manually.
5. Record the applied migration in release notes or a future migrations table created by an explicit migration.
