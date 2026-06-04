# Database

Place SQL migrations and schema files here for IBS-LK Business Manager.

Configure connection settings in `config/database.php`.

Manual migration notes live in `database/migrations/`.

Database changes must be reviewed and applied by an owner/admin action outside page load. The application must not automatically create, alter, or repair schema while serving ERP pages.

v0.1.20 adds Migration Runner planning only. The `/migration-runner` page documents the future controlled runner, dry-run/check-first workflow, backup reminder, owner/admin confirmation, audit logging, rollback planning, production safety, and Red Issues Summary behavior. It does not execute migration SQL, write migration records, or create migration tables.
