# Local setup with XAMPP

## Foundation scope
The foundation provides the schema, shared PDO connection, photo-folder setup command, and command-line setup check. Account screens, reports, uploads and claims are subsequent features. PHP 8 or later with pdo_mysql is required.

## Windows setup
1. Clone the repository to C:/xampp/htdocs/campus-lost-and-found.
2. Start Apache and MySQL in the XAMPP Control Panel.
3. Open http://localhost/phpmyadmin. On the server-level Import tab, import database/schema.sql. It creates campus_lost_found and three tables. Use a fresh database. Do not repeatedly import the initial schema into an existing installation: existing tables will cause an error, and it is not a migration script.
4. Copy config/config.example.php to config/config.php. Enter your local host, port, database user and password. The example root/blank password is a common local XAMPP default, not a deployment configuration.
5. Set storage_path in config/config.php to an absolute folder outside htdocs. The example is C:/xampp/campus-lost-found-storage. If XAMPP is installed elsewhere, adjust this setting. The setup command below creates its items subfolder. Store relative paths like items/example.jpg in the database.
6. Open XAMPP Shell and run:

```bat
cd C:\xampp\htdocs\campus-lost-and-found
php scripts/setup-storage.php
php scripts/check-setup.php
```

The storage command prints two PASS lines. The setup check then prints two PASS lines and a database server version. The setup check verifies connectivity, table access, and CLI storage permissions. Use the browser check below to verify Apache access.

XAMPP may bundle MariaDB under its MySQL service label. Record the server version from the check. This schema uses common MySQL/MariaDB syntax; confirm a strict MySQL-server requirement with the professor if applicable.

## Photo storage setup

After copying your local configuration, run `php scripts/setup-storage.php` from the project folder in XAMPP Shell. If PHP is not on your VS Code terminal's PATH, use:

```bat
C:/xampp/php/php.exe scripts/setup-storage.php
```

The command creates the configured storage folder and its `items` subfolder when missing. It preserves existing photos and configuration, writes a uniquely named temporary file to check CLI permissions, then removes that check file. You can run it again on an existing installation. It does not need a database connection or change any database records.

Use an absolute path appropriate for the computer running PHP. Windows paths such as `C:/xampp/campus-lost-found-storage` belong in Windows configuration; Linux/macOS need their own absolute path. Keep storage outside the website directory as described above. The CLI-only script returns 404 when requested through a browser.

After it passes, run `php scripts/check-setup.php` and open http://localhost/campus-lost-and-found/scripts/checklocal.php. The browser check confirms Apache can access the folder too; Apache may run with different permissions from your terminal. Actual photo uploads still need the report-feature acceptance checks.

## Troubleshooting
- Could not find driver: check that XAMPP PHP has pdo_mysql enabled.
- Connection refused: start the database service and check its configured port.
- Access denied: correct your local database username/password.
- Unknown database or missing table: verify the SQL import completed successfully.
- Missing storage/items folder: verify storage_path in config/config.php, then run php scripts/setup-storage.php.
- Storage permission error: allow the PHP CLI and Apache users to write to the configured folder, then rerun the CLI and browser checks.
- Storage path is a file: choose a folder path in config.php; the setup command will not overwrite an existing file.
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
Start new work on a separate branch from the latest main. Check the selected branch before committing, especially when using GitHub in the browser. Open a pull request when the feature is ready, then review and run the relevant local checks before merging.
