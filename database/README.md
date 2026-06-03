# Database

Place SQL migrations and schema files here for IBS-LK Business Manager.

Configure connection settings in `config/database.php`.

Manual migration notes live in `database/migrations/`.

Database changes must be reviewed and applied by an owner/admin action outside page load. The application must not automatically create, alter, or repair schema while serving ERP pages.
