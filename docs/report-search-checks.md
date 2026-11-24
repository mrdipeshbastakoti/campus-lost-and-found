# Report and search integration checks

Run `scripts/check-reports.php` after the item-report service is available. It checks that reports created and resolved through `ReportService` are returned correctly by `ReportSearch`.

## Run locally

Use the existing `config/config.php`, database, and external photo storage folder. The `users` and `item_reports` tables must use InnoDB. No schema import is needed.

From the project folder in XAMPP Shell:

```bash
php scripts/check-reports.php
```

From the VS Code terminal on a standard Windows XAMPP installation:

```bash
C:/xampp/php/php.exe scripts/check-reports.php
```

A successful run ends with:

```text
29 report and search integration checks passed.
```

A failure prints `FAIL:` and exits with status 1. If report-service files are missing, the script explains that the item-reports feature must be included first. Browser access returns an empty 404 and does not run the checks.

## Coverage

- Creating lost and found reports without photos and finding them by item name.
- Combining report-type and status filters.
- Returning only public fields from search.
- Showing only the author's reports in My Reports service results.
- Rejecting incomplete reports, impossible dates, and invalid types without adding searchable records.
- Rejecting another account's attempt to resolve a lost report.
- Keeping found reports open when the lost-report resolution action is attempted.
- Moving a recovered lost report from the open filter to the resolved filter.
- Keeping other reports unchanged and rejecting repeated recovery actions.
- Removing temporary reports and accounts afterward.

The script creates two temporary accounts and three reports in a database transaction. It rolls back on success or failure and checks that the temporary rows are gone. The report creation calls do not include uploads, so no photos are saved or deleted. MySQL may advance auto-increment counters even when records are rolled back.

## Verification of this script

The 29 checks passed with PHP 8.4.27 and MariaDB 11.8.9 on Linux, using the committed report backend from `499777b1fd91c0834a0f9278af07aa3df6646a97`. The existing 21-check search script also passed.

A deliberately failing assertion confirmed that the script exits with status 1 and still rolls back its temporary rows. Existing accounts, reports, private claims, and a file in photo storage were checked before and after the runs and remained unchanged. Missing prerequisites, nontransactional tables, and browser access were also checked.

Windows XAMPP verification remains to be run locally. Uploads, photo display, form CSRF, and browser navigation should be checked using the item-reports acceptance checklist when that feature is complete.
