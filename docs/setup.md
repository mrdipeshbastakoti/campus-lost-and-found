# Local setup with XAMPP

## Foundation scope
This package creates the schema, shared PDO connection and a command-line setup check. Account screens, reports, uploads and claims are subsequent features. PHP 8 or later with pdo_mysql is required.

## Windows setup
1. Clone the repository to C:/xampp/htdocs/campus-lost-and-found.
2. Start Apache and MySQL in the XAMPP Control Panel.
3. Open http://localhost/phpmyadmin. On the server-level Import tab, import database/schema.sql. It creates campus_lost_found and three tables. Use a fresh database. Do not repeatedly import the initial schema into an existing installation: existing tables will cause an error, and it is not a migration script.
4. Copy config/config.example.php to config/config.php. Enter your local host, port, database user and password. The example root/blank password is a common local XAMPP default, not a deployment configuration.
5. Create C:/xampp/campus-lost-found-storage/items outside htdocs. If XAMPP is installed elsewhere, change storage_path in config.php accordingly. Store relative paths like items/example.jpg in the database.
6. Open XAMPP Shell and run:

```bat
cd C:\xampp\htdocs\campus-lost-and-found
php scripts/check-setup.php
```

Expected results are two PASS lines and a database server version. The command checks connectivity, table access, and CLI storage permissions. It does not yet test the browser or upload feature.

XAMPP may bundle MariaDB under its MySQL service label. Record the server version from the check. This schema uses common MySQL/MariaDB syntax; confirm a strict MySQL-server requirement with the professor if applicable.

## Troubleshooting
- Could not find driver: check that XAMPP PHP has pdo_mysql enabled.
- Connection refused: start the database service and check its configured port.
- Access denied: correct your local database username/password.
- Unknown database or missing table: verify the SQL import completed successfully.
- Storage error: create the items directory at the configured absolute path.
- php command not found: use XAMPP Shell or C:/xampp/php/php.exe.

## Team conventions
All services use Database::connect() by requiring src/Database.php with a path based on __DIR__. Use prepared statements for user input. Keep the established table/column names. Configuration and uploads belong to each teammate's machine and are ignored by Git.

Approval and claim submission must later lock the report row and update related data in a transaction. Foreign keys in this foundation do not enforce permissions, valid transitions, or one approved claim per report.

Photo validation and serving are future report-feature work. Use JPG/PNG, 5 MB maximum, unique filenames and cleanup on failure. Configure PHP upload_max_filesize to at least 5M and post_max_size above that (for example 8M) before testing uploads, and restart Apache after php.ini changes.

## Review checks
- Run the setup command with correct settings and record the result.
- Temporarily set an incorrect database name, confirm a FAIL result, then restore it.
- Confirm config/config.php is ignored with git check-ignore config/config.php.
- Ask another teammate to repeat the setup independently.

## Branch and review
Foundation branch: setup/project-foundation.
Proposed commit: Add XAMPP database foundation and setup guide.
A teammate reviews the foundation pull request. Merge after local setup checks pass, then Sajan starts feature/accounts from updated main.
