# Search and filters — first part

Owner: Dipesh. Branch: feature/search-and-filters.

This commit adds the database part of search (FR-04) and report-type/status filters. It uses the existing schema and Database::connect(), without depending on the accounts branch. The homepage is unchanged. search.php provides a public browser search form and escaped report results.

## API

```php
require_once __DIR__ . '/src/ReportSearch.php';
$reports = ReportSearch::search('wallet', 'found', 'open');
```

Arguments are keyword, report type, and report status. Each defaults to an empty string, meaning no restriction. Types: lost or found. Statuses: open, reserved, returned, or resolved. Invalid types, statuses, malformed UTF-8 keywords, and keywords longer than 150 characters throw InvalidArgumentException.

The name search is a literal substring search: % and _ have no wildcard meaning. Matching uses the schema's utf8mb4_unicode_ci collation. Results are ordered by created_at descending, then report_id descending. An empty result is an empty array.

Only report_id, item_name, location, event_date, report_type, status, photo_path, and created_at are returned. The query does not join users or claims. User values are bound through PDO prepared statements.

ReportSearch owns search queries. When the browse page is built, it will use this class. ReportService can delegate its planned search method to ReportSearch so the design stays consistent. Report creation, photos, and details remain Shiva's responsibilities. Rendering code must escape report text and handle invalid input and database errors.

The first version returns all matching rows for a small course-project database. Pagination can be added when building the browse page if needed.

## Run the checks

Keep MySQL running in XAMPP and use the existing config/config.php. No additional schema import is needed. From Git Bash in the project folder:

```bash
/c/xampp/php/php.exe scripts/check-search.php
```

The script creates a temporary test account and five reports inside an InnoDB transaction, runs the checks, and rolls back the inserted rows. It also rolls back if a check fails. Auto-increment counters may advance. It does not delete or modify existing records. The script runs only from the terminal, not localhost.

## Verification

Both PHP files passed linting. All 21 search checks passed on PHP 8.4.27 and MariaDB 11.8.9 with the repository schema and connection class. Checks covered empty searches, combined filters, status/type choices, substring matching, literal symbols, query-injection text, public fields, sorting, rejected inputs, and fixture cleanup.

Local XAMPP check: not run yet. Dipesh should record the actual result after running the command. The search page also passed PHP linting and HTTP checks for successful loading, empty results, invalid filters, malformed query values, and escaped form text in the Linux test environment. Visual and Windows XAMPP verification remain pending.

## Open in the browser

Start Apache and MySQL in XAMPP and open http://localhost/campus-lost-and-found/search.php after updating your local feature/search-and-filters branch. Search is read-only and does not require login. If no reports have been posted, the empty-results message is expected. The CLI check uses temporary records and rolls them back, so it will not populate the browser list. Photo display and item-detail links will be connected with the reports feature.
