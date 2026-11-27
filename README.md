# Campus Lost-and-Found System

A Software Engineering course project for reporting lost and found belongings at Dong-Eui University.

## Features available on main

- Register, log in, and log out.
- Post lost or found reports with an optional JPG or PNG photo, up to 5 MB.
- Search by item name and filter by report type and status.
- Open public item details and view photos.
- View your own reports and mark an open lost report resolved after recovery.

Private ownership claims, finder decisions, and found-item returns are being developed on `feature/claims-and-returns`.

## Technologies

PHP 8 or later, HTML, CSS, JavaScript, and a MySQL-compatible database using InnoDB. The local development environment is XAMPP, which may provide MariaDB under the MySQL service name. PHP needs the `pdo_mysql` and `fileinfo` extensions.

## Run locally with Windows XAMPP

1. Clone or download the repository into `C:/xampp/htdocs/campus-lost-and-found`. The project files, including `index.php`, should be directly inside that folder.
2. Start Apache and MySQL in the XAMPP Control Panel.
3. Copy `config/config.example.php` to `config/config.php` and enter your local database settings.
4. For a new installation, open [phpMyAdmin](http://localhost/phpmyadmin/) and import [database/schema.sql](database/schema.sql). Import the initial schema once into a fresh installation; keep your existing database when updating the project.
5. Set `storage_path` to `C:/xampp/campus-lost-found-storage` and create its `items` subfolder: `C:/xampp/campus-lost-found-storage/items`. This folder is outside `htdocs`. Adjust the path if XAMPP is installed elsewhere.
6. To support the full 5 MB photo allowance, check Apache's PHP settings through XAMPP Control Panel > Apache > Config > PHP (php.ini). Enable `file_uploads` and `fileinfo`, set `upload_max_filesize` to at least `5M` and `post_max_size` above that, such as `8M`, then restart Apache if you changed the settings.
7. Open the [browser setup check](http://localhost/campus-lost-and-found/scripts/checklocal.php). Confirm its Apache, database, and photo-storage checks pass.
8. Open [Campus Lost & Found](http://localhost/campus-lost-and-found/), register a test account, and try posting and searching for a report.

From a VS Code terminal opened in the project folder, you can also run the CLI setup check:

```bat
C:/xampp/php/php.exe scripts/check-setup.php
```

The browser check verifies Apache's access to the photo folder. CLI permissions can differ from Apache permissions.

Each teammate uses their own local database, configuration, and photos. Keep `config/config.php` and personal uploads out of Git.

## Incremental development

| Increment | Functionality | Status |
|---|---|---|
| 1 | Accounts: registration, login, logout | Merged into main |
| 2 | Reports, photos, search, details, and lost-item recovery | Merged into main |
| 3 | Private claims, approval/rejection, and confirmed returns | In progress |

Each increment adds working functionality and includes relevant review and testing before integration.

## Documentation

- [Local setup and troubleshooting](docs/setup.md)
- [Requirements and acceptance checks](docs/requirements.md)
- [Architecture and UML design](docs/design.md)
- [Accounts](docs/accounts.md)
- [Search and its verification command](docs/search.md)
- [Reports, photos, and local acceptance checks](docs/reports.md)

## Team members

| Name | Student ID |
|---|---|
| Dipesh Bastakoti | 20244136 |
| Sajan Dahal | 20244210 |
| Shiva Khadka | 20244092 |
| Surya KC | 20244125 |
